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
		if ( empty( $schema ) ) {
			$schema = wp_json_encode( CWPC_Plugin::default_schema(), CWPC_JSON_FLAGS | JSON_PRETTY_PRINT );
		}
		echo '<div class="cwpc-builder" data-schema="' . esc_attr( $schema ) . '">';
		echo '<nav class="cwpc-tabs"><button type="button" class="button button-primary cwpc-tab" data-tab="visual">Visual Builder</button><button type="button" class="button cwpc-tab" data-tab="json">Advanced JSON</button></nav>';
		echo '<div class="cwpc-notices" aria-live="polite"></div>';
		echo '<section class="cwpc-tab-panel cwpc-visual" data-panel="visual">';
		echo '<p class="description">Create this configurator with editable cards. No JSON editing is needed for normal store admins.</p>';
		echo '<div class="cwpc-actions"><button type="button" class="button" id="cwpc-add-layer">Add Layer</button><button type="button" class="button" id="cwpc-add-group">Add Option Group</button><button type="button" class="button" id="cwpc-add-text">Add Text Field</button><button type="button" class="button" id="cwpc-add-upload">Add Upload Field</button></div>';
		echo '<h3>Product Preview Layers</h3><div id="cwpc-layers" class="cwpc-repeat-list"></div>';
		echo '<h3>Option Groups</h3><div id="cwpc-groups" class="cwpc-repeat-list"></div>';
		echo '<h3>Text Fields</h3><div id="cwpc-text-fields" class="cwpc-repeat-list"></div>';
		echo '<h3>Upload Fields</h3><div id="cwpc-upload-fields" class="cwpc-repeat-list"></div>';
		echo '</section>';
		echo '<section class="cwpc-tab-panel cwpc-json" data-panel="json" hidden><div class="notice notice-warning inline"><p><strong>Advanced developer mode.</strong> Editing this can break the configurator.</p></div><p><button type="button" class="button" id="cwpc-load-example">Load Example</button> <button type="button" class="button" id="cwpc-format-json">Load JSON into Visual Builder</button></p><textarea id="cwpc-schema" name="cwpc_schema" rows="22" class="large-text code">' . esc_textarea( $schema ) . '</textarea></section>';
		echo '</div>';
	}
	public function save( $post_id ) {
		if ( ! isset( $_POST['cwpc_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['cwpc_nonce'] ) ), 'cwpc_save_configurator' ) ) { return; }
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) { return; }
		if ( ! current_user_can( 'edit_post', $post_id ) ) { return; }
		$raw = isset( $_POST['cwpc_schema'] ) ? wp_unslash( $_POST['cwpc_schema'] ) : '';
		$data = json_decode( $raw, true );
		if ( is_array( $data ) ) {
			update_post_meta( $post_id, '_cwpc_schema', wp_json_encode( $this->sanitize_schema( $data ), CWPC_JSON_FLAGS | JSON_PRETTY_PRINT ) );
		}
	}
	private function sanitize_schema( $data ) {
		$clean = array( 'layers' => array() );
		foreach ( $data['layers'] ?? array() as $layer ) {
			$item = array(
				'id' => sanitize_key( $layer['id'] ?? '' ),
				'title' => CWPC_Plugin::sanitize_utf8_text( $layer['title'] ?? '' ),
				'type' => sanitize_key( $layer['type'] ?? 'option' ),
				'section' => sanitize_key( $layer['section'] ?? '' ),
				'display_type' => sanitize_key( $layer['display_type'] ?? 'buttons' ),
				'required' => empty( $layer['required'] ) ? 'no' : 'yes',
				'order' => (float) ( $layer['order'] ?? 0 ),
				'enabled' => empty( $layer['enabled'] ) ? 'no' : 'yes',
				'image' => esc_url_raw( $layer['image'] ?? '' ),
				'image_id' => absint( $layer['image_id'] ?? 0 ),
				'price' => (float) ( $layer['price'] ?? 0 ),
				'placeholder' => CWPC_Plugin::sanitize_utf8_text( $layer['placeholder'] ?? '' ),
				'max_length' => absint( $layer['max_length'] ?? 80 ),
				'allowed_types' => sanitize_text_field( $layer['allowed_types'] ?? 'jpg,png,gif,webp' ),
				'max_size' => (float) ( $layer['max_size'] ?? 5 ),
				'conditions' => array(),
				'options' => array(),
			);
			foreach ( $layer['conditions'] ?? array() as $condition ) {
				$item['conditions'][] = array( 'field' => sanitize_key( $condition['field'] ?? '' ), 'equals' => CWPC_Plugin::sanitize_utf8_text( $condition['equals'] ?? '' ) );
			}
			foreach ( $layer['options'] ?? array() as $option ) {
				$item['options'][] = array(
					'id' => sanitize_key( $option['id'] ?? '' ), 'title' => CWPC_Plugin::sanitize_utf8_text( $option['title'] ?? '' ), 'label' => CWPC_Plugin::sanitize_utf8_text( $option['label'] ?? '' ),
					'image' => esc_url_raw( $option['image'] ?? '' ), 'image_id' => absint( $option['image_id'] ?? 0 ), 'layer_image' => esc_url_raw( $option['layer_image'] ?? '' ), 'layer_image_id' => absint( $option['layer_image_id'] ?? 0 ), 'color' => sanitize_hex_color( $option['color'] ?? '' ),
					'price' => (float) ( $option['price'] ?? 0 ), 'order' => (float) ( $option['order'] ?? 0 ), 'default' => empty( $option['default'] ) ? 'no' : 'yes', 'enabled' => empty( $option['enabled'] ) ? 'no' : 'yes',
					'conditions' => array_map( function( $condition ) { return array( 'field' => sanitize_key( $condition['field'] ?? '' ), 'equals' => CWPC_Plugin::sanitize_utf8_text( $condition['equals'] ?? '' ) ); }, $option['conditions'] ?? array() ),
				);
			}
			$clean['layers'][] = $item;
		}
		return $clean;
	}
}
