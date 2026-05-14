<?php
/**
 * Safe one-click mitigation manager.
 *
 * @package WPDoctorAI
 */

namespace WPDoctorAI\Fixes;

if (! defined('ABSPATH')) {
    exit;
}

final class FixManager
{
    public function hooks(): void
    {
        add_action('wp_print_scripts', array($this, 'dedupe_duplicate_scripts'), 0);
    }

    public function apply_safe_fix(array $issue): array
    {
        $issue_type = sanitize_key($issue['issue_type'] ?? '');

        if ('duplicate_script' !== $issue_type) {
            return array(
                'applied' => false,
                'message' => __('No safe automatic fix is available for this issue type yet. AI generated a review-first plan instead.', 'wp-doctor-ai'),
            );
        }

        $fixes = get_option('wp_doctor_ai_active_fixes', array());
        $fixes = is_array($fixes) ? $fixes : array();
        $fixes['duplicate_script_dedupe'] = array(
            'enabled' => true,
            'created_at' => current_time('mysql'),
            'issue_id' => sanitize_text_field((string) ($issue['issue_uid'] ?? $issue['issue_id'] ?? 'duplicate_script')),
        );
        update_option('wp_doctor_ai_active_fixes', $fixes);

        return array(
            'applied' => true,
            'message' => __('Safe duplicate-script mitigation has been enabled. WP Doctor AI will keep the first matching script and dequeue later duplicates before WordPress prints scripts.', 'wp-doctor-ai'),
        );
    }

    public function dedupe_duplicate_scripts(): void
    {
        $fixes = get_option('wp_doctor_ai_active_fixes', array());
        if (empty($fixes['duplicate_script_dedupe']['enabled'])) {
            return;
        }

        global $wp_scripts;
        if (! $wp_scripts || empty($wp_scripts->queue)) {
            return;
        }

        $seen = array();
        foreach ((array) $wp_scripts->queue as $handle) {
            $registered = $wp_scripts->registered[$handle] ?? null;
            if (! $registered || empty($registered->src)) {
                continue;
            }

            $key = $this->script_key((string) $registered->src);
            if (! $key) {
                continue;
            }

            if (isset($seen[$key])) {
                wp_dequeue_script($handle);
                continue;
            }

            $seen[$key] = $handle;
        }
    }

    private function script_key(string $src): string
    {
        $src = remove_query_arg(array('ver', 'version'), $src);
        $path = wp_parse_url($src, PHP_URL_PATH);
        $path = $path ? $path : $src;

        return strtolower(trim($path));
    }
}
