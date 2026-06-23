<?php
defined( 'ABSPATH' ) || exit;
class CWPC_Order {
	public function __construct() { add_action( 'woocommerce_checkout_create_order_line_item', array( $this, 'meta' ), 10, 4 ); }
	public function meta( $item, $cart_item_key, $values, $order ) {
		if ( empty( $values['cwpc_configuration'] ) ) { return; }
		$item->add_meta_data( '_cwpc_configuration_json', wp_json_encode( $values['cwpc_configuration'], CWPC_JSON_FLAGS ), true );
		foreach ( (array) ( $values['cwpc_configuration']['summary'] ?? array() ) as $row ) { $item->add_meta_data( CWPC_Plugin::sanitize_utf8_text( $row['label'] ?? 'Configurator option' ), CWPC_Plugin::sanitize_utf8_text( $row['value'] ?? '' ), false ); }
		foreach ( (array) ( $values['cwpc_uploads'] ?? array() ) as $key => $url ) { $item->add_meta_data( sprintf( __( 'Configurator upload %s', 'custom-wc-product-configurator' ), $key ), esc_url_raw( $url ), false ); }
		if ( ! empty( $values['cwpc_preview_image'] ) ) { $item->add_meta_data( __( 'Configurator preview', 'custom-wc-product-configurator' ), esc_url_raw( $values['cwpc_preview_image'] ), true ); }
	}
}
