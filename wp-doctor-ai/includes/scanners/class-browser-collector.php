<?php
/**
 * Lightweight browser-side collector bootstrap.
 *
 * @package WPDoctorAI
 */

namespace WPDoctorAI\Scanners;

use WPDoctorAI\ServiceContainer;

if (! defined('ABSPATH')) {
    exit;
}

final class BrowserCollector
{
    private $container;

    public function __construct(ServiceContainer $container)
    {
        $this->container = $container;
    }

    public function hooks(): void
    {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_collector'), 99);
        add_action('admin_enqueue_scripts', array($this, 'enqueue_collector'), 99);
    }

    public function enqueue_collector(): void
    {
        if (! current_user_can('manage_options') || is_customize_preview()) {
            return;
        }

        wp_enqueue_script('wp-doctor-ai-collector', WP_DOCTOR_AI_URL . 'assets/js/collector.js', array(), WP_DOCTOR_AI_VERSION, false);
        wp_localize_script('wp-doctor-ai-collector', 'WPDoctorAICollector', array(
            'restUrl' => esc_url_raw(rest_url(WP_DOCTOR_AI_REST_NAMESPACE . '/scan')),
            'nonce' => wp_create_nonce('wp_rest'),
            'pageUrl' => esc_url_raw(home_url(add_query_arg(array(), $GLOBALS['wp']->request ?? ''))),
            'enabled' => true,
        ));
    }
}
