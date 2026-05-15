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
        $issues = array_merge($issues, $this->detect_performance_issues($payload));
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

    private function detect_performance_issues(array $payload): array
    {
        $performance = is_array($payload['performance'] ?? null) ? $payload['performance'] : array();
        if (empty($performance)) {
            return array();
        }

        $issues = array();
        $page_url = $payload['page_url'] ?? '';
        $ttfb = (int) ($performance['ttfb'] ?? 0);
        $fcp = (int) ($performance['first_contentful_paint'] ?? 0);
        $lcp = (int) ($performance['largest_contentful_paint'] ?? 0);
        $cls = (float) ($performance['cumulative_layout_shift'] ?? 0);
        $slow_resources = $this->important_slow_resources((array) ($performance['slow_resources'] ?? array()));

        if ($ttfb > 800) {
            $issues[] = $this->performance_issue(
                'page_speed_ttfb_' . md5($page_url . $ttfb),
                'page_speed_ttfb',
                $ttfb > 1800 ? 'critical' : 'high',
                __('Slow server response detected.', 'wp-doctor-ai'),
                sprintf(__('The server took about %d ms before sending the first byte. This usually means hosting, cache, database, or uncached PHP work is slowing every page before visitors see content.', 'wp-doctor-ai'), $ttfb),
                __('Turn on full-page caching, verify object cache, reduce heavy plugins on the page, check hosting CPU/database time, and exclude only truly dynamic pages from cache.', 'wp-doctor-ai'),
                __('High TTFB directly increases FCP and LCP, so PageSpeed can show very slow load times even when the browser interaction score is good.', 'wp-doctor-ai'),
                array('metric' => 'TTFB', 'value_ms' => $ttfb, 'target_ms' => 800, 'performance' => $performance),
                $page_url
            );
        }

        if ($fcp > 1800) {
            $issues[] = $this->performance_issue(
                'page_speed_fcp_' . md5($page_url . $fcp),
                'page_speed_fcp',
                $fcp > 4000 ? 'critical' : 'high',
                __('Slow First Contentful Paint detected.', 'wp-doctor-ai'),
                sprintf(__('Visitors wait about %d ms before the first visible content appears. This is often caused by slow server response, render-blocking CSS/JS, or too much above-the-fold work.', 'wp-doctor-ai'), $fcp),
                __('Enable page cache, inline/preload only critical CSS, defer non-critical JavaScript, remove unused page-builder assets, and preload the main font or hero image.', 'wp-doctor-ai'),
                __('A slow FCP makes the whole site feel blank or stuck at the beginning of the visit.', 'wp-doctor-ai'),
                array('metric' => 'FCP', 'value_ms' => $fcp, 'target_ms' => 1800, 'performance' => $performance),
                $page_url
            );
        }

        if ($lcp > 2500) {
            $issues[] = $this->performance_issue(
                'page_speed_lcp_' . md5($page_url . $lcp),
                'page_speed_lcp',
                $lcp > 4000 ? 'critical' : 'high',
                __('Slow Largest Contentful Paint detected.', 'wp-doctor-ai'),
                sprintf(__('The main above-the-fold content took about %d ms to become visible. This usually points to an oversized hero image, slow server response, render-blocking files, or lazy-loading the hero element.', 'wp-doctor-ai'), $lcp),
                __('Compress and resize the hero image, convert it to WebP/AVIF, preload it, do not lazy-load the first visible image, enable cache/CDN, and defer scripts that are not needed above the fold.', 'wp-doctor-ai'),
                __('Slow LCP is one of the biggest reasons PageSpeed and Core Web Vitals fail.', 'wp-doctor-ai'),
                array('metric' => 'LCP', 'value_ms' => $lcp, 'target_ms' => 2500, 'performance' => $performance),
                $page_url
            );
        }

        if ($cls > 0.1) {
            $issues[] = $this->performance_issue(
                'page_speed_cls_' . md5($page_url . $cls),
                'page_speed_cls',
                $cls > 0.25 ? 'high' : 'medium',
                __('Layout shift detected.', 'wp-doctor-ai'),
                sprintf(__('The page moved while loading. The measured CLS was %.3f; keep it below 0.100.', 'wp-doctor-ai'), $cls),
                __('Add width and height to images/iframes, reserve space for banners and ads, avoid inserting content above existing content, and preload fonts with font-display swap.', 'wp-doctor-ai'),
                __('Layout shifts make visitors lose their place and can cause accidental clicks.', 'wp-doctor-ai'),
                array('metric' => 'CLS', 'value' => $cls, 'target' => 0.1, 'performance' => $performance),
                $page_url
            );
        }

        if (! empty($slow_resources)) {
            $issues[] = $this->performance_issue(
                'page_speed_slow_resources_' . md5($page_url . wp_json_encode($slow_resources)),
                'page_speed_slow_resources',
                'medium',
                __('Heavy or slow page assets detected.', 'wp-doctor-ai'),
                __('Large images, fonts, CSS, or JavaScript files are slowing the page and can push FCP/LCP higher.', 'wp-doctor-ai'),
                __('Compress large images, remove unused scripts/styles on pages that do not need them, delay third-party scripts, and serve static files through cache/CDN.', 'wp-doctor-ai'),
                __('Every heavy asset competes for bandwidth and can make the full site feel slower.', 'wp-doctor-ai'),
                array('resources' => $slow_resources),
                $page_url
            );
        }

        return $issues;
    }

    private function performance_issue(string $id, string $type, string $severity, string $headline, string $cause, string $fix, string $impact, array $details, string $page_url): Issue
    {
        return new Issue(array(
            'issue_id' => $id,
            'issue_type' => $type,
            'severity' => $severity,
            'confidence' => 90,
            'affected_plugin' => 'performance',
            'affected_page' => $page_url,
            'translation_key' => $type . '_detected',
            'technical_details' => array_merge(array('headline' => $headline), $details),
            'probable_cause' => $cause,
            'suggested_fix' => $fix,
            'impact' => $impact,
        ));
    }

    private function important_slow_resources(array $resources): array
    {
        $filtered = array();
        foreach ($resources as $resource) {
            if (! is_array($resource)) {
                continue;
            }

            $transfer_size = (int) ($resource['transfer_size'] ?? 0);
            $duration = (int) ($resource['duration'] ?? 0);
            $blocking = 'blocking' === (string) ($resource['render_blocking_status'] ?? '');

            if ($transfer_size < 150000 && $duration < 1000 && ! $blocking) {
                continue;
            }

            $filtered[] = array(
                'name' => esc_url_raw((string) ($resource['name'] ?? '')),
                'type' => sanitize_key((string) ($resource['initiator_type'] ?? 'resource')),
                'duration_ms' => $duration,
                'transfer_kb' => round($transfer_size / 1024, 1),
                'render_blocking' => $blocking,
            );
        }

        usort($filtered, static function ($a, $b) {
            return ($b['duration_ms'] + ($b['transfer_kb'] * 4)) <=> ($a['duration_ms'] + ($a['transfer_kb'] * 4));
        });

        return array_slice($filtered, 0, 10);
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
            $script['wpda_canonical_src'] = $key;
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
        $src = trim(remove_query_arg(array('ver', 'version'), $src));
        if ('' === $src) {
            return '';
        }

        $path = wp_parse_url($src, PHP_URL_PATH);
        if (! $path) {
            return '';
        }

        $host = (string) wp_parse_url($src, PHP_URL_HOST);

        return sanitize_text_field(strtolower($host . '/' . ltrim($path, '/')));
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
