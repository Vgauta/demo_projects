<?php
defined( 'ABSPATH' ) || exit;
class CWPC_Cart {
	public function __construct() { add_filter( 'woocommerce_add_cart_item_data', array( $this, 'add' ), 10, 3 ); add_filter( 'woocommerce_get_item_data', array( $this, 'display' ), 10, 2 ); add_action( 'woocommerce_before_calculate_totals', array( $this, 'price' ) ); }
	public function add( $cart_item_data, $product_id, $variation_id ) {
		if ( empty( $_POST['cwpc_configuration'] ) ) { return $cart_item_data; }
		$config = json_decode( wp_unslash( $_POST['cwpc_configuration'] ), true ); if ( ! is_array( $config ) ) { return $cart_item_data; }
		$cart_item_data['cwpc_configuration'] = $this->sanitize_config( $config );
		$product = wc_get_product( $variation_id ?: $product_id );
		$cart_item_data['cwpc_base_price'] = $product ? (float) $product->get_price( 'edit' ) : 0;
		$cart_item_data['cwpc_extra_price'] = (float) ( $cart_item_data['cwpc_configuration']['priceAdjustment'] ?? 0 );
		$cart_item_data['cwpc_price_adjustment'] = $cart_item_data['cwpc_extra_price'];
		$cart_item_data['cwpc_preview_image'] = esc_url_raw( wp_unslash( $_POST['cwpc_preview_image'] ?? '' ) );
		$cart_item_data['cwpc_uploads'] = $this->handle_uploads();
		$cart_item_data['unique_key'] = md5( wp_json_encode( $cart_item_data['cwpc_configuration'], CWPC_JSON_FLAGS ) . microtime() );
		return $cart_item_data;
	}
	private function handle_uploads() {
		$uploads = array();
		foreach ( $_FILES as $field => $file ) {
			if ( 0 !== strpos( $field, 'cwpc_upload_' ) || empty( $file['name'] ) || ! empty( $file['error'] ) ) { continue; }
			$allowed = array( 'jpg|jpeg|jpe' => 'image/jpeg', 'png' => 'image/png', 'gif' => 'image/gif', 'webp' => 'image/webp' );
			$check = wp_check_filetype_and_ext( $file['tmp_name'], $file['name'], $allowed );
			if ( empty( $check['type'] ) || $file['size'] > 5 * MB_IN_BYTES ) { continue; }
			require_once ABSPATH . 'wp-admin/includes/file.php';
			$moved = wp_handle_upload( $file, array( 'test_form' => false, 'mimes' => $allowed ) );
			if ( empty( $moved['error'] ) ) { $uploads[ sanitize_key( substr( $field, 12 ) ) ] = esc_url_raw( $moved['url'] ); }
		}
		return $uploads;
	}

	private function sanitize_config( $config ) { return map_deep( $config, function( $value ) { return is_scalar( $value ) ? CWPC_Plugin::sanitize_utf8_text( (string) $value ) : $value; } ); }
	public function display( $item_data, $cart_item ) {
		if ( empty( $cart_item['cwpc_configuration']['summary'] ) ) { return $item_data; }
		foreach ( (array) $cart_item['cwpc_configuration']['summary'] as $row ) { $item_data[] = array( 'name' => CWPC_Plugin::sanitize_utf8_text( $row['label'] ?? 'Option' ), 'value' => CWPC_Plugin::sanitize_utf8_text( $row['value'] ?? '' ) ); }
		foreach ( (array) ( $cart_item['cwpc_uploads'] ?? array() ) as $key => $url ) { $item_data[] = array( 'name' => sprintf( __( 'Uploaded %s', 'custom-wc-product-configurator' ), $key ), 'value' => '<a href="' . esc_url( $url ) . '" target="_blank">View file</a>' ); }
		if ( ! empty( $cart_item['cwpc_preview_image'] ) ) { $item_data[] = array( 'name' => __( 'Preview', 'custom-wc-product-configurator' ), 'value' => '<a href="' . esc_url( $cart_item['cwpc_preview_image'] ) . '" target="_blank">View preview</a>' ); }
		return $item_data;
	}
	public function price( $cart ) { if ( is_admin() && ! defined( 'DOING_AJAX' ) ) { return; } foreach ( $cart->get_cart() as $item ) { if ( isset( $item['cwpc_extra_price'] ) ) { $base = isset( $item['cwpc_base_price'] ) ? (float) $item['cwpc_base_price'] : (float) $item['data']->get_price( 'edit' ); $item['data']->set_price( $base + (float) $item['cwpc_extra_price'] ); } } }
}
