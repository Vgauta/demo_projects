<?php
/**
 * Safe one-click mitigation manager.
 *
 * @package WPDoctorAI
 */

namespace WPDoctorAI\Fixes;

use WPDoctorAI\Logs\Logger;

if (! defined('ABSPATH')) {
    exit;
}

final class FixManager
{
    private $logger;

    public function __construct(?Logger $logger = null)
    {
        $this->logger = $logger;
    }

    public function hooks(): void
    {
        add_action('wp_print_scripts', array($this, 'dedupe_duplicate_scripts'), 0);
        add_action('admin_print_scripts', array($this, 'dedupe_duplicate_scripts'), 0);
    }

    public function apply_safe_fix(array $issue): array
    {
        $issue_type = sanitize_key($issue['issue_type'] ?? '');

        if ('duplicate_script' !== $issue_type) {
            $this->log('Automatic fix skipped: no production-safe runtime fix exists for issue type yet.', array('issue_type' => $issue_type));
            return array(
                'applied' => false,
                'reload_required' => false,
                'rollback_available' => false,
                'message' => __('No safe automatic fix is available for this issue type yet. AI generated a review-first plan instead.', 'wp-doctor-ai'),
            );
        }

        $assets = $this->issue_assets($issue);
        if (empty($assets)) {
            $this->log('Automatic duplicate-script fix skipped: no exact canonical script asset could be validated.', array('issue_type' => $issue_type));
            return array(
                'applied' => false,
                'reload_required' => false,
                'rollback_available' => false,
                'message' => __('WP Doctor AI could not validate the exact duplicate script safely, so no automatic change was applied.', 'wp-doctor-ai'),
            );
        }

        $fix_id = 'duplicate_script_dedupe';
        $fixes = $this->active_fixes();
        $fixes[$fix_id] = array(
            'id' => $fix_id,
            'enabled' => true,
            'created_at' => current_time('mysql'),
            'issue_id' => sanitize_text_field((string) ($issue['issue_uid'] ?? $issue['issue_id'] ?? 'duplicate_script')),
            'assets' => $assets,
            'strategy' => 'runtime_dequeue_exact_duplicate_only',
            'rollback_available' => true,
            'compatibility' => array(
                'no_file_edits' => true,
                'skip_dependent_handles' => true,
                'exact_canonical_src_only' => true,
            ),
        );
        update_option('wp_doctor_ai_active_fixes', $fixes, false);
        $this->log('Automatic duplicate-script mitigation activated.', array('fix_id' => $fix_id, 'assets' => $assets));

        return array(
            'applied' => true,
            'fix_id' => $fix_id,
            'reload_required' => true,
            'auto_rescan' => true,
            'rollback_available' => true,
            'message' => __('Fixed safely. WP Doctor AI activated a reversible runtime duplicate-script mitigation, skipped file edits, and will rescan after reload so this resolved issue is removed from the report.', 'wp-doctor-ai'),
        );
    }

    public function rollback_fix(string $fix_id = ''): array
    {
        $fix_id = sanitize_key($fix_id ?: 'duplicate_script_dedupe');
        $fixes = $this->active_fixes();
        if (empty($fixes[$fix_id])) {
            return array(
                'rolled_back' => false,
                'message' => __('No active fix was found to roll back.', 'wp-doctor-ai'),
            );
        }

        unset($fixes[$fix_id]);
        update_option('wp_doctor_ai_active_fixes', $fixes, false);
        $this->log('Automatic mitigation rolled back.', array('fix_id' => $fix_id));

        return array(
            'rolled_back' => true,
            'fix_id' => $fix_id,
            'reload_required' => true,
            'message' => __('Rollback complete. Reload and rescan to confirm the site is back to its original runtime behavior.', 'wp-doctor-ai'),
        );
    }

    public function active_fixes(): array
    {
        $fixes = get_option('wp_doctor_ai_active_fixes', array());
        return is_array($fixes) ? $fixes : array();
    }

    public function dedupe_duplicate_scripts(): void
    {
        $fixes = $this->active_fixes();
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
                if ($this->has_queued_dependents((string) $handle)) {
                    $this->log('Duplicate script dequeue skipped because another queued script depends on this handle.', array('handle' => $handle, 'asset' => $key));
                    continue;
                }

                wp_dequeue_script($handle);
                $this->log('Duplicate script dequeued at runtime.', array('handle' => $handle, 'kept_handle' => $seen[$key], 'asset' => $key));
                continue;
            }

            $seen[$key] = $handle;
        }
    }

    private function has_queued_dependents(string $handle): bool
    {
        global $wp_scripts;
        foreach ((array) ($wp_scripts->queue ?? array()) as $queued_handle) {
            $registered = $wp_scripts->registered[$queued_handle] ?? null;
            if ($registered && in_array($handle, (array) ($registered->deps ?? array()), true)) {
                return true;
            }
        }
        return false;
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

    private function log(string $message, array $context = array()): void
    {
        if ($this->logger) {
            $this->logger->info($message, $context);
        }
    }
}
