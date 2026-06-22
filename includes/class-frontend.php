<?php
defined( 'ABSPATH' ) || exit;
class CWPC_Frontend {
	public function __construct() {
		add_action( 'wp', array( $this, 'hook_product' ) ); add_shortcode( 'cwpc_configurator', array( $this, 'shortcode' ) ); add_action( 'wp_ajax_cwpc_upload_preview', array( $this, 'upload_preview' ) ); add_action( 'wp_ajax_nopriv_cwpc_upload_preview', array( $this, 'upload_preview' ) );
	}
	public function hook_product() {
		if ( ! function_exists( 'is_product' ) || ! is_product() ) { return; }
		$product_id = get_queried_object_id();
		if ( $product_id && 'yes' === get_post_meta( $product_id, '_cwpc_dining_enabled', true ) ) { wp_enqueue_style( 'cwpc-frontend' ); wp_enqueue_script( 'cwpc-frontend' ); add_action( 'woocommerce_before_add_to_cart_button', array( $this, 'render_dining_product' ), 5 ); return; }
		if ( ! $product_id || ! $this->is_enabled( $product_id ) ) { $this->debug( 'Configurator not attached: disabled or missing product.', $product_id ); return; }
		$configurator_id = (int) get_post_meta( $product_id, '_cwpc_configurator_id', true );
		$position = get_post_meta( $product_id, '_cwpc_position', true );
		if ( empty( $position ) ) { $position = 'woocommerce_before_add_to_cart_button'; }
		$allowed = array( 'woocommerce_before_add_to_cart_button', 'woocommerce_after_add_to_cart_button', 'woocommerce_before_single_product_summary', 'woocommerce_after_single_product_summary', 'shortcode_only' );
		if ( ! in_array( $position, $allowed, true ) ) { $position = 'woocommerce_before_add_to_cart_button'; }
		$this->debug( 'Configurator attach check.', $product_id, array( 'enabled' => get_post_meta( $product_id, '_cwpc_enabled', true ), 'configurator_id' => $configurator_id, 'hook' => $position ) );
		if ( ! $this->has_renderable_config( $product_id, $configurator_id ) ) { $this->debug( 'Configurator not attached: no template or product-level options found.', $product_id ); return; }
		wp_enqueue_style( 'cwpc-frontend' ); wp_enqueue_script( 'cwpc-frontend' );
		if ( 'shortcode_only' !== $position ) { add_action( $position, array( $this, 'render_product' ), 5 ); }
	}
	private function is_enabled( $product_id ) { return in_array( get_post_meta( $product_id, '_cwpc_enabled', true ), array( 'yes', '1', 1, true, 'on' ), true ); }
	private function has_renderable_config( $product_id, $configurator_id ) {
		if ( $configurator_id && 'cwpc_configurator' === get_post_type( $configurator_id ) ) { return true; }
		if ( get_post_meta( $product_id, '_cwpc_base_preview_image', true ) ) { return true; }
		$options = json_decode( get_post_meta( $product_id, '_cwpc_product_options', true ), true );
		return is_array( $options ) && ! empty( $options );
	}
	private function debug( $message, $product_id = 0, $context = array() ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG && function_exists( 'error_log' ) ) { error_log( '[CWPC] ' . $message . ' product_id=' . absint( $product_id ) . ' context=' . wp_json_encode( $context, CWPC_JSON_FLAGS ) ); }
	}
	private function image_url_from_row( $row, $field ) {
		$attachment_id = absint( $row[ $field . '_id' ] ?? 0 );
		if ( $attachment_id ) {
			$url = wp_get_attachment_image_url( $attachment_id, 'full' );
			if ( $url ) { return $url; }
		}
		return esc_url_raw( $row[ $field ] ?? '' );
	}
	private function prepare_dining_schema( $schema ) {
		foreach ( array( 'table_colors', 'chair_colors' ) as $section ) {
			if ( empty( $schema[ $section ] ) || ! is_array( $schema[ $section ] ) ) { continue; }
			foreach ( $schema[ $section ] as $index => $row ) { $schema[ $section ][ $index ]['preview'] = $this->image_url_from_row( $row, 'preview' ); }
		}
		if ( ! empty( $schema['chair_designs'] ) && is_array( $schema['chair_designs'] ) ) {
			foreach ( $schema['chair_designs'] as $index => $row ) {
				$schema['chair_designs'][ $index ]['thumbnail'] = $this->image_url_from_row( $row, 'thumbnail' );
				$schema['chair_designs'][ $index ]['preview'] = $this->image_url_from_row( $row, 'preview' );
			}
		}
		if ( ! empty( $schema['chair_color_images'] ) && is_array( $schema['chair_color_images'] ) ) {
			foreach ( $schema['chair_color_images'] as $index => $row ) { $schema['chair_color_images'][ $index ]['preview'] = $this->image_url_from_row( $row, 'preview' ); }
		}
		return $schema;
	}
	private function prepare_generic_schema( $schema ) {
		if ( empty( $schema['layers'] ) || ! is_array( $schema['layers'] ) ) { return $schema; }
		foreach ( $schema['layers'] as $layer_index => $layer ) {
			$schema['layers'][ $layer_index ]['image'] = $this->image_url_from_row( $layer, 'image' );
			if ( empty( $layer['options'] ) || ! is_array( $layer['options'] ) ) { continue; }
			foreach ( $layer['options'] as $option_index => $option ) {
				$schema['layers'][ $layer_index ]['options'][ $option_index ]['image'] = $this->image_url_from_row( $option, 'image' );
				$schema['layers'][ $layer_index ]['options'][ $option_index ]['layer_image'] = $this->image_url_from_row( $option, 'layer_image' );
			}
		}
		return $schema;
	}
	public function render_dining_product() { global $product; $product_id = $product ? $product->get_id() : get_queried_object_id(); if ( $product_id ) { echo $this->render_dining( $product_id ); } }
	private function attribute_values( $product, $needle ) {
		$values = array();
		foreach ( $product->get_attributes() as $attribute ) {
			$label = wc_attribute_label( $attribute->get_name() );
			if ( false === stripos( $label, $needle ) ) { continue; }
			if ( $attribute->is_taxonomy() ) {
				foreach ( wc_get_product_terms( $product->get_id(), $attribute->get_name(), array( 'fields' => 'names' ) ) as $term_name ) { $values[] = $term_name; }
			} else {
				$values = array_merge( $values, $attribute->get_options() );
			}
		}
		return array_values( array_unique( array_filter( array_map( 'wc_clean', $values ) ) ) );
	}
	public function render_dining( $product_id ) {
		$product = wc_get_product( $product_id );
		if ( ! $product || 'yes' !== get_post_meta( $product_id, '_cwpc_dining_enabled', true ) ) { return ''; }
		$schema = json_decode( get_post_meta( $product_id, '_cwpc_dining_schema', true ), true );
		if ( ! is_array( $schema ) ) { $schema = CWPC_Plugin::default_dining_schema(); }
		$schema = $this->prepare_dining_schema( $schema );
		$product_image = $product->get_image_id() ? wp_get_attachment_image_url( $product->get_image_id(), 'full' ) : '';
		wp_enqueue_style( 'cwpc-frontend' ); wp_enqueue_script( 'cwpc-frontend' );
		ob_start(); ?>
		<div class="cwpc-dining-launch"><button type="button" class="button alt cwpc-open-dining-modal">Customize &amp; Add to Cart</button></div>
		<div class="cwpc-dining-modal" aria-hidden="true">
			<div class="cwpc-dining-modal__overlay" data-cwpc-close="1"></div>
			<div class="cwpc-dining-modal__dialog" role="dialog" aria-modal="true" aria-label="Dining set configurator">
				<button type="button" class="cwpc-dining-modal__close" aria-label="Close" data-cwpc-close="1">&times;</button>
				<div class="cwpc-dining-configurator" data-config='<?php echo esc_attr( wp_json_encode( $schema, CWPC_JSON_FLAGS ) ); ?>'>
					<div class="cwpc-dining-preview"><img class="cwpc-preview-layer cwpc-table-layer" alt="Table preview" src="" data-product-image="<?php echo esc_url( $product_image ); ?>"><img class="cwpc-preview-layer cwpc-chair-layer" alt="Chair preview" src=""></div>
					<div class="cwpc-dining-fields">
						<div class="cwpc-step" data-step="1"><h4>1. Choose Table Color</h4><div class="cwpc-table-colors"></div></div>
						<div class="cwpc-step" data-step="2"><h4>2. Choose Chair Design</h4><div class="cwpc-chair-designs"></div></div>
						<div class="cwpc-step" data-step="3"><h4>3. Extra Chairs</h4><select class="cwpc-extra-chairs"></select></div>
						<div class="cwpc-step" data-step="4"><h4>4. Chair Color</h4><div class="cwpc-chair-mode"><button type="button" class="cwpc-mode cwpc-active" data-mode="same">Same as table color</button><button type="button" class="cwpc-mode" data-mode="different">Choose different chair color</button></div></div>
						<div class="cwpc-step cwpc-chair-color-step" data-step="5"><h4>5. Choose Chair Color</h4><div class="cwpc-chair-colors"></div></div>
						<div class="cwpc-step" data-step="6"><h4>6. Addons</h4><div class="cwpc-addons"></div></div>
						<div class="cwpc-dining-price"></div>
						<button type="button" class="button alt cwpc-popup-add-to-cart">Add to Cart</button>
					</div>
					<input type="hidden" name="cwpc_configuration" class="cwpc-configuration" value=""><input type="hidden" name="cwpc_preview_image" class="cwpc-preview-image" value="">
				</div>
			</div>
		</div><?php return ob_get_clean();
	}

	public function render_product() { global $product; $product_id = $product ? $product->get_id() : get_queried_object_id(); if ( $product_id ) { echo $this->render( $product_id, (int) get_post_meta( $product_id, '_cwpc_configurator_id', true ) ); } }
	public function shortcode( $atts ) { $atts = shortcode_atts( array( 'product_id' => get_the_ID(), 'configurator_id' => 0 ), $atts ); return $this->render( absint( $atts['product_id'] ), absint( $atts['configurator_id'] ) ); }
	public function render( $product_id, $configurator_id = 0 ) {
		if ( ! $product_id || ! $this->is_enabled( $product_id ) ) { return ''; }
		if ( ! $configurator_id ) { $configurator_id = (int) get_post_meta( $product_id, '_cwpc_configurator_id', true ); }
		if ( ! $this->has_renderable_config( $product_id, $configurator_id ) ) { return ''; }
		$schema = $configurator_id ? json_decode( get_post_meta( $configurator_id, '_cwpc_schema', true ), true ) : array( 'layers' => array() ); if ( ! $schema ) { $schema = array( 'layers' => array() ); }
		$schema = $this->prepare_generic_schema( $schema );
		$schema = $this->merge_product_schema( $schema, $product_id );
		if ( empty( $schema['layers'] ) ) { $this->debug( 'Configurator render skipped: schema loaded but has no layers.', $product_id, array( 'configurator_id' => $configurator_id ) ); return ''; }
		wp_enqueue_style( 'cwpc-frontend' ); wp_enqueue_script( 'cwpc-frontend' );
		$this->debug( 'Configurator rendered.', $product_id, array( 'configurator_id' => $configurator_id, 'layers' => count( $schema['layers'] ) ) );
		ob_start(); ?>
		<div class="cwpc-configurator" data-schema='<?php echo esc_attr( wp_json_encode( $schema, CWPC_JSON_FLAGS ) ); ?>' data-product-id="<?php echo esc_attr( $product_id ); ?>" data-hide-add-to-cart="<?php echo esc_attr( get_post_meta( $product_id, '_cwpc_hide_add_to_cart', true ) ); ?>">
			<div class="cwpc-preview"><canvas width="720" height="520" aria-label="Product preview"></canvas></div>
			<div class="cwpc-panel"><h3><?php esc_html_e( 'Customize your product', 'custom-wc-product-configurator' ); ?></h3><div class="cwpc-fields"></div><div class="cwpc-price"></div><button type="button" class="button cwpc-reset">Reset</button></div>
			<input type="hidden" name="cwpc_configuration" class="cwpc-configuration" value=""><input type="hidden" name="cwpc_preview_image" class="cwpc-preview-image" value="">
		</div><?php return ob_get_clean();
	}

	private function merge_product_schema( $schema, $product_id ) {
		if ( empty( $schema['layers'] ) || ! is_array( $schema['layers'] ) ) { $schema['layers'] = array(); }
		$base_id = absint( get_post_meta( $product_id, '_cwpc_base_preview_image_id', true ) );
		$base = $base_id ? wp_get_attachment_image_url( $base_id, 'full' ) : get_post_meta( $product_id, '_cwpc_base_preview_image', true );
		if ( $base ) { array_unshift( $schema['layers'], array( 'section' => 'layer', 'id' => 'product_base_preview', 'title' => 'Product Base Preview', 'type' => 'image', 'enabled' => 'yes', 'image' => esc_url_raw( $base ), 'image_id' => $base_id, 'order' => 0, 'options' => array() ) ); }
		$options = json_decode( get_post_meta( $product_id, '_cwpc_product_options', true ), true );
		if ( ! is_array( $options ) ) { return $schema; }
		$choice_options = array();
		foreach ( $options as $index => $option ) {
			if ( 'no' === ( $option['enabled'] ?? 'yes' ) ) { continue; }
			$type = sanitize_key( $option['type'] ?? 'image' );
			$id = 'product_option_' . $index;
			if ( in_array( $type, array( 'color', 'image' ), true ) ) {
				$choice_options[] = array( 'id' => $id, 'title' => CWPC_Plugin::sanitize_utf8_text( $option['title'] ?? 'Product option' ), 'label' => CWPC_Plugin::sanitize_utf8_text( $option['title'] ?? 'Product option' ), 'color' => sanitize_hex_color( $option['color'] ?? '' ), 'image' => $this->image_url_from_row( $option, 'image' ), 'image_id' => absint( $option['image_id'] ?? 0 ), 'layer_image' => $this->image_url_from_row( $option, 'image' ), 'price' => (float) ( $option['price'] ?? 0 ), 'order' => (float) ( $option['order'] ?? 0 ), 'enabled' => 'yes' );
			} elseif ( 'text' === $type ) {
				$schema['layers'][] = array( 'section' => 'text', 'id' => $id, 'title' => CWPC_Plugin::sanitize_utf8_text( $option['title'] ?? 'Product text' ), 'type' => 'text', 'enabled' => 'yes', 'price' => (float) ( $option['price'] ?? 0 ), 'order' => (float) ( $option['order'] ?? 0 ), 'placeholder' => CWPC_Plugin::sanitize_utf8_text( $option['title'] ?? '' ) );
			} elseif ( 'upload' === $type ) {
				$schema['layers'][] = array( 'section' => 'upload', 'id' => $id, 'title' => CWPC_Plugin::sanitize_utf8_text( $option['title'] ?? 'Product upload' ), 'type' => 'upload', 'enabled' => 'yes', 'price' => (float) ( $option['price'] ?? 0 ), 'order' => (float) ( $option['order'] ?? 0 ), 'allowed_types' => 'jpg,png,gif,webp', 'max_size' => 5 );
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
