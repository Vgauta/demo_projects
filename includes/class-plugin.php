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
		if ( ! get_option( 'cwpc_default_dining_schema' ) ) { update_option( 'cwpc_default_dining_schema', wp_json_encode( self::default_dining_schema() ) ); }
		if ( ! get_page_by_title( 'Dining Table Set Configurator', OBJECT, 'cwpc_configurator' ) ) {
			$post_id = wp_insert_post( array( 'post_title' => 'Dining Table Set Configurator', 'post_type' => 'cwpc_configurator', 'post_status' => 'publish' ) );
			if ( $post_id && ! is_wp_error( $post_id ) ) { update_post_meta( $post_id, '_cwpc_schema', wp_json_encode( self::default_schema(), JSON_PRETTY_PRINT ) ); }
		}
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


	public static function default_dining_schema() {
		return array(
			'table_colors' => array(
				array( 'name' => 'White', 'color' => '#ffffff', 'price' => 0, 'preview' => '' ),
				array( 'name' => 'Dark Grey', 'color' => '#4a4a4a', 'price' => 0, 'preview' => '' ),
				array( 'name' => 'Champagne', 'color' => '#d6b98c', 'price' => 0, 'preview' => '' ),
			),
			'chair_designs' => array(
				array( 'name' => 'Luna', 'thumbnail' => '', 'preview' => '', 'price' => 0, 'colors' => array( 'Mixed colors', 'Dark Grey', 'Black', 'Mustard', 'Green', 'Light Grey' ) ),
				array( 'name' => 'Tuna', 'thumbnail' => '', 'preview' => '', 'price' => 0, 'colors' => array( 'Mixed colors', 'Dark Grey', 'Black', 'Mustard', 'Green', 'Light Grey' ) ),
				array( 'name' => 'Sano', 'thumbnail' => '', 'preview' => '', 'price' => 0, 'colors' => array( 'Mixed colors', 'Dark Grey', 'Black', 'Mustard', 'Green', 'Light Grey' ) ),
				array( 'name' => 'X Design', 'thumbnail' => '', 'preview' => '', 'price' => 0, 'colors' => array( 'Mixed colors', 'Dark Grey', 'Black', 'Mustard', 'Green', 'Light Grey' ) ),
			),
			'extra_chairs' => array(
				array( 'label' => '6 chairs included in price', 'quantity' => 0, 'price' => 0 ),
				array( 'label' => 'Add 2 extra chairs', 'quantity' => 2, 'price' => 0 ),
				array( 'label' => 'Add 4 extra chairs', 'quantity' => 4, 'price' => 0 ),
				array( 'label' => 'Add 6 extra chairs', 'quantity' => 6, 'price' => 0 ),
			),
			'chair_colors' => array(
				array( 'name' => 'Mixed colors', 'color' => '#d9a441', 'price' => 0, 'preview' => '' ),
				array( 'name' => 'Dark Grey', 'color' => '#4a4a4a', 'price' => 0, 'preview' => '' ),
				array( 'name' => 'Black', 'color' => '#000000', 'price' => 0, 'preview' => '' ),
				array( 'name' => 'Mustard', 'color' => '#d6a100', 'price' => 0, 'preview' => '' ),
				array( 'name' => 'Green', 'color' => '#4f7f52', 'price' => 0, 'preview' => '' ),
				array( 'name' => 'Light Grey', 'color' => '#c9c9c9', 'price' => 0, 'preview' => '' ),
			),
			'addons' => array(
				array( 'name' => 'Chair cushion', 'price' => 0, 'enabled' => 'yes' ),
				array( 'name' => 'Waterproof cover', 'price' => 0, 'enabled' => 'yes' ),
			),
		);
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
