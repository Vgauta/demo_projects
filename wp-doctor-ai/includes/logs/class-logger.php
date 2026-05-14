<?php
/**
 * Privacy-friendly logger wrapper.
 *
 * @package WPDoctorAI
 */

namespace WPDoctorAI\Logs;

use WPDoctorAI\Database\Repository;

if (! defined('ABSPATH')) {
    exit;
}

final class Logger
{
    private $repository;

    public function __construct(Repository $repository)
    {
        $this->repository = $repository;
    }

    public function info(string $message, array $context = array()): void
    {
        do_action('wp_doctor_ai_log', 'info', sanitize_text_field($message), $context);
    }

    public function warning(string $message, array $context = array()): void
    {
        do_action('wp_doctor_ai_log', 'warning', sanitize_text_field($message), $context);
    }
}
