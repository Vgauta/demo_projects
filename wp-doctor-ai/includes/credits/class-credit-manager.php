<?php
/**
 * Rescue Credits accounting.
 *
 * @package WPDoctorAI
 */

namespace WPDoctorAI\Credits;

use WPDoctorAI\Database\Repository;

if (! defined('ABSPATH')) {
    exit;
}

final class CreditManager
{
    private $repository;

    public function __construct(Repository $repository)
    {
        $this->repository = $repository;
    }

    public function balance(): int
    {
        return max(0, (int) get_option('wp_doctor_ai_credit_balance', 0));
    }

    public function free_scans_remaining(): int
    {
        return max(0, (int) get_option('wp_doctor_ai_free_scans_remaining', 3));
    }

    public function use_free_scan(): bool
    {
        $remaining = $this->free_scans_remaining();
        if ($remaining <= 0) {
            return false;
        }
        update_option('wp_doctor_ai_free_scans_remaining', $remaining - 1);
        return true;
    }

    public function consume(int $amount, string $action, string $reference = ''): bool
    {
        $amount = max(1, $amount);
        $balance = $this->balance();
        if ($balance < $amount) {
            return false;
        }
        $new = $balance - $amount;
        update_option('wp_doctor_ai_credit_balance', $new);
        $this->repository->log_credit(-$amount, $new, $action, $reference, 'Credit consumed for advanced feature.');
        return true;
    }

    public function adjust(int $amount, string $notes = 'Manual adjustment'): int
    {
        $new = max(0, $this->balance() + $amount);
        update_option('wp_doctor_ai_credit_balance', $new);
        $this->repository->log_credit($amount, $new, 'manual_adjustment', 'admin', $notes);
        return $new;
    }

    public function run_subscription_refill(): void
    {
        $settings = get_option('wp_doctor_ai_settings', array());
        $refill = (int) ($settings['subscription_refill'] ?? 0);
        if ($refill > 0) {
            $this->adjust($refill, 'Scheduled subscription refill.');
        }
    }
}
