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
            ),
            'defaultLanguage' => sanitize_key($settings['language'] ?? 'en'),
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
        );
        include WP_DOCTOR_AI_PATH . 'templates/admin-dashboard.php';
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
