<?php
/**
 * Deterministic detection engine.
 *
 * @package WPDoctorAI
 */

namespace WPDoctorAI\Detectors;

use WPDoctorAI\Models\Issue;

if (! defined('ABSPATH')) {
    exit;
}

final class DetectionEngine
{
    /** @return Issue[] */
    public function detect(array $payload): array
    {
        $issues = array();
        $issues = array_merge($issues, $this->detect_console_errors($payload));
        $issues = array_merge($issues, $this->detect_ajax_failures($payload));
        $issues = array_merge($issues, $this->detect_duplicate_scripts($payload));
        $issues = array_merge($issues, $this->detect_jquery_conflicts($payload));
        $issues = array_merge($issues, $this->detect_elementor_crashes($payload));

        return apply_filters('wp_doctor_ai_detected_issues', $issues, $payload);
    }

    private function detect_console_errors(array $payload): array
    {
        $issues = array();
        foreach (($payload['console_errors'] ?? array()) as $index => $error) {
            $message = strtolower((string) ($error['message'] ?? ''));
            $severity = false !== strpos($message, 'syntax') || false !== strpos($message, 'uncaught') ? 'high' : 'medium';
            $issues[] = new Issue(array(
                'issue_id' => 'console_error_' . md5($message . $index),
                'issue_type' => 'console_error',
                'severity' => $severity,
                'confidence' => 88,
                'affected_plugin' => $this->guess_plugin($error['source'] ?? ''),
                'affected_page' => $payload['page_url'] ?? '',
                'translation_key' => 'console_error_detected',
                'technical_details' => $error,
                'probable_cause' => __('A JavaScript exception was thrown by a loaded asset.', 'wp-doctor-ai'),
                'suggested_fix' => __('Update or temporarily isolate the affected plugin/theme script in staging and inspect the stack trace.', 'wp-doctor-ai'),
                'impact' => __('Interactive UI elements may stop working.', 'wp-doctor-ai'),
            ));
        }
        return $issues;
    }

    private function detect_ajax_failures(array $payload): array
    {
        $issues = array();
        foreach (($payload['ajax_failures'] ?? array()) as $index => $failure) {
            $status = (int) ($failure['status'] ?? 0);
            $issues[] = new Issue(array(
                'issue_id' => 'ajax_failure_' . md5(($failure['url'] ?? '') . $status . $index),
                'issue_type' => 'ajax_failure',
                'severity' => $status >= 500 ? 'critical' : ($status >= 400 ? 'high' : 'medium'),
                'confidence' => 90,
                'affected_plugin' => $this->guess_plugin($failure['url'] ?? ''),
                'affected_page' => $payload['page_url'] ?? '',
                'translation_key' => 'ajax_failure_detected',
                'technical_details' => $failure,
                'probable_cause' => 403 === $status ? __('A nonce, permission, or security rule likely blocked the request.', 'wp-doctor-ai') : __('A background request failed or timed out.', 'wp-doctor-ai'),
                'suggested_fix' => __('Check security plugins, nonce generation, server logs, and the endpoint response in staging.', 'wp-doctor-ai'),
                'impact' => __('Forms, checkout, search, or editor actions may fail.', 'wp-doctor-ai'),
            ));
        }
        return $issues;
    }

    private function detect_duplicate_scripts(array $payload): array
    {
        $scripts = $payload['scripts'] ?? array();
        $seen = array();
        $issues = array();
        foreach ($scripts as $script) {
            $key = $this->normalize_script_key($script['src'] ?? '');
            if (! $key) {
                continue;
            }
            $seen[$key][] = $script;
        }
        foreach ($seen as $key => $items) {
            if (count($items) > 1) {
                $issues[] = new Issue(array(
                    'issue_id' => 'duplicate_script_' . md5($key),
                    'issue_type' => 'duplicate_script',
                    'severity' => 'medium',
                    'confidence' => 84,
                    'affected_plugin' => $this->guess_plugin($items[0]['src'] ?? ''),
                    'affected_page' => $payload['page_url'] ?? '',
                    'translation_key' => 'duplicate_script_detected',
                    'technical_details' => array('asset' => $key, 'instances' => $items),
                    'probable_cause' => __('Two or more components enqueue the same library or asset.', 'wp-doctor-ai'),
                    'suggested_fix' => __('Keep one canonical enqueue and disable redundant loading through plugin settings or child theme code.', 'wp-doctor-ai'),
                    'impact' => __('Can slow pages or create incompatible JavaScript state.', 'wp-doctor-ai'),
                ));
            }
        }
        return $issues;
    }

    private function detect_jquery_conflicts(array $payload): array
    {
        $versions = array();
        foreach (($payload['scripts'] ?? array()) as $script) {
            $src = (string) ($script['src'] ?? '');
            if (preg_match('/jquery(?:\.min)?\.js(?:\?ver=([^&]+))?/i', $src, $matches)) {
                $versions[] = array('src' => $src, 'version' => $matches[1] ?? ($script['version'] ?? 'unknown'));
            }
        }
        $unique = array_unique(array_map(static function ($item) { return $item['version']; }, $versions));
        $markers = $payload['jquery_markers'] ?? array();
        if (count($unique) > 1 || ! empty($markers['no_conflict_error'])) {
            return array(new Issue(array(
                'issue_id' => 'jquery_conflict_' . md5(wp_json_encode($versions) . wp_json_encode($markers)),
                'issue_type' => 'jquery_conflict',
                'severity' => 'high',
                'confidence' => count($unique) > 1 ? 92 : 78,
                'affected_plugin' => $this->guess_plugin($versions[0]['src'] ?? ''),
                'affected_page' => $payload['page_url'] ?? '',
                'translation_key' => 'jquery_conflict_detected',
                'technical_details' => array('versions' => $versions, 'markers' => $markers),
                'probable_cause' => __('Multiple jQuery versions or noConflict misuse was detected.', 'wp-doctor-ai'),
                'suggested_fix' => __('Use the WordPress-bundled jQuery dependency and remove hard-coded jQuery includes from plugins or theme templates.', 'wp-doctor-ai'),
                'impact' => __('Buttons, modals, checkout, Elementor, and legacy plugin interactions can break.', 'wp-doctor-ai'),
            )));
        }
        return array();
    }

    private function detect_elementor_crashes(array $payload): array
    {
        $issues = array();
        foreach (($payload['elementor_events'] ?? array()) as $index => $event) {
            $issues[] = new Issue(array(
                'issue_id' => 'elementor_crash_' . md5(wp_json_encode($event) . $index),
                'issue_type' => 'elementor_crash',
                'severity' => ! empty($event['editor']) ? 'critical' : 'high',
                'confidence' => 86,
                'affected_plugin' => 'elementor',
                'affected_page' => $payload['page_url'] ?? '',
                'translation_key' => 'elementor_crash_detected',
                'technical_details' => $event,
                'probable_cause' => __('An Elementor widget, dependency, or editor request crashed.', 'wp-doctor-ai'),
                'suggested_fix' => __('Update Elementor/add-ons, disable suspect widgets in staging, and inspect failed editor dependencies.', 'wp-doctor-ai'),
                'impact' => __('Page layouts or the Elementor editor may fail to load.', 'wp-doctor-ai'),
            ));
        }
        return $issues;
    }

    private function normalize_script_key(string $src): string
    {
        $src = strtok($src, '?') ?: $src;
        return strtolower(basename($src));
    }

    private function guess_plugin(string $value): string
    {
        if (preg_match('#/wp-content/plugins/([^/]+)/#', $value, $matches)) {
            return sanitize_key($matches[1]);
        }
        if (false !== strpos($value, 'elementor')) {
            return 'elementor';
        }
        if (false !== strpos($value, 'woocommerce')) {
            return 'woocommerce';
        }
        return 'unknown';
    }
}
