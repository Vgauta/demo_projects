<?php
/**
 * Persistence gateway.
 *
 * @package WPDoctorAI
 */

namespace WPDoctorAI\Database;

use WPDoctorAI\Models\Issue;

if (! defined('ABSPATH')) {
    exit;
}

final class Repository
{
    public function create_scan(array $payload, array $issues, string $source = 'manual'): int
    {
        global $wpdb;
        $wpdb->insert($wpdb->prefix . 'wp_doctor_ai_scans', array(
            'status' => 'completed',
            'source' => sanitize_key($source),
            'page_url' => isset($payload['page_url']) ? esc_url_raw($payload['page_url']) : '',
            'raw_payload' => wp_json_encode($payload),
            'issue_count' => count($issues),
            'created_at' => current_time('mysql'),
        ), array('%s', '%s', '%s', '%s', '%d', '%s'));

        $scan_id = (int) $wpdb->insert_id;
        foreach ($issues as $issue) {
            if ($issue instanceof Issue) {
                $this->insert_issue($scan_id, $issue);
            }
        }

        return $scan_id;
    }

    public function insert_issue(int $scan_id, Issue $issue): void
    {
        global $wpdb;
        $wpdb->insert($wpdb->prefix . 'wp_doctor_ai_issues', array(
            'scan_id' => $scan_id,
            'issue_uid' => $issue->issue_id,
            'issue_type' => $issue->issue_type,
            'severity' => $issue->severity,
            'confidence' => $issue->confidence,
            'affected_plugin' => $issue->affected_plugin,
            'affected_page' => $issue->affected_page,
            'translation_key' => $issue->translation_key,
            'technical_details' => wp_json_encode($issue->technical_details),
            'probable_cause' => $issue->probable_cause,
            'suggested_fix' => $issue->suggested_fix,
            'created_at' => current_time('mysql'),
        ));
    }

    public function recent_scans(int $limit = 10): array
    {
        global $wpdb;
        return $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}wp_doctor_ai_scans ORDER BY id DESC LIMIT %d", $limit), ARRAY_A) ?: array();
    }

    public function issues(array $filters = array()): array
    {
        global $wpdb;
        $where = 'WHERE 1=1';
        $values = array();
        foreach (array('severity', 'issue_type', 'affected_plugin') as $field) {
            if (! empty($filters[$field])) {
                $where .= " AND {$field} = %s";
                $values[] = sanitize_text_field((string) $filters[$field]);
            }
        }
        $sql = "SELECT * FROM {$wpdb->prefix}wp_doctor_ai_issues {$where} ORDER BY id DESC LIMIT 100";
        return $values ? $wpdb->get_results($wpdb->prepare($sql, $values), ARRAY_A) : ($wpdb->get_results($sql, ARRAY_A) ?: array());
    }

    public function log_credit(int $amount, int $balance_after, string $action, string $reference = '', string $notes = ''): void
    {
        global $wpdb;
        $wpdb->insert($wpdb->prefix . 'wp_doctor_ai_credit_logs', array(
            'user_id' => get_current_user_id(),
            'amount' => $amount,
            'balance_after' => $balance_after,
            'action' => sanitize_key($action),
            'reference' => sanitize_text_field($reference),
            'notes' => sanitize_textarea_field($notes),
            'created_at' => current_time('mysql'),
        ));
    }

    public function credit_logs(int $limit = 50): array
    {
        global $wpdb;
        return $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}wp_doctor_ai_credit_logs ORDER BY id DESC LIMIT %d", $limit), ARRAY_A) ?: array();
    }
}
