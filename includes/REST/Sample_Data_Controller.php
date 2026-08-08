<?php
/**
 * Sample content REST endpoints.
 *
 * @package ItsDZ\Doczur\REST
 */

namespace ItsDZ\Doczur\REST;

use ItsDZ\Doczur\Utils\Content_Validator;
use ItsDZ\Doczur\Utils\Sample_Data;

defined( 'ABSPATH' ) || exit;

/**
 * Creates and removes the bundled demo documentation.
 */
final class Sample_Data_Controller extends REST_Controller {

	/**
	 * Register sample data routes.
	 *
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/sample-data',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'status' ),
					'permission_callback' => array( $this, 'can_manage' ),
					'args'                => $this->kb_arg(),
				),
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create' ),
					'permission_callback' => array( $this, 'can_manage' ),
					'args'                => $this->kb_arg(),
				),
				array(
					'methods'             => \WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'remove' ),
					'permission_callback' => array( $this, 'can_manage' ),
					'args'                => $this->kb_arg(),
				),
			)
		);
	}

	/**
	 * Report whether the project already holds sample content.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function status( $request ) {
		$kb = $this->resolve_kb( $request );

		if ( is_wp_error( $kb ) ) {
			return $kb;
		}

		return rest_ensure_response( array( 'exists' => Sample_Data::exists( $kb->ID ) ) );
	}

	/**
	 * Generate the sample articles and sections.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function create( $request ) {
		$kb = $this->resolve_kb( $request );

		if ( is_wp_error( $kb ) ) {
			return $kb;
		}

		$result = Sample_Data::create( $kb->ID );

		return rest_ensure_response(
			array(
				'articles' => $result['articles'],
				'exists'   => true,
				'sections' => $result['sections'],
			)
		);
	}

	/**
	 * Delete previously generated sample content.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function remove( $request ) {
		$kb = $this->resolve_kb( $request );

		if ( is_wp_error( $kb ) ) {
			return $kb;
		}

		$result = Sample_Data::remove( $kb->ID );

		return rest_ensure_response(
			array(
				'articles' => $result['articles'],
				'exists'   => false,
				'sections' => $result['sections'],
			)
		);
	}

	/**
	 * Shared project argument definition.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	private function kb_arg() {
		return array(
			'kb_id' => array(
				'required' => true,
				'type'     => 'integer',
			),
		);
	}

	/**
	 * Validate the requested project.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_Post|\WP_Error
	 */
	private function resolve_kb( $request ) {
		return Content_Validator::get_kb( absint( $request->get_param( 'kb_id' ) ) );
	}
}
