<?php
/**
 * Optional AI adapter facade. AI never performs diagnosis.
 *
 * @package WPDoctorAI
 */

namespace WPDoctorAI\AI;

use WPDoctorAI\Credits\CreditManager;
use WPDoctorAI\Licensing\LicenseManager;
use WPDoctorAI\Logs\Logger;

if (! defined('ABSPATH')) {
    exit;
}

final class AiManager
{
    private $credits;
    private $logger;
    private $licensing;
    private $last_error = '';

    public function __construct(CreditManager $credits, Logger $logger, LicenseManager $licensing)
    {
        $this->credits = $credits;
        $this->logger = $logger;
        $this->licensing = $licensing;
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
        return 'gemini-2.0-flash';
    }

    public function providers(): array
    {
        return array('none', 'openai', 'claude', 'gemini', 'local');
    }

    public function is_premium(): bool
    {
        return $this->licensing->is_premium();
    }

    public function summarize_issue(array $issue, string $mode, string $language): string
    {
        if (! $this->has_api_key()) {
            return __('Add your Google AI Studio API key before using AI explanations.', 'wp-doctor-ai');
        }

        if (! $this->is_premium() && ! $this->credits->consume(1, 'ai_explanation', $issue['issue_id'] ?? 'issue')) {
            return __('Add Rescue Credits to unlock advanced AI explanations.', 'wp-doctor-ai');
        }

        $this->logger->info('AI explanation requested', array('provider' => $this->provider(), 'mode' => $mode, 'premium' => $this->is_premium()));

        $summary = apply_filters('wp_doctor_ai_ai_summary', '', $issue, $mode, $language, $this->provider());
        if ($summary) {
            return sanitize_textarea_field($summary);
        }

        if ($this->has_api_key()) {
            $text = $this->gemini_request($this->build_explanation_prompt($issue, $mode, $language));
            if ($text) {
                return $this->ensure_complete_explanation($text, $issue);
            }

            return $this->last_error ? sprintf(__('AI did not return an explanation. Gemini said: %s', 'wp-doctor-ai'), $this->last_error) : __('AI did not return an explanation. Check your Google AI Studio API key, quota, and model.', 'wp-doctor-ai');
        }

        return __('AI adapter is configured but no provider callback returned a summary. Deterministic recommendations are still available.', 'wp-doctor-ai');
    }

    public function last_error(): string
    {
        return $this->last_error;
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
                'message' => $this->last_error ? sprintf(__('AI did not return a solution plan. Gemini said: %s', 'wp-doctor-ai'), $this->last_error) : __('AI did not return a solution plan. Keep using the deterministic safe recommendation.', 'wp-doctor-ai'),
                'steps' => array(),
            );
        }

        return array(
            'needs_api_key' => false,
            'message' => __('AI safe solution plan generated. Review it before making changes.', 'wp-doctor-ai'),
            'steps' => $this->complete_solution_steps($this->split_steps($text), $issue),
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
        $mode_instructions = array(
            'beginner' => 'Use simple, non-technical words and explain what the site owner should notice.',
            'developer' => 'Use technical details, likely enqueue/runtime causes, and debugging checks.',
            'store_owner' => 'Focus on sales, checkout, forms, and customer-impact language.',
            'agency' => 'Focus on client communication, staging workflow, priority, and handoff steps.',
        );
        $instruction = $mode_instructions[$mode] ?? $mode_instructions['beginner'];

        return 'You are WP Doctor AI. Write in ' . $language . '. Audience mode: ' . $mode . '. ' . $instruction . ' IMPORTANT: Do not greet the user. Do not say only that you found something. Start directly with the issue. Use only the deterministic WordPress issue JSON below. Return a complete explanation with these exact labels: Issue, What it means, Why it matters, Likely cause, Safe next steps. Mention the issue_type and affected_plugin if present. Do not invent plugins, files, or causes not present in the data. Do not claim you changed the website. Write 120 to 220 words. Issue JSON: ' . wp_json_encode($issue);
    }

    private function build_solution_prompt(array $issue, string $language): string
    {
        return 'You are WP Doctor AI. Create a safe premium one-click solution plan in ' . $language . '. Use only this deterministic WordPress issue JSON. If the issue_type starts with page_speed_, explain the fix in simple site-owner language and focus on cache, images, fonts, render-blocking CSS/JS, heavy plugins, CDN, and verification in PageSpeed/Core Web Vitals. Do not claim you applied a fix. Do not suggest editing core files or destructive actions. Return 3 to 6 concise numbered steps for staging/safe-mode testing and verification. Issue JSON: ' . wp_json_encode($issue);
    }

    private function gemini_request(string $prompt): string
    {
        $api_key = $this->api_key();
        if (! $api_key) {
            $this->last_error = __('Missing Google AI Studio API key.', 'wp-doctor-ai');
            return '';
        }

        $this->last_error = '';
        $models = array_values(array_unique(array_filter(array(
            $this->model(),
            'gemini-2.0-flash',
            'gemini-2.5-flash',
        ))));

        foreach ($models as $model) {
            $text = $this->gemini_model_request($prompt, $api_key, $model);
            if ($text) {
                return $text;
            }
        }

        return '';
    }

    private function gemini_model_request(string $prompt, string $api_key, string $model): string
    {
        $endpoint = add_query_arg(
            'key',
            $api_key,
            'https://generativelanguage.googleapis.com/v1beta/models/' . rawurlencode($model) . ':generateContent'
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
                    'maxOutputTokens' => 2200,
                ),
            )),
        ));

        if (is_wp_error($response)) {
            $this->last_error = $response->get_error_message();
            $this->logger->info('Gemini request failed', array('error' => $this->last_error, 'model' => $model));
            return '';
        }

        $body = json_decode((string) wp_remote_retrieve_body($response), true);
        $code = (int) wp_remote_retrieve_response_code($response);
        if ($code >= 400) {
            $this->last_error = sanitize_text_field((string) ($body['error']['message'] ?? wp_remote_retrieve_response_message($response)));
            $this->logger->info('Gemini request rejected', array('error' => $this->last_error, 'model' => $model, 'code' => $code));
            return '';
        }

        $text = $body['candidates'][0]['content']['parts'][0]['text'] ?? '';
        if (! $text) {
            $this->last_error = sanitize_text_field((string) ($body['promptFeedback']['blockReason'] ?? __('Empty Gemini response.', 'wp-doctor-ai')));
        }

        return sanitize_textarea_field((string) $text);
    }


    private function ensure_complete_explanation(string $text, array $issue): string
    {
        $plain = trim(wp_strip_all_tags($text));
        $looks_incomplete = strlen($plain) < 220 || preg_match('/(what I found:?|let\'s talk about what|don\'t worry,? I\'ll)$/i', $plain);

        if (! $looks_incomplete) {
            return $text;
        }

        $details = array_filter(array(
            'Issue type: ' . sanitize_text_field((string) ($issue['issue_type'] ?? 'unknown')),
            'Severity: ' . sanitize_text_field((string) ($issue['severity'] ?? 'unknown')),
            'Affected plugin: ' . sanitize_text_field((string) ($issue['affected_plugin'] ?? 'unknown')),
            'Probable cause: ' . sanitize_textarea_field((string) ($issue['probable_cause'] ?? '')),
            'Safe next step: ' . sanitize_textarea_field((string) ($issue['suggested_fix'] ?? '')),
        ));

        return trim($text . "\n\n" . __('WP Doctor AI detected details:', 'wp-doctor-ai') . "\n- " . implode("\n- ", $details));
    }

    private function complete_solution_steps(array $steps, array $issue): array
    {
        $steps = array_values(array_filter(array_map('sanitize_textarea_field', $steps)));
        $last = trim((string) end($steps));
        $looks_cut_off = '' === $last || preg_match('#[/,:;\-]$#', $last) || count($steps) < 3;

        if (! $looks_cut_off) {
            return $steps;
        }

        $fallbacks = array(
            sprintf(__('Confirm the detected issue type is still present before changing anything: %s.', 'wp-doctor-ai'), sanitize_key($issue['issue_type'] ?? 'issue')),
            sanitize_textarea_field((string) ($issue['suggested_fix'] ?? __('Apply the safest reversible mitigation available for this issue.', 'wp-doctor-ai'))),
            __('Run WP Doctor AI again after the change. The fix is successful only when the issue disappears from the new scan.', 'wp-doctor-ai'),
        );

        return array_values(array_unique(array_merge($steps, $fallbacks)));
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
