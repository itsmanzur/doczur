<?php
/**
 * Public documentation feedback REST controller.
 *
 * @package ItsDZ\Doczur\REST
 */

namespace ItsDZ\Doczur\REST;

use ItsDZ\Doczur\Core\Migrations\Migration_1_0_0;
use ItsDZ\Doczur\PostTypes\Article_Post_Type;
use ItsDZ\Doczur\Security\Rate_Limiter;
use ItsDZ\Doczur\Utils\Content_Validator;

defined( 'ABSPATH' ) || exit;

/**
 * Stores privacy-conscious helpfulness feedback.
 */
final class Feedback_Controller extends REST_Controller {
	/**
	 * Register feedback route.
	 *
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/feedback',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'create_feedback' ),
				'permission_callback' => array( $this, 'allow_public' ),
				'args'                => array(
					'article_id' => array(
						'required' => true,
						'type'     => 'integer',
					),
					'helpful'    => array(
						'required' => true,
						'type'     => 'boolean',
					),
					'comment'    => array(
						'default' => '',
						'type'    => 'string',
					),
				),
			)
		);
	}

	/**
	 * Store feedback for a public article.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function create_feedback( $request ) {
		global $wpdb;

		$limiter    = new Rate_Limiter();
		$rate_limit = $limiter->consume( 'feedback', 5, MINUTE_IN_SECONDS );

		if ( is_wp_error( $rate_limit ) ) {
			return $rate_limit;
		}

		$article_id = absint( $request->get_param( 'article_id' ) );
		$article    = get_post( $article_id );
		$kb_id      = absint( get_post_meta( $article_id, '_itsdz_kb_id', true ) );

		if ( ! $article || Article_Post_Type::POST_TYPE !== $article->post_type || 'publish' !== $article->post_status || is_wp_error( Content_Validator::get_kb( $kb_id, true ) ) ) {
			return new \WP_Error( 'itsdz_feedback_article_not_found', __( 'The article is not publicly available.', 'itsmanzur-docs' ), array( 'status' => 404 ) );
		}

		$duplicate_key = 'itsdz_feedback_' . md5( $article_id . '|' . $limiter->get_ip_hash() );

		if ( get_transient( $duplicate_key ) ) {
			return new \WP_Error( 'itsdz_feedback_duplicate', __( 'Feedback has already been submitted for this article.', 'itsmanzur-docs' ), array( 'status' => 409 ) );
		}

		$comment = sanitize_textarea_field( $request->get_param( 'comment' ) );
		$comment = mb_substr( $comment, 0, 1000 );
		$table   = Migration_1_0_0::table_names()['feedback'];
		$sql     = $wpdb->prepare(
			'INSERT INTO %i (article_id, kb_id, helpful, comment, ip_hash, created_at) VALUES (%d, %d, %d, %s, %s, %s)',
			$table,
			$article_id,
			$kb_id,
			rest_sanitize_boolean( $request->get_param( 'helpful' ) ) ? 1 : 0,
			$comment,
			$limiter->get_ip_hash(),
			current_time( 'mysql', true )
		);

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$inserted = $wpdb->query( $sql );

		if ( false === $inserted ) {
			return new \WP_Error( 'itsdz_feedback_failed', __( 'Feedback could not be saved.', 'itsmanzur-docs' ), array( 'status' => 500 ) );
		}

		set_transient( $duplicate_key, '1', 12 * HOUR_IN_SECONDS );
		$response = rest_ensure_response( array( 'created' => true ) );
		$response->set_status( 201 );

		return $response;
	}
}
