<?php
/**
 * Scanner orchestration service.
 *
 * @package WPDoctorAI
 */

namespace WPDoctorAI\Scanners;

use WPDoctorAI\Database\Repository;
use WPDoctorAI\Detectors\DetectionEngine;
use WPDoctorAI\Logs\Logger;

if (! defined('ABSPATH')) {
    exit;
}

final class ScannerEngine
{
    private $detector;
    private $repository;
    private $logger;

    public function __construct(DetectionEngine $detector, Repository $repository, Logger $logger)
    {
        $this->detector = $detector;
        $this->repository = $repository;
        $this->logger = $logger;
    }

    public function run(array $payload, string $source = 'manual'): array
    {
        $payload = $this->sanitize_payload($payload);
        $issues = $this->detector->detect($payload);
        $scan_id = $this->repository->create_scan($payload, $issues, $source);
        $this->logger->info('Scan completed', array('scan_id' => $scan_id, 'issue_count' => count($issues)));

        return array(
            'scan_id' => $scan_id,
            'issues' => array_map(static function ($issue) {
                return $issue->to_array();
            }, $issues),
        );
    }

    private function sanitize_payload(array $payload): array
    {
        $payload['page_url'] = isset($payload['page_url']) ? esc_url_raw($payload['page_url']) : home_url('/');
        foreach (array('console_errors', 'ajax_failures', 'scripts', 'elementor_events') as $list) {
            $payload[$list] = array_values(array_filter(array_map(array($this, 'sanitize_record'), $payload[$list] ?? array())));
        }
        $payload['performance'] = is_array($payload['performance'] ?? null) ? $this->sanitize_record($payload['performance']) : array();
        $payload['jquery_markers'] = is_array($payload['jquery_markers'] ?? null) ? array_map('sanitize_text_field', $payload['jquery_markers']) : array();
        return $payload;
    }

    private function sanitize_record($record): array
    {
        if (! is_array($record)) {
            return array();
        }
        $clean = array();
        foreach ($record as $key => $value) {
            $key = sanitize_key((string) $key);
            if (is_array($value)) {
                $clean[$key] = $this->sanitize_record($value);
            } elseif (false !== strpos($key, 'url') || 'src' === $key || 'source' === $key) {
                $clean[$key] = esc_url_raw((string) $value);
            } else {
                $clean[$key] = sanitize_textarea_field((string) $value);
            }
        }
        return $clean;
    }
}
