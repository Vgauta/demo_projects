<?php
defined( 'ABSPATH' ) || exit;

class CWPC_Post_Types {
	public static function register() {
		register_post_type( 'cwpc_configurator', array(
			'labels' => array(
				'name' => __( 'Configurators', 'custom-wc-product-configurator' ),
				'singular_name' => __( 'Configurator', 'custom-wc-product-configurator' ),
				'add_new_item' => __( 'Add Configurator', 'custom-wc-product-configurator' ),
				'edit_item' => __( 'Edit Configurator', 'custom-wc-product-configurator' ),
			),
			'public' => false,
			'show_ui' => true,
			'show_in_menu' => false,
			'menu_icon' => 'dashicons-art',
			'supports' => array( 'title' ),
			'capability_type' => 'post',
			'show_in_rest' => false,
		) );
	}
}
