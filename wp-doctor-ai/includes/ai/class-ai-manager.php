<?php
/**
 * Optional AI adapter facade. AI never performs diagnosis.
 *
 * @package WPDoctorAI
 */

namespace WPDoctorAI\AI;

use WPDoctorAI\Credits\CreditManager;
use WPDoctorAI\Logs\Logger;

if (! defined('ABSPATH')) {
    exit;
}

final class AiManager
{
    private $credits;
    private $logger;

    public function __construct(CreditManager $credits, Logger $logger)
    {
        $this->credits = $credits;
        $this->logger = $logger;
    }

    public function is_enabled(): bool
    {
        $settings = get_option('wp_doctor_ai_settings', array());
        return ! empty($settings['ai_enabled']) && ! empty($settings['ai_provider']) && 'none' !== $settings['ai_provider'];
    }

    public function providers(): array
    {
        return array('none', 'openai', 'claude', 'gemini', 'local');
    }

    public function summarize_issue(array $issue, string $mode, string $language): string
    {
        if (! $this->credits->consume(1, 'ai_explanation', $issue['issue_id'] ?? 'issue')) {
            return __('Add Rescue Credits to unlock advanced AI explanations.', 'wp-doctor-ai');
        }

        $this->logger->info('AI summary requested', array('provider' => $this->provider(), 'mode' => $mode));

        /**
         * Integrators can call OpenAI, Claude, Gemini, or local models here.
         * The prompt receives only deterministic issue data and must not invent diagnoses.
         */
        $summary = apply_filters('wp_doctor_ai_ai_summary', '', $issue, $mode, $language, $this->provider());
        if ($summary) {
            return sanitize_textarea_field($summary);
        }

        return __('AI adapter is configured but no provider callback returned a summary. Deterministic recommendations are still available.', 'wp-doctor-ai');
    }

    private function provider(): string
    {
        $settings = get_option('wp_doctor_ai_settings', array());
        return sanitize_key($settings['ai_provider'] ?? 'none');
    }
}
