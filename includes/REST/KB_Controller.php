<?php
/**
 * Documentation project REST controller.
 *
 * @package ItsDZ\Doczur\REST
 */

namespace ItsDZ\Doczur\REST;

use ItsDZ\Doczur\PostTypes\KB_Post_Type;

defined( 'ABSPATH' ) || exit;

/**
 * Handles admin CRUD for documentation projects.
 */
final class KB_Controller extends REST_Controller {
	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'kb';

	/**
	 * Register project routes.
	 *
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => array( $this, 'can_manage' ),
				),
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_item' ),
					'permission_callback' => array( $this, 'can_manage' ),
					'args'                => $this->write_args( true ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>\d+)',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_item' ),
					'permission_callback' => array( $this, 'can_manage' ),
				),
				array(
					'methods'             => \WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_item' ),
					'permission_callback' => array( $this, 'can_manage' ),
					'args'                => $this->write_args( false ),
				),
				array(
					'methods'             => \WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_item' ),
					'permission_callback' => array( $this, 'can_manage' ),
				),
			)
		);
	}

	/**
	 * List projects.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	public function get_items( $request ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
		$posts = get_posts(
			array(
				'orderby'        => 'date',
				'order'          => 'DESC',
				'posts_per_page' => 100,
				'post_status'    => array( 'draft', 'publish' ),
				'post_type'      => KB_Post_Type::POST_TYPE,
			)
		);

		return rest_ensure_response( array_map( array( $this, 'prepare_item' ), $posts ) );
	}

	/**
	 * Get one project.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function get_item( $request ) {
		$post = $this->get_post( $request['id'], KB_Post_Type::POST_TYPE );

		return is_wp_error( $post ) ? $post : rest_ensure_response( $this->prepare_item( $post ) );
	}

	/**
	 * Create the single Free project.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function create_item( $request ) {
		$maximum  = (int) apply_filters( 'itsdz_max_documentation_projects', 1 );
		$existing = get_posts(
			array(
				'fields'         => 'ids',
				'posts_per_page' => $maximum > 0 ? $maximum : 1,
				'post_status'    => array( 'draft', 'publish', 'pending', 'private' ),
				'post_type'      => KB_Post_Type::POST_TYPE,
			)
		);

		if ( $maximum > 0 && count( $existing ) >= $maximum ) {
			return new \WP_Error(
				'itsdz_kb_limit_reached',
				__( 'The Free version supports one documentation project.', 'doczur' ),
				array( 'status' => 403 )
			);
		}

		$post_id = wp_insert_post( $this->post_data( $request ), true );

		if ( is_wp_error( $post_id ) ) {
			return $post_id;
		}

		$this->update_meta( $post_id, $request->get_param( 'meta' ) );

		$response = rest_ensure_response( $this->prepare_item( get_post( $post_id ) ) );
		$response->set_status( 201 );

		return $response;
	}

	/**
	 * Update one project.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function update_item( $request ) {
		$post = $this->get_post( $request['id'], KB_Post_Type::POST_TYPE );

		if ( is_wp_error( $post ) ) {
			return $post;
		}

		$data       = $this->post_data( $request, $post );
		$data['ID'] = $post->ID;
		$result     = wp_update_post( $data, true );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$this->update_meta( $post->ID, $request->get_param( 'meta' ) );

		return rest_ensure_response( $this->prepare_item( get_post( $post->ID ) ) );
	}

	/**
	 * Trash or permanently delete a project.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function delete_item( $request ) {
		$post = $this->get_post( $request['id'], KB_Post_Type::POST_TYPE );

		if ( is_wp_error( $post ) ) {
			return $post;
		}

		$force   = rest_sanitize_boolean( $request->get_param( 'force' ) );
		$deleted = $force ? wp_delete_post( $post->ID, true ) : wp_trash_post( $post->ID );

		if ( ! $deleted ) {
			return new \WP_Error( 'itsdz_delete_failed', __( 'The documentation project could not be deleted.', 'doczur' ), array( 'status' => 500 ) );
		}

		return rest_ensure_response(
			array(
				'deleted' => true,
				'id'      => $post->ID,
			)
		);
	}

	/**
	 * Format a project for REST output.
	 *
	 * @param \WP_Post $post Project object.
	 * @return array<string, mixed>
	 */
	public function prepare_item( $post ) {
		return array(
			'id'      => $post->ID,
			'title'   => get_the_title( $post ),
			'content' => $post->post_content,
			'slug'    => $post->post_name,
			'status'  => $post->post_status,
			'url'     => get_permalink( $post ),
			'meta'    => $this->get_meta( $post->ID ),
		);
	}

	/**
	 * Build safe post data.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @param \WP_Post|null    $existing Existing project.
	 * @return array<string, mixed>
	 */
	private function post_data( $request, $existing = null ) {
		return array(
			'post_content' => null !== $request->get_param( 'content' ) ? wp_kses_post( $request->get_param( 'content' ) ) : ( $existing ? $existing->post_content : '' ),
			'post_status'  => $this->sanitize_status( $request->get_param( 'status' ), $existing ? $existing->post_status : 'draft' ),
			'post_title'   => null !== $request->get_param( 'title' ) ? sanitize_text_field( $request->get_param( 'title' ) ) : ( $existing ? $existing->post_title : '' ),
			'post_type'    => KB_Post_Type::POST_TYPE,
		);
	}

	/**
	 * Update allowlisted project meta.
	 *
	 * @param int   $post_id Project ID.
	 * @param mixed $meta    Raw meta object.
	 * @return void
	 */
	private function update_meta( $post_id, $meta ) {
		if ( ! is_array( $meta ) ) {
			return;
		}

		foreach ( array_keys( $this->get_meta( $post_id ) ) as $key ) {
			if ( array_key_exists( $key, $meta ) ) {
				update_post_meta( $post_id, $key, $meta[ $key ] );
			}
		}
	}

	/**
	 * Read project meta.
	 *
	 * @param int $post_id Project ID.
	 * @return array<string, mixed>
	 */
	private function get_meta( $post_id ) {
		$keys = array( '_itsdz_kb_logo', '_itsdz_kb_brand_color', '_itsdz_kb_theme_mode', '_itsdz_kb_template', '_itsdz_kb_doc_type', '_itsdz_kb_slug_base', '_itsdz_kb_layout_mode', '_itsdz_kb_active_version' );
		$meta = array();

		foreach ( $keys as $key ) {
			$meta[ $key ] = get_post_meta( $post_id, $key, true );
		}

		return $meta;
	}

	/**
	 * Route arguments for project writes.
	 *
	 * @param bool $title_required Whether title is required.
	 * @return array<string, array<string, mixed>>
	 */
	private function write_args( $title_required ) {
		return array(
			'title'   => array(
				'required'          => $title_required,
				'sanitize_callback' => 'sanitize_text_field',
				'type'              => 'string',
			),
			'content' => array( 'type' => 'string' ),
			'status'  => array(
				'enum' => array( 'draft', 'publish' ),
				'type' => 'string',
			),
			'meta'    => array( 'type' => 'object' ),
		);
	}
}
