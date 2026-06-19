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
			$schema = wp_json_encode( CWPC_Plugin::default_schema(), JSON_PRETTY_PRINT );
		}
		echo '<div class="cwpc-builder" data-schema="' . esc_attr( $schema ) . '">';
		echo '<nav class="cwpc-tabs"><button type="button" class="button button-primary cwpc-tab" data-tab="visual">Visual Builder</button><button type="button" class="button cwpc-tab" data-tab="json">Advanced JSON</button></nav>';
		echo '<div class="cwpc-notices" aria-live="polite"></div>';
		echo '<section class="cwpc-tab-panel cwpc-visual" data-panel="visual">';
		echo '<p class="description">Build the configurator with simple fields. The plugin stores this as JSON automatically in the background.</p>';
		echo '<div class="cwpc-actions"><button type="button" class="button" id="cwpc-add-layer">Add Layer</button><button type="button" class="button" id="cwpc-add-group">Add Option Group</button><button type="button" class="button" id="cwpc-add-text">Add Text Field</button><button type="button" class="button" id="cwpc-add-upload">Add Upload Field</button></div>';
		echo '<h3>Product Preview Layers</h3><div id="cwpc-layers" class="cwpc-repeat-list"></div>';
		echo '<h3>Options / Variations and Color Choices</h3><div id="cwpc-groups" class="cwpc-repeat-list"></div>';
		echo '<h3>Text Input Fields</h3><div id="cwpc-text-fields" class="cwpc-repeat-list"></div>';
		echo '<h3>Upload Fields</h3><div id="cwpc-upload-fields" class="cwpc-repeat-list"></div>';
		echo '<h3>Conditional Logic</h3><p class="description">For each layer, option group, text field, or upload field, use “Show when field” and “equals value” to show it only after a previous choice.</p>';
		echo '</section>';
		echo '<section class="cwpc-tab-panel cwpc-json" data-panel="json" hidden><p class="description">Developer-only JSON view. Clients normally do not need to edit this.</p><p><button type="button" class="button" id="cwpc-load-example">Load Example</button> <button type="button" class="button" id="cwpc-format-json">Format JSON</button></p></section>';
		echo '<textarea id="cwpc-schema" name="cwpc_schema" rows="18" class="large-text code">' . esc_textarea( $schema ) . '</textarea>';
		echo '</div>';
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
		$clean = array( 'layers' => array() );
		foreach ( $data['layers'] ?? array() as $layer ) {
			$item = array(
				'id' => sanitize_key( $layer['id'] ?? '' ),
				'title' => sanitize_text_field( $layer['title'] ?? '' ),
				'type' => sanitize_key( $layer['type'] ?? 'option' ),
				'order' => (float) ( $layer['order'] ?? 0 ),
				'enabled' => empty( $layer['enabled'] ) ? 'no' : 'yes',
				'image' => esc_url_raw( $layer['image'] ?? '' ),
				'price' => (float) ( $layer['price'] ?? 0 ),
				'placeholder' => sanitize_text_field( $layer['placeholder'] ?? '' ),
				'conditions' => array(),
				'options' => array(),
			);
			foreach ( $layer['conditions'] ?? array() as $condition ) {
				$item['conditions'][] = array( 'field' => sanitize_key( $condition['field'] ?? '' ), 'equals' => sanitize_text_field( $condition['equals'] ?? '' ) );
			}
			foreach ( $layer['options'] ?? array() as $option ) {
				$item['options'][] = array(
					'id' => sanitize_key( $option['id'] ?? '' ), 'title' => sanitize_text_field( $option['title'] ?? '' ), 'label' => sanitize_text_field( $option['label'] ?? '' ),
					'image' => esc_url_raw( $option['image'] ?? '' ), 'layer_image' => esc_url_raw( $option['layer_image'] ?? '' ), 'color' => sanitize_hex_color( $option['color'] ?? '' ),
					'price' => (float) ( $option['price'] ?? 0 ), 'order' => (float) ( $option['order'] ?? 0 ), 'enabled' => empty( $option['enabled'] ) ? 'no' : 'yes',
					'conditions' => array_map( function( $condition ) { return array( 'field' => sanitize_key( $condition['field'] ?? '' ), 'equals' => sanitize_text_field( $condition['equals'] ?? '' ) ); }, $option['conditions'] ?? array() ),
				);
			}
			$clean['layers'][] = $item;
		}
		return $clean;
	}
}
