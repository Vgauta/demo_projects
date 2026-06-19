<?php
defined( 'ABSPATH' ) || exit;

class CWPC_Admin {
	public function __construct() {
		add_action( 'add_meta_boxes', array( $this, 'metaboxes' ) );
		add_action( 'save_post_cwpc_configurator', array( $this, 'save' ) );
	}
	public function metaboxes() {
		add_meta_box( 'cwpc_builder', __( 'Configurator Builder', 'custom-wc-product-configurator' ), array( $this, 'render' ), 'cwpc_configurator', 'normal', 'high' );
	}
	public function render( $post ) {
		wp_nonce_field( 'cwpc_save_configurator', 'cwpc_nonce' );
		$schema = get_post_meta( $post->ID, '_cwpc_schema', true );
		if ( empty( $schema ) ) { $schema = wp_json_encode( CWPC_Plugin::default_schema(), JSON_PRETTY_PRINT ); }
		echo '<p>Define an extensible JSON schema for layers, options, prices, upload/text fields, and conditional rules. Use the helper buttons for quick starts.</p>';
		echo '<p><button type="button" class="button" id="cwpc-load-example">Load Example</button> <button type="button" class="button" id="cwpc-format-json">Format JSON</button></p>';
		echo '<textarea id="cwpc-schema" name="cwpc_schema" rows="26" class="large-text code">' . esc_textarea( $schema ) . '</textarea>';
		echo '<p class="description">Layer types: color, image, option, text, upload. Conditions use {"field":"base","equals":"black"}.</p>';
	}
	public function save( $post_id ) {
		if ( ! isset( $_POST['cwpc_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['cwpc_nonce'] ) ), 'cwpc_save_configurator' ) ) { return; }
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) { return; }
		if ( ! current_user_can( 'edit_post', $post_id ) ) { return; }
		$raw = isset( $_POST['cwpc_schema'] ) ? wp_unslash( $_POST['cwpc_schema'] ) : '';
		$data = json_decode( $raw, true );
		if ( is_array( $data ) ) {
			update_post_meta( $post_id, '_cwpc_schema', wp_json_encode( $this->sanitize_schema( $data ), JSON_PRETTY_PRINT ) );
		}
	}
	private function sanitize_schema( $data ) {
		foreach ( $data['layers'] ?? array() as &$layer ) {
			foreach ( $layer as $key => $value ) {
				if ( ! is_array( $value ) ) { $layer[ $key ] = is_numeric( $value ) ? (float) $value : sanitize_text_field( $value ); }
			}
			foreach ( $layer['options'] ?? array() as &$option ) {
				foreach ( $option as $key => $value ) { if ( ! is_array( $value ) ) { $option[ $key ] = is_numeric( $value ) ? (float) $value : sanitize_text_field( $value ); } }
			}
		}
		return $data;
	}
}
