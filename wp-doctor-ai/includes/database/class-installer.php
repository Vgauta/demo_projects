<?php
/**
 * Database installer.
 *
 * @package WPDoctorAI
 */

namespace WPDoctorAI\Database;

if (! defined('ABSPATH')) {
    exit;
}

final class Installer
{
    public static function install(): void
    {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $charset = $wpdb->get_charset_collate();
        $scans = $wpdb->prefix . 'wp_doctor_ai_scans';
        $issues = $wpdb->prefix . 'wp_doctor_ai_issues';
        $credits = $wpdb->prefix . 'wp_doctor_ai_credit_logs';

        dbDelta("CREATE TABLE {$scans} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            status VARCHAR(30) NOT NULL DEFAULT 'completed',
            source VARCHAR(60) NOT NULL DEFAULT 'manual',
            page_url TEXT NULL,
            raw_payload LONGTEXT NULL,
            issue_count INT UNSIGNED NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY status (status),
            KEY created_at (created_at)
        ) {$charset};");

        dbDelta("CREATE TABLE {$issues} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            scan_id BIGINT UNSIGNED NOT NULL,
            issue_uid VARCHAR(120) NOT NULL,
            issue_type VARCHAR(80) NOT NULL,
            severity VARCHAR(20) NOT NULL,
            confidence TINYINT UNSIGNED NOT NULL DEFAULT 0,
            affected_plugin VARCHAR(190) NULL,
            affected_page TEXT NULL,
            translation_key VARCHAR(120) NOT NULL,
            technical_details LONGTEXT NULL,
            probable_cause TEXT NULL,
            suggested_fix TEXT NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY scan_id (scan_id),
            KEY issue_type (issue_type),
            KEY severity (severity)
        ) {$charset};");

        dbDelta("CREATE TABLE {$credits} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
            amount INT NOT NULL,
            balance_after INT NOT NULL,
            action VARCHAR(80) NOT NULL,
            reference VARCHAR(190) NULL,
            notes TEXT NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY action (action),
            KEY created_at (created_at)
        ) {$charset};");

        add_option('wp_doctor_ai_free_scans_remaining', 3);
        add_option('wp_doctor_ai_credit_balance', 0);
        add_option('wp_doctor_ai_settings', array(
            'ai_enabled' => false,
            'ai_provider' => 'none',
            'google_ai_studio_api_key' => '',
            'gemini_model' => 'gemini-1.5-flash',
            'language' => 'en',
            'telemetry' => false,
            'safe_mode' => false,
        ));
        update_option('wp_doctor_ai_db_version', WP_DOCTOR_AI_VERSION);
    }
}
