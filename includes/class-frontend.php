<?php
defined( 'ABSPATH' ) || exit;
class CWPC_Frontend {
	public function __construct() {
		add_action( 'wp', array( $this, 'hook_product' ) ); add_shortcode( 'cwpc_configurator', array( $this, 'shortcode' ) ); add_action( 'wp_ajax_cwpc_upload_preview', array( $this, 'upload_preview' ) ); add_action( 'wp_ajax_nopriv_cwpc_upload_preview', array( $this, 'upload_preview' ) );
	}
	public function hook_product() { if ( is_product() ) { global $post; if ( $post && 'yes' === get_post_meta( $post->ID, '_cwpc_enabled', true ) ) { $position = get_post_meta( $post->ID, '_cwpc_position', true ) ?: 'woocommerce_before_add_to_cart_button'; if ( 'shortcode_only' !== $position ) { add_action( $position, array( $this, 'render_product' ), 5 ); } } } }
	public function render_product() { global $product; if ( $product ) { echo $this->render( $product->get_id(), (int) get_post_meta( $product->get_id(), '_cwpc_configurator_id', true ) ); } }
	public function shortcode( $atts ) { $atts = shortcode_atts( array( 'product_id' => get_the_ID(), 'configurator_id' => 0 ), $atts ); return $this->render( absint( $atts['product_id'] ), absint( $atts['configurator_id'] ) ); }
	public function render( $product_id, $configurator_id = 0 ) {
		if ( ! $configurator_id ) { $configurator_id = (int) get_post_meta( $product_id, '_cwpc_configurator_id', true ); }
		$schema = $configurator_id ? json_decode( get_post_meta( $configurator_id, '_cwpc_schema', true ), true ) : array( 'layers' => array() ); if ( ! $schema ) { $schema = CWPC_Plugin::default_schema(); }
		$schema = $this->merge_product_schema( $schema, $product_id );
		wp_enqueue_style( 'cwpc-frontend' ); wp_enqueue_script( 'cwpc-frontend' );
		ob_start(); ?>
		<div class="cwpc-configurator" data-schema='<?php echo esc_attr( wp_json_encode( $schema ) ); ?>' data-product-id="<?php echo esc_attr( $product_id ); ?>" data-hide-add-to-cart="<?php echo esc_attr( get_post_meta( $product_id, '_cwpc_hide_add_to_cart', true ) ); ?>">
			<div class="cwpc-preview"><canvas width="720" height="520" aria-label="Product preview"></canvas></div>
			<div class="cwpc-panel"><h3><?php esc_html_e( 'Customize your product', 'custom-wc-product-configurator' ); ?></h3><div class="cwpc-fields"></div><div class="cwpc-price"></div><button type="button" class="button cwpc-reset">Reset</button></div>
			<input type="hidden" name="cwpc_configuration" class="cwpc-configuration" value=""><input type="hidden" name="cwpc_preview_image" class="cwpc-preview-image" value="">
		</div><?php return ob_get_clean();
	}

	private function merge_product_schema( $schema, $product_id ) {
		if ( empty( $schema['layers'] ) || ! is_array( $schema['layers'] ) ) { $schema['layers'] = array(); }
		$base = get_post_meta( $product_id, '_cwpc_base_preview_image', true );
		if ( $base ) { array_unshift( $schema['layers'], array( 'section' => 'layer', 'id' => 'product_base_preview', 'title' => 'Product Base Preview', 'type' => 'image', 'enabled' => 'yes', 'image' => esc_url_raw( $base ), 'order' => 0, 'options' => array() ) ); }
		$options = json_decode( get_post_meta( $product_id, '_cwpc_product_options', true ), true );
		if ( ! is_array( $options ) ) { return $schema; }
		$choice_options = array();
		foreach ( $options as $index => $option ) {
			if ( 'no' === ( $option['enabled'] ?? 'yes' ) ) { continue; }
			$type = sanitize_key( $option['type'] ?? 'image' );
			$id = 'product_option_' . $index;
			if ( in_array( $type, array( 'color', 'image' ), true ) ) {
				$choice_options[] = array( 'id' => $id, 'title' => sanitize_text_field( $option['title'] ?? 'Product option' ), 'label' => sanitize_text_field( $option['title'] ?? 'Product option' ), 'color' => sanitize_hex_color( $option['color'] ?? '' ), 'image' => esc_url_raw( $option['image'] ?? '' ), 'layer_image' => esc_url_raw( $option['image'] ?? '' ), 'price' => (float) ( $option['price'] ?? 0 ), 'order' => (float) ( $option['order'] ?? 0 ), 'enabled' => 'yes' );
			} elseif ( 'text' === $type ) {
				$schema['layers'][] = array( 'section' => 'text', 'id' => $id, 'title' => sanitize_text_field( $option['title'] ?? 'Product text' ), 'type' => 'text', 'enabled' => 'yes', 'price' => (float) ( $option['price'] ?? 0 ), 'order' => (float) ( $option['order'] ?? 0 ), 'placeholder' => sanitize_text_field( $option['title'] ?? '' ) );
			} elseif ( 'upload' === $type ) {
				$schema['layers'][] = array( 'section' => 'upload', 'id' => $id, 'title' => sanitize_text_field( $option['title'] ?? 'Product upload' ), 'type' => 'upload', 'enabled' => 'yes', 'price' => (float) ( $option['price'] ?? 0 ), 'order' => (float) ( $option['order'] ?? 0 ), 'allowed_types' => 'jpg,png,gif,webp', 'max_size' => 5 );
			}
		}
		if ( $choice_options ) { $schema['layers'][] = array( 'section' => 'group', 'id' => 'product_options', 'title' => 'Product Options', 'type' => 'option', 'display_type' => 'buttons', 'enabled' => 'yes', 'order' => 15, 'options' => $choice_options ); }
		return $schema;
	}

	public function upload_preview() {
		check_ajax_referer( 'cwpc_frontend', 'nonce' );
		$data = isset( $_POST['image'] ) ? (string) wp_unslash( $_POST['image'] ) : ''; if ( ! preg_match( '/^data:image\/(png|jpeg);base64,/', $data ) ) { wp_send_json_error( 'Invalid image.' ); }
		$bits = base64_decode( preg_replace( '/^data:image\/(png|jpeg);base64,/', '', $data ) ); if ( ! $bits || strlen( $bits ) > 5 * MB_IN_BYTES ) { wp_send_json_error( 'Invalid size.' ); }
		$upload = wp_upload_bits( 'cwpc-preview-' . time() . '.png', null, $bits ); if ( ! empty( $upload['error'] ) ) { wp_send_json_error( $upload['error'] ); }
		$attachment_id = wp_insert_attachment( array( 'post_mime_type' => 'image/png', 'post_title' => basename( $upload['file'] ), 'post_status' => 'private' ), $upload['file'] );
		wp_send_json_success( array( 'attachment_id' => $attachment_id, 'url' => esc_url_raw( $upload['url'] ) ) );
	}
}
