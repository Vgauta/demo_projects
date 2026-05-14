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
        $settings = $this->settings();
        return ! empty($settings['ai_enabled']) && ! empty($settings['ai_provider']) && 'none' !== $settings['ai_provider'];
    }

    public function has_api_key(): bool
    {
        return '' !== $this->api_key();
    }

    public function api_key(): string
    {
        $settings = $this->settings();
        return sanitize_text_field((string) ($settings['google_ai_studio_api_key'] ?? ''));
    }

    public function model(): string
    {
        $settings = $this->settings();
        $model = sanitize_text_field((string) ($settings['gemini_model'] ?? 'gemini-1.5-flash'));
        return $model ?: 'gemini-1.5-flash';
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

        $summary = apply_filters('wp_doctor_ai_ai_summary', '', $issue, $mode, $language, $this->provider());
        if ($summary) {
            return sanitize_textarea_field($summary);
        }

        if ($this->has_api_key()) {
            return $this->gemini_request($this->build_explanation_prompt($issue, $mode, $language));
        }

        return __('AI adapter is configured but no provider callback returned a summary. Deterministic recommendations are still available.', 'wp-doctor-ai');
    }

    public function solution_plan(array $issue, string $language): array
    {
        if (! $this->has_api_key()) {
            return array(
                'needs_api_key' => true,
                'message' => __('Add your Google AI Studio API key before using premium AI one-click solutions.', 'wp-doctor-ai'),
                'steps' => array(),
            );
        }

        $text = apply_filters('wp_doctor_ai_ai_solution_plan', '', $issue, $language, $this->provider());
        if (! $text) {
            $text = $this->gemini_request($this->build_solution_prompt($issue, $language));
        }

        if (! $text) {
            return array(
                'needs_api_key' => false,
                'message' => __('AI did not return a solution plan. Keep using the deterministic safe recommendation.', 'wp-doctor-ai'),
                'steps' => array(),
            );
        }

        return array(
            'needs_api_key' => false,
            'message' => __('AI safe solution plan generated. Review it before making changes.', 'wp-doctor-ai'),
            'steps' => $this->split_steps($text),
        );
    }

    private function provider(): string
    {
        $settings = $this->settings();
        return sanitize_key($settings['ai_provider'] ?? 'none');
    }

    private function settings(): array
    {
        $settings = get_option('wp_doctor_ai_settings', array());
        return is_array($settings) ? $settings : array();
    }

    private function build_explanation_prompt(array $issue, string $mode, string $language): string
    {
        return 'Explain this deterministic WordPress issue in ' . $language . ' for ' . $mode . '. Do not invent a diagnosis. Use only the provided issue JSON and give safe, reversible advice. Issue JSON: ' . wp_json_encode($issue);
    }

    private function build_solution_prompt(array $issue, string $language): string
    {
        return 'You are WP Doctor AI. Create a safe premium one-click solution plan in ' . $language . '. Use only this deterministic WordPress issue JSON. Do not claim you applied a fix. Do not suggest editing core files or destructive actions. Return 3 to 6 concise numbered steps for staging/safe-mode testing and verification. Issue JSON: ' . wp_json_encode($issue);
    }

    private function gemini_request(string $prompt): string
    {
        $api_key = $this->api_key();
        if (! $api_key) {
            return '';
        }

        $endpoint = add_query_arg(
            'key',
            $api_key,
            'https://generativelanguage.googleapis.com/v1beta/models/' . rawurlencode($this->model()) . ':generateContent'
        );

        $response = wp_remote_post($endpoint, array(
            'timeout' => 20,
            'headers' => array('Content-Type' => 'application/json'),
            'body' => wp_json_encode(array(
                'contents' => array(
                    array(
                        'role' => 'user',
                        'parts' => array(array('text' => $prompt)),
                    ),
                ),
                'generationConfig' => array(
                    'temperature' => 0.2,
                    'maxOutputTokens' => 700,
                ),
            )),
        ));

        if (is_wp_error($response)) {
            $this->logger->info('Gemini request failed', array('error' => $response->get_error_message()));
            return '';
        }

        $body = json_decode((string) wp_remote_retrieve_body($response), true);
        $text = $body['candidates'][0]['content']['parts'][0]['text'] ?? '';

        return sanitize_textarea_field((string) $text);
    }

    private function split_steps(string $text): array
    {
        $lines = preg_split('/\r\n|\r|\n/', $text) ?: array($text);
        $steps = array();

        foreach ($lines as $line) {
            $line = trim(preg_replace('/^[-*\d.)\s]+/', '', $line));
            if ('' !== $line) {
                $steps[] = sanitize_textarea_field($line);
            }
        }

        return $steps ?: array(sanitize_textarea_field($text));
    }
}
