<?php
/**
 * Plugin Name: Custom WooCommerce Product Configurator
 * Description: Self-hosted WooCommerce product configurator with layered previews, custom text, uploads, conditional options, dynamic pricing, cart/order integration, and shortcode rendering.
 * Version: 1.0.12
 * Author: OpenAI
 * Text Domain: custom-wc-product-configurator
 * Requires at least: 6.0
 * Requires PHP: 7.4
 */

defined( 'ABSPATH' ) || exit;

define( 'CWPC_VERSION', '1.0.12' );
define( 'CWPC_JSON_FLAGS', JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
define( 'CWPC_FILE', __FILE__ );
define( 'CWPC_PATH', plugin_dir_path( __FILE__ ) );
define( 'CWPC_URL', plugin_dir_url( __FILE__ ) );

require_once CWPC_PATH . 'includes/class-plugin.php';

register_activation_hook( __FILE__, array( 'CWPC_Plugin', 'activate' ) );
add_action( 'plugins_loaded', array( 'CWPC_Plugin', 'instance' ) );
