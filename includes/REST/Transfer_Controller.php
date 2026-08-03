<?php
/**
 * JSON import and export REST controller.
 *
 * @package ItsDZ\Doczur\REST
 */

namespace ItsDZ\Doczur\REST;

use ItsDZ\Doczur\PostTypes\Article_Post_Type;
use ItsDZ\Doczur\Utils\Content_Validator;

defined( 'ABSPATH' ) || exit;

/**
 * Transfers portable Free-format documentation JSON.
 */
final class Transfer_Controller extends REST_Controller {
	/**
	 * Register transfer routes.
	 *
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/export',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'export' ),
				'permission_callback' => array( $this, 'can_manage' ),
				'args'                => array(
					'kb_id' => array(
						'required' => true,
						'type'     => 'integer',
					),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/import',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'import' ),
				'permission_callback' => array( $this, 'can_manage' ),
				'args'                => array(
					'kb_id'    => array(
						'required' => true,
						'type'     => 'integer',
					),
					'articles' => array(
						'required' => true,
						'type'     => 'array',
					),
				),
			)
		);
	}

	/**
	 * Export one project and its articles.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function export( $request ) {
		$kb_id = absint( $request->get_param( 'kb_id' ) );
		$kb    = Content_Validator::get_kb( $kb_id );

		if ( is_wp_error( $kb ) ) {
			return $kb;
		}

		$article_ids = get_posts(
			array(
				'fields'         => 'ids',
				'meta_key'       => '_itsdz_kb_id', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value'     => $kb_id, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
				'orderby'        => array(
			'menu_order' => 'ASC',
			'date'       => 'ASC',
			),
				'posts_per_page' => -1,
				'post_status'    => array( 'draft', 'publish' ),
				'post_type'      => Article_Post_Type::POST_TYPE,
			)
		);
		$articles    = array();

		foreach ( $article_ids as $article_id ) {
			$article    = get_post( $article_id );
			$sections   = wp_get_object_terms( $article_id, 'itsdz_section' );
			$tags       = wp_get_object_terms( $article_id, 'itsdz_tag', array( 'fields' => 'names' ) );
			$articles[] = array(
				'title'      => $article->post_title,
				'content'    => $article->post_content,
				'slug'       => $article->post_name,
				'status'     => $article->post_status,
				'menu_order' => (int) $article->menu_order,
				'sections'   => is_wp_error( $sections ) ? array() : array_values( array_filter( array_map( array( $this, 'get_section_path' ), $sections ) ) ),
				'tags'       => is_wp_error( $tags ) ? array() : $tags,
			);
		}

		$response = rest_ensure_response(
			array(
				'format'      => 'doczur-json',
				'version'     => 2,
				'exported_at' => current_time( 'mysql', true ),
				'project'     => array(
					'id'    => $kb->ID,
					'title' => $kb->post_title,
					'slug'  => $kb->post_name,
				),
				'articles'    => $articles,
			)
		);
		$response->header( 'Content-Disposition', 'attachment; filename="doczur-export-' . $kb_id . '.json"' );

		return $response;
	}

	/**
	 * Import articles as drafts into an existing project.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function import( $request ) {
		$kb_id    = absint( $request->get_param( 'kb_id' ) );
		$kb       = Content_Validator::get_kb( $kb_id );
		$articles = $request->get_param( 'articles' );

		if ( is_wp_error( $kb ) ) {
			return $kb;
		}

		if ( ! is_array( $articles ) || count( $articles ) > 500 ) {
			return new \WP_Error( 'itsdz_import_invalid', __( 'The import must contain no more than 500 articles.', 'doczur' ), array( 'status' => 400 ) );
		}

		$section_paths = array();

		foreach ( $articles as $index => $article ) {
			if ( ! is_array( $article ) || empty( $article['title'] ) || ! is_scalar( $article['title'] ) ) {
				return new \WP_Error( 'itsdz_import_invalid_article', __( 'Every imported article must have a title.', 'doczur' ), array( 'status' => 400 ) );
			}

			$paths = $this->normalize_section_paths( isset( $article['sections'] ) ? $article['sections'] : array() );

			if ( null === $paths ) {
				return new \WP_Error( 'itsdz_import_invalid_section', __( 'Every section path must contain between one and three valid levels.', 'doczur' ), array( 'status' => 400 ) );
			}

			$section_paths[ $index ] = $paths;
		}

		$created            = array();
		$section_ids        = array();
		$section_term_cache = array();

		foreach ( $section_paths as $index => $paths ) {
			$section_ids[ $index ] = array();

			foreach ( $paths as $path ) {
				$term_id = $this->resolve_section_path( $path, $section_term_cache );

				if ( is_wp_error( $term_id ) ) {
					return $term_id;
				}

				$section_ids[ $index ][] = $term_id;
			}
		}

		foreach ( $articles as $index => $article ) {
			$post_id = wp_insert_post(
				array(
					'menu_order'   => isset( $article['menu_order'] ) ? absint( $article['menu_order'] ) : 0,
					'post_content' => isset( $article['content'] ) && is_scalar( $article['content'] ) ? wp_kses_post( $article['content'] ) : '',
					'post_status'  => 'draft',
					'post_title'   => sanitize_text_field( $article['title'] ),
					'post_type'    => Article_Post_Type::POST_TYPE,
				),
				true
			);

			if ( is_wp_error( $post_id ) ) {
				continue;
			}

			update_post_meta( $post_id, '_itsdz_kb_id', $kb_id );

			if ( ! empty( $section_ids[ $index ] ) ) {
				wp_set_object_terms( $post_id, $section_ids[ $index ], 'itsdz_section' );
			}

			if ( ! empty( $article['tags'] ) && is_array( $article['tags'] ) ) {
				wp_set_object_terms( $post_id, $this->sanitize_term_names( $article['tags'] ), 'itsdz_tag' );
			}

			$created[] = $post_id;
		}

		$response = rest_ensure_response(
			array(
				'created'     => count( $created ),
				'article_ids' => $created,
			)
		);
		$response->set_status( 201 );

		return $response;
	}

	/**
	 * Build a portable root-to-leaf path for a section.
	 *
	 * @param \WP_Term $term Section term.
	 * @return string[]
	 */
	private function get_section_path( $term ) {
		$path = array();
		$seen = array();

		while ( $term instanceof \WP_Term && ! isset( $seen[ $term->term_id ] ) ) {
			$seen[ $term->term_id ] = true;
			array_unshift( $path, $term->name );

			if ( ! $term->parent ) {
				break;
			}

			$term = get_term( $term->parent, 'itsdz_section' );
		}

		return $path;
	}

	/**
	 * Normalize version-2 paths and legacy version-1 flat section names.
	 *
	 * @param mixed $sections Raw article sections.
	 * @return array<int, string[]>|null
	 */
	private function normalize_section_paths( $sections ) {
		if ( ! is_array( $sections ) ) {
			return null;
		}

		$paths = array();

		foreach ( $sections as $section ) {
			$raw_path = is_scalar( $section ) ? array( $section ) : $section;

			if ( ! is_array( $raw_path ) || empty( $raw_path ) || count( $raw_path ) > 3 ) {
				return null;
			}

			$path = array();

			foreach ( $raw_path as $level ) {
				if ( ! is_scalar( $level ) ) {
					return null;
				}

				$level = sanitize_text_field( $level );

				if ( '' === $level ) {
					return null;
				}

				$path[] = $level;
			}

			$paths[ wp_json_encode( $path ) ] = $path;
		}

		return array_values( $paths );
	}

	/**
	 * Resolve or create a validated section path.
	 *
	 * @param string[]          $path  Root-to-leaf names.
	 * @param array<string,int> $cache Per-import term cache.
	 * @return int|\WP_Error
	 */
	private function resolve_section_path( $path, &$cache ) {
		$parent = 0;

		foreach ( $path as $name ) {
			$cache_key = $parent . ':' . sanitize_title( $name );

			if ( isset( $cache[ $cache_key ] ) ) {
				$parent = $cache[ $cache_key ];
				continue;
			}

			$existing = term_exists( $name, 'itsdz_section', $parent );

			if ( $existing ) {
				$term_id = is_array( $existing ) ? absint( $existing['term_id'] ) : absint( $existing );
			} else {
				$inserted = wp_insert_term( $name, 'itsdz_section', array( 'parent' => $parent ) );

				if ( is_wp_error( $inserted ) ) {
					$term_id = absint( $inserted->get_error_data( 'term_exists' ) );

					if ( ! $term_id ) {
						return $inserted;
					}
				} else {
					$term_id = absint( $inserted['term_id'] );
				}
			}

			$cache[ $cache_key ] = $term_id;
			$parent              = $term_id;
		}

		return $parent;
	}

	/**
	 * Sanitize portable term names while discarding nested values.
	 *
	 * @param array $names Raw term names.
	 * @return string[]
	 */
	private function sanitize_term_names( $names ) {
		$sanitized = array();

		foreach ( $names as $name ) {
			if ( is_scalar( $name ) ) {
				$name = sanitize_text_field( $name );

				if ( '' !== $name ) {
					$sanitized[] = $name;
				}
			}
		}

		return array_values( array_unique( $sanitized ) );
	}
}
