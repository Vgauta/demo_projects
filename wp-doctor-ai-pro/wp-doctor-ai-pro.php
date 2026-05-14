<?php
/**
 * Plugin Name: WP Doctor AI Pro
 * Plugin URI: https://starlineinfotech.net/
 * Description: Pro add-on for WP Doctor AI that unlocks unlimited scans and premium Google AI Studio/Gemini one-click solution plans.
 * Version: 1.0.0
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Author: Starline Infotech
 * Author URI: https://starlineinfotech.net/
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wp-doctor-ai
 *
 * @package WPDoctorAIPro
 */

if (! defined('ABSPATH')) {
    exit;
}

define('WP_DOCTOR_AI_PRO_VERSION', '1.0.0');
define('WP_DOCTOR_AI_PRO_FILE', __FILE__);
define('WP_DOCTOR_AI_PRO_ACTIVE', true);

register_activation_hook(__FILE__, 'wp_doctor_ai_pro_activate');
register_deactivation_hook(__FILE__, 'wp_doctor_ai_pro_deactivate');
add_action('plugins_loaded', 'wp_doctor_ai_pro_boot', 0);

/**
 * Store a pro plan marker so the base plugin unlocks Pro even if filters are unavailable.
 */
function wp_doctor_ai_pro_activate(): void
{
    $license = get_option('wp_doctor_ai_license', array());
    $license = is_array($license) ? $license : array();
    $license['plan'] = 'pro';
    $license['source'] = 'wp_doctor_ai_pro';
    update_option('wp_doctor_ai_license', $license);
}

/**
 * Remove only the Pro marker created by this add-on on deactivation.
 */
function wp_doctor_ai_pro_deactivate(): void
{
    $license = get_option('wp_doctor_ai_license', array());
    $license = is_array($license) ? $license : array();

    if ('wp_doctor_ai_pro' === ($license['source'] ?? '')) {
        $license['plan'] = 'free';
        unset($license['source']);
        update_option('wp_doctor_ai_license', $license);
    }
}

/**
 * Enable pro capabilities before the base WP Doctor AI plugin boots.
 */
function wp_doctor_ai_pro_boot(): void
{
    if (! defined('WP_DOCTOR_AI_VERSION')) {
        add_action('admin_notices', 'wp_doctor_ai_pro_missing_base_notice');
        return;
    }

    wp_doctor_ai_pro_activate();

    add_filter('wp_doctor_ai_license_plan', 'wp_doctor_ai_pro_license_plan');
    add_filter('wp_doctor_ai_feature_enabled', 'wp_doctor_ai_pro_feature_enabled', 10, 3);
    add_filter('wp_doctor_ai_checkout_url', 'wp_doctor_ai_pro_checkout_url');
}

/**
 * Force the base plugin license layer into the pro plan.
 *
 * @param string $plan Current detected plan.
 * @return string
 */
function wp_doctor_ai_pro_license_plan(string $plan): string
{
    return 'pro';
}

/**
 * Unlock premium feature flags provided by the base plugin.
 *
 * @param bool   $enabled Current feature state.
 * @param string $feature Feature key.
 * @param string $plan    Current plan.
 * @return bool
 */
function wp_doctor_ai_pro_feature_enabled(bool $enabled, string $feature, string $plan): bool
{
    $premium_features = array(
        'one_click_solution',
        'unlimited_scans',
        'ai_solution_plan',
        'custom_ai_language',
    );

    return in_array($feature, $premium_features, true) ? true : $enabled;
}

/**
 * Keep upgrade/account links pointed at the seller website for now.
 *
 * @param string $url Current checkout URL.
 * @return string
 */
function wp_doctor_ai_pro_checkout_url(string $url): string
{
    return 'https://starlineinfotech.net/';
}

/**
 * Explain that Pro requires the base plugin when it is activated alone.
 */
function wp_doctor_ai_pro_missing_base_notice(): void
{
    if (! current_user_can('activate_plugins')) {
        return;
    }
    ?>
    <div class="notice notice-error">
        <p><?php esc_html_e('WP Doctor AI Pro requires the free WP Doctor AI plugin to be installed and active.', 'wp-doctor-ai'); ?></p>
    </div>
    <?php
}
