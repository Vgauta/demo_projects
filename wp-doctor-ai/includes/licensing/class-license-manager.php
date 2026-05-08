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
        $settings = get_option('wp_doctor_ai_license', array());
        return sanitize_key($settings['plan'] ?? 'free');
    }

    public function feature_enabled(string $feature): bool
    {
        return (bool) apply_filters('wp_doctor_ai_feature_enabled', true, sanitize_key($feature), $this->plan());
    }
}
