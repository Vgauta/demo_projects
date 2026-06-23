<?php
defined( 'ABSPATH' ) || exit;

class CWPC_Assets {
	public function __construct() {
		add_action( 'admin_enqueue_scripts', array( $this, 'admin' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'frontend' ) );
	}
	public function admin( $hook ) {
		$screen = get_current_screen();
		if ( $screen && in_array( $screen->id, array( 'cwpc_configurator', 'product' ), true ) ) {
			wp_enqueue_media();
			wp_enqueue_style( 'wp-color-picker' );
			wp_enqueue_style( 'cwpc-admin', CWPC_URL . 'assets/css/admin.css', array( 'wp-color-picker' ), CWPC_VERSION );
			wp_enqueue_script( 'cwpc-admin-builder', CWPC_URL . 'assets/js/admin-builder.js', array( 'jquery', 'wp-color-picker' ), CWPC_VERSION, true );
		}
	}
	public function frontend() {
		wp_register_style( 'cwpc-frontend', CWPC_URL . 'assets/css/frontend.css', array(), CWPC_VERSION );
		wp_register_script( 'cwpc-frontend', CWPC_URL . 'assets/js/frontend-configurator.js', array( 'jquery' ), CWPC_VERSION, true );
		wp_localize_script( 'cwpc-frontend', 'CWPC', array(
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce' => wp_create_nonce( 'cwpc_frontend' ),
			'i18n' => array( 'complete' => __( 'Please complete required configuration fields.', 'custom-wc-product-configurator' ) ),
		) );
	}
}
