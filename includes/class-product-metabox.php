<?php
defined( 'ABSPATH' ) || exit;
class CWPC_Product_Metabox {
	public function __construct() {
		add_filter( 'woocommerce_product_data_tabs', array( $this, 'tab' ) );
		add_action( 'woocommerce_product_data_panels', array( $this, 'panel' ) );
		add_action( 'woocommerce_process_product_meta', array( $this, 'save' ) );
	}
	public function tab( $tabs ) {
		$tabs['cwpc_dining_set'] = array( 'label' => __( 'Dining Set Configurator', 'custom-wc-product-configurator' ), 'target' => 'cwpc_dining_set_product_data', 'class' => array(), 'priority' => 75 );
		return $tabs;
	}
	public function panel() {
		global $post;
		$product_id = $post ? $post->ID : 0;
		$schema = get_post_meta( $product_id, '_cwpc_dining_schema', true );
		if ( ! $schema ) { $schema = get_option( 'cwpc_default_dining_schema', wp_json_encode( CWPC_Plugin::default_dining_schema(), CWPC_JSON_FLAGS ) ); }
		echo '<div id="cwpc_dining_set_product_data" class="panel woocommerce_options_panel hidden"><div class="options_group">';
		woocommerce_wp_checkbox( array( 'id' => '_cwpc_dining_enabled', 'label' => __( 'Enable Dining Set Configurator', 'custom-wc-product-configurator' ), 'value' => get_post_meta( $product_id, '_cwpc_dining_enabled', true ) ) );
		echo '<p class="form-field"><label>' . esc_html__( 'Default template', 'custom-wc-product-configurator' ) . '</label><button type="button" class="button" id="cwpc-load-dining-default">' . esc_html__( 'Load Default Dining Set Template', 'custom-wc-product-configurator' ) . '</button><span class="description"> ' . esc_html__( 'Loads table colors, chair designs, extra chairs, chair colors, and addons.', 'custom-wc-product-configurator' ) . '</span></p>';
		echo '</div><div class="options_group cwpc-dining-schema-builder" data-default="' . esc_attr( get_option( 'cwpc_default_dining_schema', wp_json_encode( CWPC_Plugin::default_dining_schema(), CWPC_JSON_FLAGS ) ) ) . '">';
		echo '<input type="hidden" id="_cwpc_dining_schema" name="_cwpc_dining_schema" value="' . esc_attr( $schema ) . '">';
		echo '<h4>Preview Recolor Layers</h4><p class="description">Upload one base image and transparent PNG masks. Colors are applied dynamically from color codes.</p><div id="cwpc-dining-preview-assets" class="cwpc-repeat-list"></div>';
		echo '<h4>1. Choose Table Color</h4><div id="cwpc-table-colors" class="cwpc-repeat-list"></div><p><button type="button" class="button cwpc-add-row" data-target="table_colors">Add Table Color</button></p>';
		echo '<h4>2. Choose Chair Design</h4><div id="cwpc-chair-designs" class="cwpc-repeat-list"></div><p><button type="button" class="button cwpc-add-row" data-target="chair_designs">Add Chair Design</button></p>';
		echo '<h4>3. Extra Chairs</h4><div id="cwpc-extra-chairs" class="cwpc-repeat-list"></div><p><button type="button" class="button cwpc-add-row" data-target="extra_chairs">Add Extra Chairs Option</button></p>';
		echo '<h4>4-5. Chair Color</h4><div id="cwpc-chair-colors" class="cwpc-repeat-list"></div><p><button type="button" class="button cwpc-add-row" data-target="chair_colors">Add Chair Color</button></p>';
		echo '<h4>Chair Color Images per Design</h4><div id="cwpc-chair-color-images" class="cwpc-repeat-list"></div><p><button type="button" class="button cwpc-add-row" data-target="chair_color_images">Add Chair Color Image</button></p>';
		echo '<h4>6. Addons</h4><div id="cwpc-addons" class="cwpc-repeat-list"></div><p><button type="button" class="button cwpc-add-row" data-target="addons">Add Addon</button></p>';
		echo '</div></div>';
	}
	public function save( $product_id ) {
		update_post_meta( $product_id, '_cwpc_dining_enabled', isset( $_POST['_cwpc_dining_enabled'] ) ? 'yes' : 'no' );
		$schema = json_decode( wp_unslash( $_POST['_cwpc_dining_schema'] ?? '' ), true );
		if ( ! is_array( $schema ) ) { $schema = CWPC_Plugin::default_dining_schema(); }
		$clean = array( 'base_image' => esc_url_raw( $schema['base_image'] ?? '' ), 'base_image_id' => absint( $schema['base_image_id'] ?? 0 ), 'table_mask' => esc_url_raw( $schema['table_mask'] ?? '' ), 'table_mask_id' => absint( $schema['table_mask_id'] ?? 0 ), 'chair_mask' => esc_url_raw( $schema['chair_mask'] ?? '' ), 'chair_mask_id' => absint( $schema['chair_mask_id'] ?? 0 ), 'table_colors' => array(), 'chair_designs' => array(), 'extra_chairs' => array(), 'chair_colors' => array(), 'chair_color_images' => array(), 'addons' => array() );
		foreach ( $schema['table_colors'] ?? array() as $row ) { $clean['table_colors'][] = array( 'name' => CWPC_Plugin::sanitize_utf8_text( $row['name'] ?? '' ), 'color' => sanitize_hex_color( $row['color'] ?? '' ), 'price' => (float) ( $row['price'] ?? 0 ), 'preview' => esc_url_raw( $row['preview'] ?? '' ), 'preview_id' => absint( $row['preview_id'] ?? 0 ) ); }
		foreach ( $schema['chair_designs'] ?? array() as $row ) { $clean['chair_designs'][] = array( 'name' => CWPC_Plugin::sanitize_utf8_text( $row['name'] ?? '' ), 'thumbnail' => esc_url_raw( $row['thumbnail'] ?? '' ), 'thumbnail_id' => absint( $row['thumbnail_id'] ?? 0 ), 'preview' => esc_url_raw( $row['preview'] ?? '' ), 'preview_id' => absint( $row['preview_id'] ?? 0 ), 'price' => (float) ( $row['price'] ?? 0 ), 'colors' => array_map( array( 'CWPC_Plugin', 'sanitize_utf8_text' ), (array) ( $row['colors'] ?? array() ) ) ); }
		foreach ( $schema['extra_chairs'] ?? array() as $row ) { $clean['extra_chairs'][] = array( 'label' => CWPC_Plugin::sanitize_utf8_text( $row['label'] ?? '' ), 'quantity' => absint( $row['quantity'] ?? 0 ), 'price' => (float) ( $row['price'] ?? 0 ) ); }
		foreach ( $schema['chair_colors'] ?? array() as $row ) { $clean['chair_colors'][] = array( 'name' => CWPC_Plugin::sanitize_utf8_text( $row['name'] ?? '' ), 'color' => sanitize_hex_color( $row['color'] ?? '' ), 'price' => (float) ( $row['price'] ?? 0 ), 'preview' => esc_url_raw( $row['preview'] ?? '' ), 'preview_id' => absint( $row['preview_id'] ?? 0 ) ); }
		foreach ( $schema['chair_color_images'] ?? array() as $row ) { $clean['chair_color_images'][] = array( 'design' => CWPC_Plugin::sanitize_utf8_text( $row['design'] ?? '' ), 'color' => CWPC_Plugin::sanitize_utf8_text( $row['color'] ?? '' ), 'preview' => esc_url_raw( $row['preview'] ?? '' ), 'preview_id' => absint( $row['preview_id'] ?? 0 ) ); }
		foreach ( $schema['addons'] ?? array() as $row ) { $clean['addons'][] = array( 'name' => CWPC_Plugin::sanitize_utf8_text( $row['name'] ?? '' ), 'price' => (float) ( $row['price'] ?? 0 ), 'enabled' => empty( $row['enabled'] ) ? 'no' : 'yes' ); }
		update_post_meta( $product_id, '_cwpc_dining_schema', wp_json_encode( $clean, CWPC_JSON_FLAGS ) );
	}
}
