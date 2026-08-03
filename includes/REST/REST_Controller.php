<?php
/**
 * Shared Doczur REST controller behavior.
 *
 * @package ItsDZ\Doczur\REST
 */

namespace ItsDZ\Doczur\REST;

use ItsDZ\Doczur\Core\Service;
use ItsDZ\Doczur\Security\Capabilities;

defined( 'ABSPATH' ) || exit;

/**
 * Provides route bootstrapping and permission helpers.
 */
abstract class REST_Controller extends \WP_REST_Controller implements Service {
	/**
	 * REST namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'itsdz/v1';

	/**
	 * Register the controller during REST initialization.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Require Doczur management capability.
	 *
	 * @return true|\WP_Error
	 */
	public function can_manage() {
		if ( current_user_can( Capabilities::MANAGE_DOCS ) ) {
			return true;
		}

		return new \WP_Error(
			'itsdz_rest_forbidden',
			__( 'You are not allowed to manage documentation.', 'doczur' ),
			array( 'status' => rest_authorization_required_code() )
		);
	}

	/**
	 * Explicit public-route permission callback.
	 *
	 * Abuse protection is applied inside each public endpoint.
	 *
	 * @return true
	 */
	public function allow_public() {
		return true;
	}

	/**
	 * Resolve a Doczur post or return a REST error.
	 *
	 * @param int    $post_id   Post ID.
	 * @param string $post_type Expected post type.
	 * @return \WP_Post|\WP_Error
	 */
	protected function get_post( $post_id, $post_type ) {
		$post = get_post( absint( $post_id ) );

		if ( ! $post || $post_type !== $post->post_type || 'trash' === $post->post_status ) {
			return new \WP_Error(
				'itsdz_rest_not_found',
				__( 'The requested documentation item was not found.', 'doczur' ),
				array( 'status' => 404 )
			);
		}

		return $post;
	}

	/**
	 * Allow the Free editing statuses only.
	 *
	 * @param mixed  $status   Raw status.
	 * @param string $fallback Fallback status.
	 * @return string
	 */
	protected function sanitize_status( $status, $fallback = 'draft' ) {
		$status = is_scalar( $status ) ? sanitize_key( $status ) : '';

		return in_array( $status, array( 'draft', 'publish' ), true ) ? $status : $fallback;
	}
}
