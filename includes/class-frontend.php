<?php
defined( 'ABSPATH' ) || exit;
class CWPC_Frontend {
	public function __construct() {
		add_action( 'wp', array( $this, 'hook_product' ) ); add_shortcode( 'cwpc_configurator', array( $this, 'shortcode' ) ); add_action( 'wp_ajax_cwpc_upload_preview', array( $this, 'upload_preview' ) ); add_action( 'wp_ajax_nopriv_cwpc_upload_preview', array( $this, 'upload_preview' ) );
	}
	public function hook_product() { if ( is_product() ) { global $post; if ( $post && 'yes' === get_post_meta( $post->ID, '_cwpc_enabled', true ) ) { add_action( get_post_meta( $post->ID, '_cwpc_position', true ) ?: 'woocommerce_before_add_to_cart_button', array( $this, 'render_product' ), 5 ); } } }
	public function render_product() { global $product; if ( $product ) { echo $this->render( $product->get_id(), (int) get_post_meta( $product->get_id(), '_cwpc_configurator_id', true ) ); } }
	public function shortcode( $atts ) { $atts = shortcode_atts( array( 'product_id' => get_the_ID(), 'configurator_id' => 0 ), $atts ); return $this->render( absint( $atts['product_id'] ), absint( $atts['configurator_id'] ) ); }
	public function render( $product_id, $configurator_id = 0 ) {
		if ( ! $configurator_id ) { $configurator_id = (int) get_post_meta( $product_id, '_cwpc_configurator_id', true ); } if ( ! $configurator_id ) { return ''; }
		$schema = json_decode( get_post_meta( $configurator_id, '_cwpc_schema', true ), true ); if ( ! $schema ) { $schema = CWPC_Plugin::default_schema(); }
		wp_enqueue_style( 'cwpc-frontend' ); wp_enqueue_script( 'cwpc-frontend' );
		ob_start(); ?>
		<div class="cwpc-configurator" data-schema='<?php echo esc_attr( wp_json_encode( $schema ) ); ?>' data-product-id="<?php echo esc_attr( $product_id ); ?>">
			<div class="cwpc-preview"><canvas width="720" height="520" aria-label="Product preview"></canvas></div>
			<div class="cwpc-panel"><h3><?php esc_html_e( 'Customize your product', 'custom-wc-product-configurator' ); ?></h3><div class="cwpc-fields"></div><div class="cwpc-price"></div><button type="button" class="button cwpc-reset">Reset</button></div>
			<input type="hidden" name="cwpc_configuration" class="cwpc-configuration" value=""><input type="hidden" name="cwpc_preview_image" class="cwpc-preview-image" value="">
		</div><?php return ob_get_clean();
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
