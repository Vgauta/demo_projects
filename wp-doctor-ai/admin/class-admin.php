<?php
/**
 * Admin shell.
 *
 * @package WPDoctorAI
 */

namespace WPDoctorAI\Admin;

use WPDoctorAI\ServiceContainer;

if (! defined('ABSPATH')) {
    exit;
}

final class Admin
{
    private $container;

    public function __construct(ServiceContainer $container)
    {
        $this->container = $container;
    }

    public function hooks(): void
    {
        add_action('admin_menu', array($this, 'menu'));
        add_action('admin_enqueue_scripts', array($this, 'assets'));
        add_action('admin_post_wp_doctor_ai_export', array($this, 'export'));
        add_action('admin_post_wp_doctor_ai_save_settings', array($this, 'save_settings'));
    }

    public function menu(): void
    {
        add_menu_page(__('WP Doctor AI', 'wp-doctor-ai'), __('WP Doctor AI', 'wp-doctor-ai'), 'manage_options', 'wp-doctor-ai', array($this, 'render'), 'dashicons-stethoscope', 58);
    }

    public function assets(string $hook): void
    {
        if ('toplevel_page_wp-doctor-ai' !== $hook) {
            return;
        }
        $settings = get_option('wp_doctor_ai_settings', array());
        $settings = is_array($settings) ? $settings : array();

        wp_enqueue_style('wp-doctor-ai-admin', WP_DOCTOR_AI_URL . 'assets/css/admin.css', array(), WP_DOCTOR_AI_VERSION);
        wp_enqueue_script('wp-doctor-ai-admin', WP_DOCTOR_AI_URL . 'assets/js/admin.js', array(), WP_DOCTOR_AI_VERSION, true);
        wp_localize_script('wp-doctor-ai-admin', 'WPDoctorAIAdmin', array(
            'restUrl' => esc_url_raw(rest_url(WP_DOCTOR_AI_REST_NAMESPACE)),
            'nonce' => wp_create_nonce('wp_rest'),
            'exportUrl' => esc_url_raw(admin_url('admin-post.php?action=wp_doctor_ai_export&_wpnonce=' . wp_create_nonce('wp_doctor_ai_export'))),
            'strings' => array(
                'scanning' => esc_html__('Scanning…', 'wp-doctor-ai'),
                'runScan' => esc_html__('Run Browser Scan', 'wp-doctor-ai'),
                'oneClickSolution' => esc_html__('One-click solution', 'wp-doctor-ai'),
                'premiumRequired' => esc_html__('Premium required', 'wp-doctor-ai'),
                'upgradeCheckout' => esc_html__('Upgrade checkout', 'wp-doctor-ai'),
                'apiKeyRequired' => esc_html__('API key required', 'wp-doctor-ai'),
                'processing' => esc_html__('Processing…', 'wp-doctor-ai'),
                'explaining' => esc_html__('Generating explanation…', 'wp-doctor-ai'),
                'solving' => esc_html__('Working on one-click solution…', 'wp-doctor-ai'),
                'reloadToVerify' => esc_html__('Reload this page or run another scan to verify the fix.', 'wp-doctor-ai'),
                'autoRescan' => esc_html__('Reloading now so WP Doctor AI can rescan and remove the fixed issue from the report.', 'wp-doctor-ai'),
            ),
            'defaultLanguage' => sanitize_text_field($settings['language'] ?? 'en'),
            'isPremium' => $this->container->licensing()->is_premium(),
            'aiReady' => $this->container->ai()->has_api_key(),
        ));
    }

    public function render(): void
    {
        $data = array(
            'credits' => $this->container->credits()->balance(),
            'free_scans' => $this->container->credits()->free_scans_remaining(),
            'languages' => $this->container->translations()->languages(),
            'scans' => $this->container->repository()->recent_scans(5),
            'issues' => $this->container->repository()->issues(),
            'plan' => $this->container->licensing()->plan(),
            'is_premium' => $this->container->licensing()->is_premium(),
            'checkout_url' => $this->container->licensing()->checkout_url(),
            'settings' => get_option('wp_doctor_ai_settings', array()),
            'active_fixes' => $this->container->fixes()->active_fixes(),
            'debug_logs' => get_option('wp_doctor_ai_debug_logs', array()),
            'ai_ready' => $this->container->ai()->has_api_key(),
        );
        include WP_DOCTOR_AI_PATH . 'templates/admin-dashboard.php';
    }


    public function save_settings(): void
    {
        if (! current_user_can('manage_options') || ! check_admin_referer('wp_doctor_ai_settings')) {
            wp_die(esc_html__('You are not allowed to save WP Doctor AI settings.', 'wp-doctor-ai'));
        }

        $settings = get_option('wp_doctor_ai_settings', array());
        $settings = is_array($settings) ? $settings : array();
        $posted = wp_unslash($_POST);
        $api_key = isset($posted['google_ai_studio_api_key']) ? sanitize_text_field((string) $posted['google_ai_studio_api_key']) : '';
        $language = isset($posted['language']) ? sanitize_text_field((string) $posted['language']) : 'en';
        $debug_mode = ! empty($posted['debug_mode']);

        $settings['ai_enabled'] = '' !== $api_key;
        $settings['ai_provider'] = '' !== $api_key ? 'gemini' : 'none';
        $settings['google_ai_studio_api_key'] = $api_key;
        $settings['gemini_model'] = 'gemini-2.0-flash';
        $settings['language'] = $language ?: 'en';
        $settings['debug_mode'] = $debug_mode;

        update_option('wp_doctor_ai_settings', $settings);

        wp_safe_redirect(add_query_arg('wpda_saved', '1', admin_url('admin.php?page=wp-doctor-ai')));
        exit;
    }

    public function export(): void
    {
        if (! current_user_can('manage_options') || ! check_admin_referer('wp_doctor_ai_export')) {
            wp_die(esc_html__('You are not allowed to export this report.', 'wp-doctor-ai'));
        }
        $format = isset($_GET['format']) ? sanitize_key(wp_unslash($_GET['format'])) : 'json';
        $issues = $this->container->repository()->issues();
        if ('txt' === $format) {
            header('Content-Type: text/plain; charset=utf-8');
            header('Content-Disposition: attachment; filename="wp-doctor-ai-report.txt"');
            foreach ($issues as $issue) {
                echo esc_html__('Issue Type:', 'wp-doctor-ai') . ' ' . sanitize_text_field($issue['issue_type']) . "\n";
                echo esc_html__('Severity:', 'wp-doctor-ai') . ' ' . sanitize_text_field($issue['severity']) . "\n";
                echo esc_html__('Affected Plugin:', 'wp-doctor-ai') . ' ' . sanitize_text_field($issue['affected_plugin']) . "\n";
                echo esc_html__('Probable Cause:', 'wp-doctor-ai') . ' ' . sanitize_text_field($issue['probable_cause']) . "\n";
                echo esc_html__('Suggested Fix:', 'wp-doctor-ai') . ' ' . sanitize_text_field($issue['suggested_fix']) . "\n\n";
            }
            exit;
        }
        if ('pdf' === $format) {
            header('Content-Type: text/html; charset=utf-8');
            header('Content-Disposition: attachment; filename="wp-doctor-ai-report.html"');
            echo '<h1>' . esc_html__('WP Doctor AI Report', 'wp-doctor-ai') . '</h1><p>' . esc_html__('Print this browser-friendly report from your browser if a PDF copy is needed.', 'wp-doctor-ai') . '</p><pre>' . esc_html(wp_json_encode($issues, JSON_PRETTY_PRINT)) . '</pre>';
            exit;
        }
        wp_send_json(array('issues' => $issues));
    }
}
