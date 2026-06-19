<?php
defined( 'ABSPATH' ) || exit;
class CWPC_Product_Metabox {
	private $positions = array(
		'woocommerce_before_add_to_cart_button' => 'Before add to cart button',
		'woocommerce_after_add_to_cart_button' => 'After add to cart button',
		'woocommerce_before_single_product_summary' => 'Before product summary',
		'woocommerce_after_single_product_summary' => 'After product summary',
		'shortcode_only' => 'Shortcode only',
	);
	public function __construct() {
		add_filter( 'woocommerce_product_data_tabs', array( $this, 'tab' ) );
		add_action( 'woocommerce_product_data_panels', array( $this, 'panel' ) );
		add_action( 'woocommerce_process_product_meta', array( $this, 'save' ) );
		add_filter( 'woocommerce_quantity_input_args', array( $this, 'quantity_args' ), 10, 2 );
	}
	public function tab( $tabs ) {
		$tabs['cwpc_configurator'] = array( 'label' => __( 'Configurator', 'custom-wc-product-configurator' ), 'target' => 'cwpc_configurator_product_data', 'class' => array(), 'priority' => 75 );
		return $tabs;
	}
	public function panel() {
		global $post;
		$product_id = $post ? $post->ID : 0;
		$configs = get_posts( array( 'post_type' => 'cwpc_configurator', 'numberposts' => -1, 'post_status' => 'publish' ) );
		$selected = (int) get_post_meta( $product_id, '_cwpc_configurator_id', true );
		$position = get_post_meta( $product_id, '_cwpc_position', true ) ?: 'woocommerce_before_add_to_cart_button';
		$options = json_decode( get_post_meta( $product_id, '_cwpc_product_options', true ), true );
		if ( ! is_array( $options ) ) { $options = array(); }
		echo '<div id="cwpc_configurator_product_data" class="panel woocommerce_options_panel hidden"><div class="options_group">';
		woocommerce_wp_checkbox( array( 'id' => '_cwpc_enabled', 'label' => __( 'Enable Configurator', 'custom-wc-product-configurator' ), 'value' => get_post_meta( $product_id, '_cwpc_enabled', true ) ) );
		echo '<p class="form-field"><label for="_cwpc_configurator_id">' . esc_html__( 'Starting Point / Configurator Template', 'custom-wc-product-configurator' ) . '</label><select id="_cwpc_configurator_id" name="_cwpc_configurator_id"><option value="0">' . esc_html__( 'None', 'custom-wc-product-configurator' ) . '</option>';
		foreach ( $configs as $config ) { echo '<option value="' . esc_attr( $config->ID ) . '" ' . selected( $selected, $config->ID, false ) . '>' . esc_html( $config->post_title ) . '</option>'; }
		echo '</select></p>';
		woocommerce_wp_text_input( array( 'id' => '_cwpc_manual_configurator_code', 'label' => __( 'Manual template code/ID', 'custom-wc-product-configurator' ), 'description' => __( 'Optional fallback for custom integrations.', 'custom-wc-product-configurator' ), 'desc_tip' => true, 'value' => get_post_meta( $product_id, '_cwpc_manual_configurator_code', true ) ) );
		woocommerce_wp_text_input( array( 'id' => '_cwpc_beginning_quantity', 'label' => __( 'Beginning Quantity', 'custom-wc-product-configurator' ), 'type' => 'number', 'custom_attributes' => array( 'min' => '1', 'step' => '1' ), 'description' => __( 'Quantity added first time customer adds product to cart.', 'custom-wc-product-configurator' ), 'desc_tip' => true, 'value' => get_post_meta( $product_id, '_cwpc_beginning_quantity', true ) ?: 1 ) );
		woocommerce_wp_text_input( array( 'id' => '_cwpc_min_quantity', 'label' => __( 'Minimum Quantity', 'custom-wc-product-configurator' ), 'type' => 'number', 'custom_attributes' => array( 'min' => '0', 'step' => '1' ), 'value' => get_post_meta( $product_id, '_cwpc_min_quantity', true ) ) );
		woocommerce_wp_text_input( array( 'id' => '_cwpc_max_quantity', 'label' => __( 'Maximum Quantity', 'custom-wc-product-configurator' ), 'type' => 'number', 'custom_attributes' => array( 'min' => '0', 'step' => '1' ), 'value' => get_post_meta( $product_id, '_cwpc_max_quantity', true ) ) );
		woocommerce_wp_text_input( array( 'id' => '_cwpc_step_quantity', 'label' => __( 'Increment Step Quantity', 'custom-wc-product-configurator' ), 'type' => 'number', 'custom_attributes' => array( 'min' => '1', 'step' => '1' ), 'value' => get_post_meta( $product_id, '_cwpc_step_quantity', true ) ?: 1 ) );
		woocommerce_wp_checkbox( array( 'id' => '_cwpc_hide_until_complete', 'label' => __( 'Require Complete Configuration', 'custom-wc-product-configurator' ), 'value' => get_post_meta( $product_id, '_cwpc_hide_until_complete', true ) ) );
		woocommerce_wp_checkbox( array( 'id' => '_cwpc_hide_add_to_cart', 'label' => __( 'Hide Default Add To Cart Until Ready', 'custom-wc-product-configurator' ), 'value' => get_post_meta( $product_id, '_cwpc_hide_add_to_cart', true ) ) );
		echo '<p class="form-field"><label for="_cwpc_position">' . esc_html__( 'Configurator Position', 'custom-wc-product-configurator' ) . '</label><select id="_cwpc_position" name="_cwpc_position">';
		foreach ( $this->positions as $key => $label ) { echo '<option value="' . esc_attr( $key ) . '" ' . selected( $position, $key, false ) . '>' . esc_html( $label ) . '</option>'; }
		echo '</select></p>';
		$base = get_post_meta( $product_id, '_cwpc_base_preview_image', true );
		echo '<p class="form-field"><label>' . esc_html__( 'Product Base Preview Image', 'custom-wc-product-configurator' ) . '</label><input type="text" class="short cwpc-product-base-image" name="_cwpc_base_preview_image" value="' . esc_attr( $base ) . '"> <button type="button" class="button cwpc-product-media">' . esc_html__( 'Choose Image', 'custom-wc-product-configurator' ) . '</button></p>';
		echo '</div><div class="options_group cwpc-product-options"><h4>' . esc_html__( 'Product Option Images / Layers', 'custom-wc-product-configurator' ) . '</h4><div id="cwpc-product-option-list" data-options="' . esc_attr( wp_json_encode( $options ) ) . '"></div><p><button type="button" class="button" id="cwpc-add-product-option">' . esc_html__( 'Add Product Option', 'custom-wc-product-configurator' ) . '</button></p><input type="hidden" id="_cwpc_product_options" name="_cwpc_product_options" value="' . esc_attr( wp_json_encode( $options ) ) . '"></div></div>';
	}
	public function save( $product_id ) {
		update_post_meta( $product_id, '_cwpc_enabled', isset( $_POST['_cwpc_enabled'] ) ? 'yes' : 'no' );
		update_post_meta( $product_id, '_cwpc_configurator_id', absint( $_POST['_cwpc_configurator_id'] ?? 0 ) );
		update_post_meta( $product_id, '_cwpc_manual_configurator_code', sanitize_text_field( wp_unslash( $_POST['_cwpc_manual_configurator_code'] ?? '' ) ) );
		foreach ( array( '_cwpc_beginning_quantity', '_cwpc_min_quantity', '_cwpc_max_quantity', '_cwpc_step_quantity' ) as $key ) { update_post_meta( $product_id, $key, absint( $_POST[ $key ] ?? ( '_cwpc_step_quantity' === $key || '_cwpc_beginning_quantity' === $key ? 1 : 0 ) ) ); }
		update_post_meta( $product_id, '_cwpc_hide_until_complete', isset( $_POST['_cwpc_hide_until_complete'] ) ? 'yes' : 'no' );
		update_post_meta( $product_id, '_cwpc_hide_add_to_cart', isset( $_POST['_cwpc_hide_add_to_cart'] ) ? 'yes' : 'no' );
		update_post_meta( $product_id, '_cwpc_position', sanitize_key( $_POST['_cwpc_position'] ?? 'woocommerce_before_add_to_cart_button' ) );
		update_post_meta( $product_id, '_cwpc_base_preview_image', esc_url_raw( wp_unslash( $_POST['_cwpc_base_preview_image'] ?? '' ) ) );
		$options = json_decode( wp_unslash( $_POST['_cwpc_product_options'] ?? '[]' ), true );
		$clean = array();
		foreach ( is_array( $options ) ? $options : array() as $option ) { $clean[] = array( 'title' => sanitize_text_field( $option['title'] ?? '' ), 'type' => sanitize_key( $option['type'] ?? 'image' ), 'color' => sanitize_hex_color( $option['color'] ?? '' ), 'image' => esc_url_raw( $option['image'] ?? '' ), 'price' => (float) ( $option['price'] ?? 0 ), 'order' => (float) ( $option['order'] ?? 0 ), 'enabled' => empty( $option['enabled'] ) ? 'no' : 'yes' ); }
		update_post_meta( $product_id, '_cwpc_product_options', wp_json_encode( $clean ) );
	}
	public function quantity_args( $args, $product ) {
		if ( 'yes' !== get_post_meta( $product->get_id(), '_cwpc_enabled', true ) ) { return $args; }
		$args['input_value'] = absint( get_post_meta( $product->get_id(), '_cwpc_beginning_quantity', true ) ) ?: 1;
		$min = absint( get_post_meta( $product->get_id(), '_cwpc_min_quantity', true ) ); $max = absint( get_post_meta( $product->get_id(), '_cwpc_max_quantity', true ) ); $step = absint( get_post_meta( $product->get_id(), '_cwpc_step_quantity', true ) ) ?: 1;
		if ( $min ) { $args['min_value'] = $min; } if ( $max ) { $args['max_value'] = $max; } $args['step'] = $step; return $args;
	}
}
