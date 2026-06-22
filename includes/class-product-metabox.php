<?php
defined( 'ABSPATH' ) || exit;
class CWPC_Product_Metabox {
	public function __construct() {
		add_filter( 'woocommerce_product_data_tabs', array( $this, 'tab' ) );
		add_action( 'woocommerce_product_data_panels', array( $this, 'panel' ) );
		add_action( 'woocommerce_process_product_meta', array( $this, 'save' ) );
	}
	public function tab( $tabs ) {
		$tabs['cwpc_dining_set'] = array( 'label' => __( 'Dining Set Options', 'custom-wc-product-configurator' ), 'target' => 'cwpc_dining_set_product_data', 'class' => array(), 'priority' => 75 );
		return $tabs;
	}
	public function panel() {
		global $post;
		$product_id = $post ? $post->ID : 0;
		$map = json_decode( get_post_meta( $product_id, '_cwpc_dining_preview_map', true ), true );
		if ( ! is_array( $map ) ) { $map = array(); }
		echo '<div id="cwpc_dining_set_product_data" class="panel woocommerce_options_panel hidden"><div class="options_group">';
		woocommerce_wp_checkbox( array( 'id' => '_cwpc_dining_enabled', 'label' => __( 'Enable Dining Set Configurator', 'custom-wc-product-configurator' ), 'value' => get_post_meta( $product_id, '_cwpc_dining_enabled', true ) ) );
		woocommerce_wp_text_input( array( 'id' => '_cwpc_base_chairs', 'label' => __( 'Base set includes number of chairs', 'custom-wc-product-configurator' ), 'type' => 'number', 'custom_attributes' => array( 'min' => '0', 'step' => '1' ), 'value' => get_post_meta( $product_id, '_cwpc_base_chairs', true ) ?: 6 ) );
		woocommerce_wp_text_input( array( 'id' => '_cwpc_extra_chair_price', 'label' => __( 'Extra chair price', 'custom-wc-product-configurator' ), 'type' => 'number', 'custom_attributes' => array( 'min' => '0', 'step' => '0.01' ), 'value' => get_post_meta( $product_id, '_cwpc_extra_chair_price', true ) ?: 0 ) );
		woocommerce_wp_checkbox( array( 'id' => '_cwpc_sync_chair_color', 'label' => __( 'Sync chair color with table color by default', 'custom-wc-product-configurator' ), 'value' => get_post_meta( $product_id, '_cwpc_sync_chair_color', true ) ?: 'yes' ) );
		woocommerce_wp_checkbox( array( 'id' => '_cwpc_hide_chair_color_until_different', 'label' => __( 'Hide Chair Color field unless “Different chair color” is selected', 'custom-wc-product-configurator' ), 'value' => get_post_meta( $product_id, '_cwpc_hide_chair_color_until_different', true ) ) );
		echo '</div><div class="options_group cwpc-dining-map"><h4>' . esc_html__( 'Product preview image mapping', 'custom-wc-product-configurator' ) . '</h4><p class="description">Add one row for each table/chair color preview image. Colors should match your WooCommerce attribute option names.</p><div id="cwpc-dining-map-list" data-map="' . esc_attr( wp_json_encode( $map ) ) . '"></div><p><button type="button" class="button" id="cwpc-add-dining-map">' . esc_html__( 'Add Preview Mapping', 'custom-wc-product-configurator' ) . '</button></p><input type="hidden" id="_cwpc_dining_preview_map" name="_cwpc_dining_preview_map" value="' . esc_attr( wp_json_encode( $map ) ) . '"></div></div>';
	}
	public function save( $product_id ) {
		update_post_meta( $product_id, '_cwpc_dining_enabled', isset( $_POST['_cwpc_dining_enabled'] ) ? 'yes' : 'no' );
		update_post_meta( $product_id, '_cwpc_base_chairs', absint( $_POST['_cwpc_base_chairs'] ?? 6 ) );
		update_post_meta( $product_id, '_cwpc_extra_chair_price', wc_format_decimal( wp_unslash( $_POST['_cwpc_extra_chair_price'] ?? 0 ) ) );
		update_post_meta( $product_id, '_cwpc_sync_chair_color', isset( $_POST['_cwpc_sync_chair_color'] ) ? 'yes' : 'no' );
		update_post_meta( $product_id, '_cwpc_hide_chair_color_until_different', isset( $_POST['_cwpc_hide_chair_color_until_different'] ) ? 'yes' : 'no' );
		$rows = json_decode( wp_unslash( $_POST['_cwpc_dining_preview_map'] ?? '[]' ), true );
		$clean = array();
		foreach ( is_array( $rows ) ? $rows : array() as $row ) {
			$clean[] = array( 'table_color' => sanitize_text_field( $row['table_color'] ?? '' ), 'chair_color' => sanitize_text_field( $row['chair_color'] ?? '' ), 'image' => esc_url_raw( $row['image'] ?? '' ) );
		}
		update_post_meta( $product_id, '_cwpc_dining_preview_map', wp_json_encode( $clean ) );
	}
}
