<?php
/**
 * Uninstall cleanup for WP Doctor AI.
 *
 * The default uninstall path removes runtime/temporary data and scheduled jobs while
 * preserving diagnostic history and credit records. Site owners can opt in to full
 * data removal by setting the `wp_doctor_ai_delete_data_on_uninstall` option to true
 * before deleting the plugin.
 *
 * @package WPDoctorAI
 */

if (! defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

global $wpdb;

wp_clear_scheduled_hook('wp_doctor_ai_subscription_refill');

$transient_names = array(
    'wp_doctor_ai_last_scan_summary',
    'wp_doctor_ai_remote_features',
    'wp_doctor_ai_asset_audit',
);

foreach ($transient_names as $transient_name) {
    delete_transient($transient_name);
    delete_site_transient($transient_name);
}

$delete_all_data = (bool) get_option('wp_doctor_ai_delete_data_on_uninstall', false);

// Always remove ephemeral options that do not represent user diagnostic history.
delete_option('wp_doctor_ai_admin_notice_dismissed');
delete_option('wp_doctor_ai_db_version');

if ($delete_all_data) {
    $options = array(
        'wp_doctor_ai_free_scans_remaining',
        'wp_doctor_ai_credit_balance',
        'wp_doctor_ai_settings',
        'wp_doctor_ai_license',
        'wp_doctor_ai_delete_data_on_uninstall',
    );

    foreach ($options as $option) {
        delete_option($option);
    }

    $tables = array(
        $wpdb->prefix . 'wp_doctor_ai_scans',
        $wpdb->prefix . 'wp_doctor_ai_issues',
        $wpdb->prefix . 'wp_doctor_ai_credit_logs',
    );

    foreach ($tables as $table) {
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Table names are built from the trusted WordPress prefix and static suffixes.
        $wpdb->query("DROP TABLE IF EXISTS {$table}");
    }
}
