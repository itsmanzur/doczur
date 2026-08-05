<?php
/**
 * Documentation article REST controller.
 *
 * @package ItsDZ\Doczur\REST
 */

namespace ItsDZ\Doczur\REST;

use ItsDZ\Doczur\PostTypes\Article_Post_Type;
use ItsDZ\Doczur\Utils\Content_Validator;

defined( 'ABSPATH' ) || exit;

/**
 * Handles article CRUD, relationships, and ordering.
 */
final class Article_Controller extends REST_Controller {
	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'articles';

	/**
	 * Register article routes.
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
			'/' . $this->rest_base . '/reorder',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'reorder_items' ),
				'permission_callback' => array( $this, 'can_manage' ),
				'args'                => array(
					'kb_id' => array(
						'required' => true,
						'type'     => 'integer',
					),
					'items' => array(
						'required' => true,
						'type'     => 'array',
					),
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
	 * List articles, optionally scoped to one project.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function get_items( $request ) {
		$args  = array(
			'orderby'        => array(
				'menu_order' => 'ASC',
				'date'       => 'DESC',
			),
			'posts_per_page' => 100,
			'post_status'    => array( 'draft', 'publish' ),
			'post_type'      => Article_Post_Type::POST_TYPE,
		);
		$kb_id = absint( $request->get_param( 'kb_id' ) );

		if ( $kb_id ) {
			$kb = Content_Validator::get_kb( $kb_id );

			if ( is_wp_error( $kb ) ) {
				return $kb;
			}

			$args['meta_key']   = '_itsdz_kb_id'; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			$args['meta_value'] = $kb_id; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
		}

		$posts = get_posts( $args );

		return rest_ensure_response( array_map( array( $this, 'prepare_item' ), $posts ) );
	}

	/**
	 * Get one article.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function get_item( $request ) {
		$post = $this->get_post( $request['id'], Article_Post_Type::POST_TYPE );

		return is_wp_error( $post ) ? $post : rest_ensure_response( $this->prepare_item( $post ) );
	}

	/**
	 * Create an article with a validated project relationship.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function create_item( $request ) {
		$kb_id = absint( $request->get_param( 'kb_id' ) );
		$kb    = Content_Validator::get_kb( $kb_id );

		if ( is_wp_error( $kb ) ) {
			return $kb;
		}

		$term_validation = $this->validate_terms( $request );

		if ( is_wp_error( $term_validation ) ) {
			return $term_validation;
		}

		$post_id = wp_insert_post( $this->post_data( $request ), true );

		if ( is_wp_error( $post_id ) ) {
			return $post_id;
		}

		update_post_meta( $post_id, '_itsdz_kb_id', $kb_id );
		$this->update_optional_meta( $post_id, $request );
		$this->update_terms( $post_id, $request );

		$response = rest_ensure_response( $this->prepare_item( get_post( $post_id ) ) );
		$response->set_status( 201 );

		return $response;
	}

	/**
	 * Update an article.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function update_item( $request ) {
		$post = $this->get_post( $request['id'], Article_Post_Type::POST_TYPE );

		if ( is_wp_error( $post ) ) {
			return $post;
		}

		$kb_id = null !== $request->get_param( 'kb_id' ) ? absint( $request->get_param( 'kb_id' ) ) : absint( get_post_meta( $post->ID, '_itsdz_kb_id', true ) );
		$kb    = Content_Validator::get_kb( $kb_id );

		if ( is_wp_error( $kb ) ) {
			return $kb;
		}

		$term_validation = $this->validate_terms( $request );

		if ( is_wp_error( $term_validation ) ) {
			return $term_validation;
		}

		$data       = $this->post_data( $request, $post );
		$data['ID'] = $post->ID;
		$result     = wp_update_post( $data, true );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		update_post_meta( $post->ID, '_itsdz_kb_id', $kb_id );
		$this->update_optional_meta( $post->ID, $request );
		$this->update_terms( $post->ID, $request );

		return rest_ensure_response( $this->prepare_item( get_post( $post->ID ) ) );
	}

	/**
	 * Trash or delete an article.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function delete_item( $request ) {
		$post = $this->get_post( $request['id'], Article_Post_Type::POST_TYPE );

		if ( is_wp_error( $post ) ) {
			return $post;
		}

		$force   = rest_sanitize_boolean( $request->get_param( 'force' ) );
		$deleted = $force ? wp_delete_post( $post->ID, true ) : wp_trash_post( $post->ID );

		if ( ! $deleted ) {
			return new \WP_Error( 'itsdz_delete_failed', __( 'The article could not be deleted.', 'doczur' ), array( 'status' => 500 ) );
		}

		return rest_ensure_response(
			array(
				'deleted' => true,
				'id'      => $post->ID,
			)
		);
	}

	/**
	 * Atomically validate, then apply menu order and section updates.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function reorder_items( $request ) {
		$kb_id = absint( $request->get_param( 'kb_id' ) );
		$items = $request->get_param( 'items' );

		if ( is_wp_error( Content_Validator::get_kb( $kb_id ) ) || ! is_array( $items ) || count( $items ) > 500 ) {
			return new \WP_Error( 'itsdz_invalid_reorder', __( 'The reorder payload is invalid.', 'doczur' ), array( 'status' => 400 ) );
		}

		$validated = array();

		foreach ( $items as $item ) {
			$article_id = isset( $item['id'] ) ? absint( $item['id'] ) : 0;
			$article    = $this->get_post( $article_id, Article_Post_Type::POST_TYPE );
			$section_id = isset( $item['section_id'] ) ? absint( $item['section_id'] ) : 0;

			if ( is_wp_error( $article ) || absint( get_post_meta( $article_id, '_itsdz_kb_id', true ) ) !== $kb_id || ( $section_id && ! $this->valid_section( $section_id ) ) ) {
				return new \WP_Error( 'itsdz_invalid_reorder_item', __( 'One or more reorder items are invalid.', 'doczur' ), array( 'status' => 400 ) );
			}

			$validated[] = array(
				'id'         => $article_id,
				'menu_order' => isset( $item['menu_order'] ) ? absint( $item['menu_order'] ) : 0,
				'section_id' => $section_id,
			);
		}

		foreach ( $validated as $item ) {
			wp_update_post(
				array(
					'ID'         => $item['id'],
					'menu_order' => $item['menu_order'],
				)
			);
			wp_set_object_terms( $item['id'], $item['section_id'] ? array( $item['section_id'] ) : array(), 'itsdz_section' );
		}

		return rest_ensure_response( array( 'updated' => count( $validated ) ) );
	}

	/**
	 * Format one article.
	 *
	 * @param \WP_Post $post Article object.
	 * @return array<string, mixed>
	 */
	public function prepare_item( $post ) {
		$versions = wp_get_object_terms( $post->ID, 'itsdz_version', array( 'fields' => 'ids' ) );

		return array(
			'id'           => $post->ID,
			'kb_id'        => absint( get_post_meta( $post->ID, '_itsdz_kb_id', true ) ),
			'title'        => get_the_title( $post ),
			'content'      => $post->post_content,
			'slug'         => $post->post_name,
			'status'       => $post->post_status,
			'menu_order'   => (int) $post->menu_order,
			'reading_time' => absint( get_post_meta( $post->ID, '_itsdz_reading_time', true ) ),
			'section_ids'  => wp_get_object_terms( $post->ID, 'itsdz_section', array( 'fields' => 'ids' ) ),
			'tag_ids'      => wp_get_object_terms( $post->ID, 'itsdz_tag', array( 'fields' => 'ids' ) ),
			'version_id'   => ! is_wp_error( $versions ) && ! empty( $versions ) ? $versions[0] : 0,
			'url'          => 'publish' === $post->post_status ? get_permalink( $post ) : ( get_preview_post_link( $post ) ? get_preview_post_link( $post ) : get_permalink( $post ) ),
		);
	}

	/**
	 * Build safe article post data.
	 *
	 * @param \WP_REST_Request $request  Request object.
	 * @param \WP_Post|null    $existing Existing article.
	 * @return array<string, mixed>
	 */
	private function post_data( $request, $existing = null ) {
		return array(
			'menu_order'   => null !== $request->get_param( 'menu_order' ) ? absint( $request->get_param( 'menu_order' ) ) : ( $existing ? (int) $existing->menu_order : 0 ),
			'post_content' => null !== $request->get_param( 'content' ) ? Content_Validator::sanitize_content( $request->get_param( 'content' ) ) : ( $existing ? $existing->post_content : '' ),
			'post_status'  => $this->sanitize_status( $request->get_param( 'status' ), $existing ? $existing->post_status : 'draft' ),
			'post_title'   => null !== $request->get_param( 'title' ) ? sanitize_text_field( $request->get_param( 'title' ) ) : ( $existing ? $existing->post_title : '' ),
			'post_type'    => Article_Post_Type::POST_TYPE,
		);
	}

	/**
	 * Update optional workflow-ready meta.
	 *
	 * @param int              $post_id Article ID.
	 * @param \WP_REST_Request $request Request object.
	 * @return void
	 */
	private function update_optional_meta( $post_id, $request ) {
		foreach ( array( '_itsdz_last_reviewed', '_itsdz_owner' ) as $key ) {
			if ( null !== $request->get_param( $key ) ) {
				update_post_meta( $post_id, $key, $request->get_param( $key ) );
			}
		}
	}

	/**
	 * Update article section and tag relationships.
	 *
	 * @param int              $post_id Article ID.
	 * @param \WP_REST_Request $request Request object.
	 * @return void
	 */
	private function update_terms( $post_id, $request ) {
		foreach ( array(
			'section_ids' => 'itsdz_section',
			'tag_ids'     => 'itsdz_tag',
		) as $parameter => $taxonomy ) {
			$term_ids = $request->get_param( $parameter );

			if ( is_array( $term_ids ) ) {
				wp_set_object_terms( $post_id, array_map( 'absint', $term_ids ), $taxonomy );
			}
		}

		$version_id = $request->get_param( 'version_id' );
		if ( null !== $version_id ) {
			wp_set_object_terms( $post_id, $version_id ? array( absint( $version_id ) ) : array(), 'itsdz_version' );
		}
	}

	/**
	 * Validate requested term relationships before mutating an article.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return true|\WP_Error
	 */
	private function validate_terms( $request ) {
		$sections = $request->get_param( 'section_ids' );
		$tags     = $request->get_param( 'tag_ids' );

		if ( is_array( $sections ) ) {
			foreach ( $sections as $section_id ) {
				if ( ! $this->valid_section( absint( $section_id ) ) ) {
					return new \WP_Error( 'itsdz_invalid_section', __( 'One or more sections are invalid or exceed three levels.', 'doczur' ), array( 'status' => 400 ) );
				}
			}
		}

		if ( is_array( $tags ) ) {
			foreach ( $tags as $tag_id ) {
				if ( ! term_exists( absint( $tag_id ), 'itsdz_tag' ) ) {
					return new \WP_Error( 'itsdz_invalid_tag', __( 'One or more documentation tags are invalid.', 'doczur' ), array( 'status' => 400 ) );
				}
			}
		}

		$version_id = $request->get_param( 'version_id' );
		if ( $version_id && ! term_exists( absint( $version_id ), 'itsdz_version' ) ) {
			return new \WP_Error( 'itsdz_invalid_version', __( 'The selected version is invalid.', 'doczur' ), array( 'status' => 400 ) );
		}

		return true;
	}

	/**
	 * Validate a section and enforce the three-level limit.
	 *
	 * @param int $term_id Section term ID.
	 * @return bool
	 */
	private function valid_section( $term_id ) {
		$term  = get_term( $term_id, 'itsdz_section' );
		$depth = 1;

		if ( ! $term || is_wp_error( $term ) ) {
			return false;
		}

		while ( $term->parent ) {
			++$depth;

			if ( $depth > 3 ) {
				return false;
			}

			$term = get_term( $term->parent, 'itsdz_section' );

			if ( ! $term || is_wp_error( $term ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Article write arguments.
	 *
	 * @param bool $required Whether title and project are required.
	 * @return array<string, array<string, mixed>>
	 */
	private function write_args( $required ) {
		return array(
			'title'       => array(
				'required'          => $required,
				'sanitize_callback' => 'sanitize_text_field',
				'type'              => 'string',
			),
			'content'     => array( 'type' => 'string' ),
			'status'      => array(
				'enum' => array( 'draft', 'publish' ),
				'type' => 'string',
			),
			'kb_id'       => array(
				'required' => $required,
				'type'     => 'integer',
			),
			'menu_order'  => array(
				'minimum' => 0,
				'type'    => 'integer',
			),
			'section_ids' => array(
				'items' => array( 'type' => 'integer' ),
				'type'  => 'array',
			),
			'tag_ids'     => array(
				'items' => array( 'type' => 'integer' ),
				'type'  => 'array',
			),
			'version_id'  => array(
				'type' => 'integer',
			),
		);
	}
}
