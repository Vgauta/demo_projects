<?php
/**
 * Plugin Name: WP Doctor AI
 * Plugin URI: https://wpdoctorai.com/wp-doctor-ai/
 * Description: WordPress diagnostics and issue explanations for console errors, AJAX failures, jQuery conflicts, duplicate scripts, and Elementor crashes.
 * Version: 1.0.0
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Author: WP Doctor AI
 * Author URI: https://wpdoctorai.com/
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wp-doctor-ai
 * Domain Path: /languages
 *
 * @package WPDoctorAI
 */

if (! defined('ABSPATH')) {
    exit;
}

define('WP_DOCTOR_AI_VERSION', '1.0.0');
define('WP_DOCTOR_AI_FILE', __FILE__);
define('WP_DOCTOR_AI_PATH', plugin_dir_path(__FILE__));
define('WP_DOCTOR_AI_URL', plugin_dir_url(__FILE__));
define('WP_DOCTOR_AI_REST_NAMESPACE', 'wp-doctor-ai/v1');

require_once WP_DOCTOR_AI_PATH . 'includes/helpers/autoload.php';
require_once WP_DOCTOR_AI_PATH . 'admin/class-admin.php';

register_activation_hook(__FILE__, array('WPDoctorAI\\Plugin', 'activate'));
register_deactivation_hook(__FILE__, array('WPDoctorAI\\Plugin', 'deactivate'));

add_action('plugins_loaded', static function () {
    load_plugin_textdomain('wp-doctor-ai', false, dirname(plugin_basename(__FILE__)) . '/languages');
    WPDoctorAI\Plugin::instance()->boot();
});
