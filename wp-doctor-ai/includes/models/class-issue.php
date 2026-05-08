<?php
/**
 * Structured issue object. Scanners and detectors never generate prose.
 *
 * @package WPDoctorAI
 */

namespace WPDoctorAI\Models;

if (! defined('ABSPATH')) {
    exit;
}

final class Issue
{
    public $issue_id;
    public $issue_type;
    public $severity;
    public $confidence;
    public $affected_plugin;
    public $affected_page;
    public $translation_key;
    public $technical_details;
    public $probable_cause;
    public $suggested_fix;
    public $impact;
    public $documentation;

    public function __construct(array $data)
    {
        $this->issue_id = sanitize_key($data['issue_id'] ?? uniqid('issue_', false));
        $this->issue_type = sanitize_key($data['issue_type'] ?? 'unknown');
        $this->severity = self::normalize_severity($data['severity'] ?? 'low');
        $this->confidence = max(0, min(100, (int) ($data['confidence'] ?? 50)));
        $this->affected_plugin = sanitize_text_field($data['affected_plugin'] ?? 'unknown');
        $this->affected_page = esc_url_raw($data['affected_page'] ?? '');
        $this->translation_key = sanitize_key($data['translation_key'] ?? $this->issue_type . '_detected');
        $this->technical_details = is_array($data['technical_details'] ?? null) ? $data['technical_details'] : array();
        $this->probable_cause = sanitize_textarea_field($data['probable_cause'] ?? __('Review the technical details and recently changed plugins or theme assets.', 'wp-doctor-ai'));
        $this->suggested_fix = sanitize_textarea_field($data['suggested_fix'] ?? __('Test in staging, update affected components, and remove duplicate or failing scripts safely.', 'wp-doctor-ai'));
        $this->impact = sanitize_text_field($data['impact'] ?? __('May affect interactive page behavior.', 'wp-doctor-ai'));
        $this->documentation = array_map('esc_url_raw', $data['documentation'] ?? array());
    }

    public function to_array(): array
    {
        return get_object_vars($this);
    }

    private static function normalize_severity(string $severity): string
    {
        return in_array($severity, array('low', 'medium', 'high', 'critical'), true) ? $severity : 'low';
    }
}
