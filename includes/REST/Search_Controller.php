<?php
/**
 * Public search REST controller.
 *
 * @package ItsDZ\Doczur\REST
 */

namespace ItsDZ\Doczur\REST;

use ItsDZ\Doczur\Search\Search_Service;
use ItsDZ\Doczur\Security\Rate_Limiter;

defined( 'ABSPATH' ) || exit;

/**
 * Exposes rate-limited cached documentation search.
 */
final class Search_Controller extends REST_Controller {
	/**
	 * Register search route.
	 *
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/search',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'search' ),
				'permission_callback' => array( $this, 'allow_public' ),
				'args'                => array(
					'kb_id' => array(
						'required' => true,
						'type'     => 'integer',
					),
					'q'     => array(
						'required' => true,
						'type'     => 'string',
					),
					'limit' => array(
						'default' => 10,
						'maximum' => 20,
						'minimum' => 1,
						'type'    => 'integer',
					),
				),
			)
		);
	}

	/**
	 * Search one public project.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function search( $request ) {
		$rate_limit = ( new Rate_Limiter() )->consume( 'search', 30, MINUTE_IN_SECONDS );

		if ( is_wp_error( $rate_limit ) ) {
			return $rate_limit;
		}

		$results = ( new Search_Service() )->search(
			$request->get_param( 'kb_id' ),
			$request->get_param( 'q' ),
			$request->get_param( 'limit' )
		);

		if ( is_wp_error( $results ) ) {
			return $results;
		}

		$response = rest_ensure_response(
			array(
				'count'   => count( $results ),
				'results' => $results,
			)
		);
		$response->header( 'Cache-Control', 'public, max-age=60, stale-while-revalidate=120' );

		return $response;
	}
}
