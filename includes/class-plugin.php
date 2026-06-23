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
		if ( ! get_option( 'cwpc_default_dining_schema' ) ) { update_option( 'cwpc_default_dining_schema', wp_json_encode( self::default_dining_schema(), CWPC_JSON_FLAGS ) ); }
		if ( ! get_page_by_title( 'Dining Table Set Configurator', OBJECT, 'cwpc_configurator' ) ) {
			$post_id = wp_insert_post( array( 'post_title' => 'Dining Table Set Configurator', 'post_type' => 'cwpc_configurator', 'post_status' => 'publish' ) );
			if ( $post_id && ! is_wp_error( $post_id ) ) { update_post_meta( $post_id, '_cwpc_schema', wp_json_encode( self::default_schema(), CWPC_JSON_FLAGS | JSON_PRETTY_PRINT ) ); }
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



	public static function sanitize_utf8_text( $value ) {
		$text = (string) $value;
		if ( preg_match( '/u05[0-9a-f]{2}/i', $text ) ) {
			$text = preg_replace_callback( '/(?<![0-9a-fA-F])([0-9a-fA-F]{3})(?=u05[0-9a-fA-F]{2}|$)/', function( $match ) { return html_entity_decode( '&#x0' . $match[1] . ';', ENT_NOQUOTES, 'UTF-8' ); }, $text );
			$text = preg_replace_callback( '/(?<!\\\\)u([0-9a-fA-F]{4})/', function( $match ) { return html_entity_decode( '&#x' . $match[1] . ';', ENT_NOQUOTES, 'UTF-8' ); }, $text );
		}
		return sanitize_text_field( $text );
	}

	public static function default_dining_schema() {
		return array(
			'button_label' => 'Customize & Add to Cart',
			'included_chairs' => 6,
			'sync_colors' => 'yes',
			'color_mode_same_enabled' => 'yes',
			'color_mode_mixed_enabled' => 'yes',
			'mixed_colors_placeholder' => 'For example 2 gray, 1 mustard, 3 light blue',
			'base_image' => '',
			'base_image_id' => 0,
			'table_base' => '',
			'table_base_id' => 0,
			'table_mask' => '',
			'table_mask_id' => 0,
			'table_x' => 0,
			'table_y' => 0,
			'table_width' => 900,
			'table_height' => 650,
			'chair_mask' => '',
			'chair_mask_id' => 0,
			'table_colors' => array(
				array( 'name' => 'White', 'color' => '#ffffff', 'price' => 0, 'preview' => '', 'preview_id' => 0 ),
				array( 'name' => 'Dark Grey', 'color' => '#4a4a4a', 'price' => 0, 'preview' => '', 'preview_id' => 0 ),
				array( 'name' => 'Champagne', 'color' => '#d6b98c', 'price' => 0, 'preview' => '', 'preview_id' => 0 ),
			),
			'chair_designs' => array(
				array( 'name' => 'Luna', 'thumbnail' => '', 'thumbnail_id' => 0, 'base' => '', 'base_id' => 0, 'mask' => '', 'mask_id' => 0, 'overlay' => '', 'overlay_id' => 0, 'x' => 0, 'y' => 0, 'width' => 900, 'height' => 650, 'preview' => '', 'preview_id' => 0, 'price' => 0, 'chair_colors' => array( array( 'name' => 'Light Grey', 'color' => '#c9c9c9', 'price' => 0 ), array( 'name' => 'Green', 'color' => '#4f7f52', 'price' => 0 ), array( 'name' => 'Mustard', 'color' => '#d6a100', 'price' => 0 ), array( 'name' => 'Black', 'color' => '#000000', 'price' => 0 ) ), 'colors' => array( 'Light Grey', 'Green', 'Mustard', 'Black' ) ),
				array( 'name' => 'Tuna', 'thumbnail' => '', 'thumbnail_id' => 0, 'base' => '', 'base_id' => 0, 'mask' => '', 'mask_id' => 0, 'overlay' => '', 'overlay_id' => 0, 'x' => 0, 'y' => 0, 'width' => 900, 'height' => 650, 'preview' => '', 'preview_id' => 0, 'price' => 0, 'chair_colors' => array( array( 'name' => 'Light Grey', 'color' => '#c9c9c9', 'price' => 0 ), array( 'name' => 'Green', 'color' => '#4f7f52', 'price' => 0 ), array( 'name' => 'Mustard', 'color' => '#d6a100', 'price' => 0 ), array( 'name' => 'Black', 'color' => '#000000', 'price' => 0 ) ), 'colors' => array( 'Light Grey', 'Green', 'Mustard', 'Black' ) ),
				array( 'name' => 'Sano', 'thumbnail' => '', 'thumbnail_id' => 0, 'base' => '', 'base_id' => 0, 'mask' => '', 'mask_id' => 0, 'overlay' => '', 'overlay_id' => 0, 'x' => 0, 'y' => 0, 'width' => 900, 'height' => 650, 'preview' => '', 'preview_id' => 0, 'price' => 0, 'chair_colors' => array( array( 'name' => 'Light Grey', 'color' => '#c9c9c9', 'price' => 0 ), array( 'name' => 'Green', 'color' => '#4f7f52', 'price' => 0 ), array( 'name' => 'Mustard', 'color' => '#d6a100', 'price' => 0 ), array( 'name' => 'Black', 'color' => '#000000', 'price' => 0 ) ), 'colors' => array( 'Light Grey', 'Green', 'Mustard', 'Black' ) ),
				array( 'name' => 'X Design', 'thumbnail' => '', 'thumbnail_id' => 0, 'base' => '', 'base_id' => 0, 'mask' => '', 'mask_id' => 0, 'overlay' => '', 'overlay_id' => 0, 'x' => 0, 'y' => 0, 'width' => 900, 'height' => 650, 'preview' => '', 'preview_id' => 0, 'price' => 0, 'chair_colors' => array( array( 'name' => 'Light Grey', 'color' => '#c9c9c9', 'price' => 0 ), array( 'name' => 'Green', 'color' => '#4f7f52', 'price' => 0 ), array( 'name' => 'Mustard', 'color' => '#d6a100', 'price' => 0 ), array( 'name' => 'Black', 'color' => '#000000', 'price' => 0 ) ), 'colors' => array( 'Light Grey', 'Green', 'Mustard', 'Black' ) ),
			),
			'extra_chairs' => array(
				array( 'label' => '6 chairs included in price', 'quantity' => 0, 'price' => 0 ),
				array( 'label' => 'Add 2 extra chairs', 'quantity' => 2, 'price' => 0 ),
				array( 'label' => 'Add 4 extra chairs', 'quantity' => 4, 'price' => 0 ),
				array( 'label' => 'Add 6 extra chairs', 'quantity' => 6, 'price' => 0 ),
			),
			'chair_colors' => array(
				array( 'name' => 'Mixed colors', 'color' => '#d9a441', 'price' => 0, 'preview' => '', 'preview_id' => 0 ),
				array( 'name' => 'Dark Grey', 'color' => '#4a4a4a', 'price' => 0, 'preview' => '', 'preview_id' => 0 ),
				array( 'name' => 'Black', 'color' => '#000000', 'price' => 0, 'preview' => '', 'preview_id' => 0 ),
				array( 'name' => 'Mustard', 'color' => '#d6a100', 'price' => 0, 'preview' => '', 'preview_id' => 0 ),
				array( 'name' => 'Green', 'color' => '#4f7f52', 'price' => 0, 'preview' => '', 'preview_id' => 0 ),
				array( 'name' => 'Light Grey', 'color' => '#c9c9c9', 'price' => 0, 'preview' => '', 'preview_id' => 0 ),
			),
			'chair_color_images' => array(),
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
