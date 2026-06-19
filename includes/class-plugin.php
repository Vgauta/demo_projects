<?php
defined( 'ABSPATH' ) || exit;

final class CWPC_Plugin {
	private static $instance = null;
	public $woocommerce_active = false;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		$this->woocommerce_active = class_exists( 'WooCommerce' );
		$this->includes();
		add_action( 'init', array( 'CWPC_Post_Types', 'register' ) );
		add_action( 'admin_notices', array( $this, 'woocommerce_notice' ) );
		add_action( 'admin_notices', array( $this, 'onboarding_notice' ) );
		new CWPC_Assets();
		new CWPC_Admin();
		new CWPC_REST_API();
		if ( $this->woocommerce_active ) {
			new CWPC_Product_Metabox();
			new CWPC_Frontend();
			new CWPC_Cart();
			new CWPC_Order();
		}
	}

	private function includes() {
		foreach ( array( 'post-types', 'assets', 'admin', 'product-metabox', 'frontend', 'cart', 'order', 'rest-api' ) as $file ) {
			require_once CWPC_PATH . 'includes/class-' . $file . '.php';
		}
	}

	public static function activate() {
		require_once CWPC_PATH . 'includes/class-post-types.php';
		CWPC_Post_Types::register();
		flush_rewrite_rules();
		set_transient( 'cwpc_activation_notice', 1, DAY_IN_SECONDS );
	}

	public function onboarding_notice() {
		if ( ! current_user_can( 'edit_posts' ) || ! get_transient( 'cwpc_activation_notice' ) ) {
			return;
		}
		delete_transient( 'cwpc_activation_notice' );
		$url = admin_url( 'edit.php?post_type=cwpc_configurator' );
		echo '<div class="notice notice-success is-dismissible"><p><strong>Custom WooCommerce Product Configurator:</strong> Go to <a href="' . esc_url( $url ) . '">Configurators</a> to create your first configurator.</p></div>';
	}

	public function woocommerce_notice() {
		if ( current_user_can( 'activate_plugins' ) && ! $this->woocommerce_active ) {
			echo '<div class="notice notice-warning"><p><strong>Custom WooCommerce Product Configurator:</strong> WooCommerce is inactive. Configurator templates remain editable, but product, cart, and order integrations are disabled.</p></div>';
		}
	}

	public static function default_schema() {
		return array(
			'layers' => array(
				array(
					'id' => 'base', 'title' => 'Base Color', 'type' => 'color', 'order' => 10,
					'options' => array(
						array( 'id' => 'white', 'title' => 'White', 'color' => '#ffffff', 'price' => 0, 'image' => '', 'conditions' => array() ),
						array( 'id' => 'black', 'title' => 'Black', 'color' => '#111111', 'price' => 5, 'image' => '', 'conditions' => array() ),
					),
				),
				array(
					'id' => 'text', 'title' => 'Custom Text', 'type' => 'text', 'order' => 20,
					'price' => 8, 'placeholder' => 'Your text', 'conditions' => array(),
				),
				array(
					'id' => 'logo', 'title' => 'Upload Logo', 'type' => 'upload', 'order' => 30,
					'price' => 12, 'conditions' => array(),
				),
			),
		);
	}
}
