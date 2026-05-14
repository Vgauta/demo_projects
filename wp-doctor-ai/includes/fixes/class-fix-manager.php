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
        add_action('admin_print_scripts', array($this, 'dedupe_duplicate_scripts'), 0);
    }

    public function apply_safe_fix(array $issue): array
    {
        $issue_type = sanitize_key($issue['issue_type'] ?? '');

        if ('duplicate_script' !== $issue_type) {
            return array(
                'applied' => false,
                'reload_required' => false,
                'message' => __('No safe automatic fix is available for this issue type yet. AI generated a review-first plan instead.', 'wp-doctor-ai'),
            );
        }

        $assets = $this->issue_assets($issue);
        $fixes = get_option('wp_doctor_ai_active_fixes', array());
        $fixes = is_array($fixes) ? $fixes : array();
        $fixes['duplicate_script_dedupe'] = array(
            'enabled' => true,
            'created_at' => current_time('mysql'),
            'issue_id' => sanitize_text_field((string) ($issue['issue_uid'] ?? $issue['issue_id'] ?? 'duplicate_script')),
            'assets' => $assets,
        );
        update_option('wp_doctor_ai_active_fixes', $fixes);

        return array(
            'applied' => true,
            'reload_required' => true,
            'auto_rescan' => true,
            'message' => __('Fixed. WP Doctor AI activated the safe duplicate-script mitigation and will rescan after reload so this resolved issue is removed from the report.', 'wp-doctor-ai'),
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

        $target_assets = array_map('sanitize_text_field', (array) ($fixes['duplicate_script_dedupe']['assets'] ?? array()));
        $seen = array();
        foreach ((array) $wp_scripts->queue as $handle) {
            $registered = $wp_scripts->registered[$handle] ?? null;
            if (! $registered || empty($registered->src)) {
                continue;
            }

            $key = $this->script_key((string) $registered->src);
            if (! $key || (! empty($target_assets) && ! in_array($key, $target_assets, true))) {
                continue;
            }

            if (isset($seen[$key])) {
                wp_dequeue_script($handle);
                continue;
            }

            $seen[$key] = $handle;
        }
    }

    private function issue_assets(array $issue): array
    {
        $details = $issue['technical_details'] ?? array();
        if (is_string($details)) {
            $decoded = json_decode($details, true);
            $details = is_array($decoded) ? $decoded : array();
        }

        $assets = array();
        if (! empty($details['asset'])) {
            $assets[] = $this->script_key((string) $details['asset']);
        }

        foreach ((array) ($details['instances'] ?? array()) as $instance) {
            if (! empty($instance['src'])) {
                $assets[] = $this->script_key((string) $instance['src']);
            }
        }

        return array_values(array_unique(array_filter($assets)));
    }

    private function script_key(string $src): string
    {
        $src = remove_query_arg(array('ver', 'version'), $src);
        $path = wp_parse_url($src, PHP_URL_PATH);
        $path = $path ? $path : $src;

        $host = (string) wp_parse_url($src, PHP_URL_HOST);

        return sanitize_text_field(strtolower($host . '/' . ltrim($path, '/')));
    }
}
