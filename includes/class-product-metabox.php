<?php
defined( 'ABSPATH' ) || exit;
class CWPC_Product_Metabox {
	public function __construct() { add_action( 'add_meta_boxes_product', array( $this, 'add' ) ); add_action( 'save_post_product', array( $this, 'save' ) ); }
	public function add() { add_meta_box( 'cwpc_product', __( 'Product Configurator', 'custom-wc-product-configurator' ), array( $this, 'render' ), 'product', 'side' ); }
	public function render( $post ) {
		wp_nonce_field( 'cwpc_save_product', 'cwpc_product_nonce' );
		$enabled = get_post_meta( $post->ID, '_cwpc_enabled', true ); $selected = (int) get_post_meta( $post->ID, '_cwpc_configurator_id', true );
		$position = get_post_meta( $post->ID, '_cwpc_position', true ) ?: 'woocommerce_before_add_to_cart_button'; $hide = get_post_meta( $post->ID, '_cwpc_hide_until_complete', true );
		$configs = get_posts( array( 'post_type' => 'cwpc_configurator', 'numberposts' => -1, 'post_status' => 'publish' ) );
		echo '<p><label><input type="checkbox" name="cwpc_enabled" value="yes" ' . checked( $enabled, 'yes', false ) . '> Enable configurator</label></p><p><label>Template</label><select name="cwpc_configurator_id" class="widefat"><option value="0">None</option>';
		foreach ( $configs as $config ) { echo '<option value="' . esc_attr( $config->ID ) . '" ' . selected( $selected, $config->ID, false ) . '>' . esc_html( $config->post_title ) . '</option>'; }
		echo '</select></p><p><label>Position hook</label><input class="widefat" name="cwpc_position" value="' . esc_attr( $position ) . '"></p><p><label><input type="checkbox" name="cwpc_hide_until_complete" value="yes" ' . checked( $hide, 'yes', false ) . '> Require complete configuration</label></p>';
	}
	public function save( $post_id ) {
		if ( ! isset( $_POST['cwpc_product_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['cwpc_product_nonce'] ) ), 'cwpc_save_product' ) || ! current_user_can( 'edit_post', $post_id ) ) { return; }
		update_post_meta( $post_id, '_cwpc_enabled', isset( $_POST['cwpc_enabled'] ) ? 'yes' : 'no' );
		update_post_meta( $post_id, '_cwpc_configurator_id', absint( $_POST['cwpc_configurator_id'] ?? 0 ) );
		update_post_meta( $post_id, '_cwpc_position', sanitize_key( $_POST['cwpc_position'] ?? 'woocommerce_before_add_to_cart_button' ) );
		update_post_meta( $post_id, '_cwpc_hide_until_complete', isset( $_POST['cwpc_hide_until_complete'] ) ? 'yes' : 'no' );
	}
}
