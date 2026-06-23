<?php
defined( 'ABSPATH' ) || exit;
class CWPC_REST_API {
	public function __construct() { add_action( 'rest_api_init', array( $this, 'routes' ) ); }
	public function routes() { register_rest_route( 'cwpc/v1', '/schema/(?P<id>\d+)', array( 'methods' => 'GET', 'callback' => array( $this, 'schema' ), 'permission_callback' => '__return_true' ) ); }
	public function schema( WP_REST_Request $request ) { $id = absint( $request['id'] ); $schema = json_decode( get_post_meta( $id, '_cwpc_schema', true ), true ); return rest_ensure_response( $schema ?: CWPC_Plugin::default_schema() ); }
}
