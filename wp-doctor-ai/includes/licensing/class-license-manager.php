<?php
/**
 * Licensing-ready feature flags.
 *
 * @package WPDoctorAI
 */

namespace WPDoctorAI\Licensing;

if (! defined('ABSPATH')) {
    exit;
}

final class LicenseManager
{
    public function plan(): string
    {
        if (defined('WP_DOCTOR_AI_PRO_ACTIVE') && WP_DOCTOR_AI_PRO_ACTIVE) {
            return 'pro';
        }

        if (defined('WP_DOCTOR_AI_PREMIUM') && WP_DOCTOR_AI_PREMIUM) {
            return 'premium';
        }

        $settings = get_option('wp_doctor_ai_license', array());
        $settings = is_array($settings) ? $settings : array();
        $plan = sanitize_key($settings['plan'] ?? 'free');

        return sanitize_key((string) apply_filters('wp_doctor_ai_license_plan', $plan));
    }

    public function is_premium(): bool
    {
        return in_array($this->plan(), array('premium', 'pro', 'agency'), true);
    }

    public function feature_enabled(string $feature): bool
    {
        $feature = sanitize_key($feature);
        $premium_only = in_array($feature, array('one_click_solution'), true);
        $enabled = $premium_only ? $this->is_premium() : true;

        return (bool) apply_filters('wp_doctor_ai_feature_enabled', $enabled, $feature, $this->plan());
    }

    public function checkout_url(): string
    {
        $settings = get_option('wp_doctor_ai_license', array());
        $settings = is_array($settings) ? $settings : array();
        $url = isset($settings['checkout_url']) && $settings['checkout_url'] ? esc_url_raw($settings['checkout_url']) : 'https://starlineinfotech.net/';

        return (string) apply_filters('wp_doctor_ai_checkout_url', $url);
    }
}
