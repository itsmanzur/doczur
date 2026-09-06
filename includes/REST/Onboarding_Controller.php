<?php
/**
 * Onboarding wizard REST endpoints.
 *
 * @package ItsDZ\Doczur\REST
 */

namespace ItsDZ\Doczur\REST;

use ItsDZ\Doczur\Admin\Onboarding;
use ItsDZ\Doczur\Security\Capabilities;

defined( 'ABSPATH' ) || exit;

/**
 * Persists wizard UI progress. Never creates, updates, or deletes documentation
 * content except for the optional published page that embeds [nirdeshio_docs].
 */
final class Onboarding_Controller extends REST_Controller {

	/**
	 * Register onboarding routes.
	 *
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/onboarding/status',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_status' ),
					'permission_callback' => array( $this, 'can_onboard' ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/onboarding/complete-step',
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'complete_step' ),
					'permission_callback' => array( $this, 'can_onboard' ),
					'args'                => array(
						'step'             => array(
							'type'              => 'integer',
							'minimum'           => 1,
							'maximum'           => 5,
							'required'          => false,
							'sanitize_callback' => 'absint',
							'validate_callback' => 'rest_validate_request_arg',
						),
						'restart'          => $this->bool_arg(),
						'analytics_opt_in' => $this->bool_arg(),
						'create_page'      => $this->bool_arg(),
					),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/onboarding/skip',
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'skip' ),
					'permission_callback' => array( $this, 'can_onboard' ),
					'args'                => array(
						'analytics_opt_in' => $this->bool_arg(),
					),
				),
			)
		);
	}

	/**
	 * Require a valid wp_rest nonce plus documentation capability.
	 *
	 * Cookie authentication alone is not enough. manage_options is listed
	 * explicitly for this settings-style endpoint; Capabilities::install()
	 * already grants MANAGE_DOCS to every role that has manage_options, so
	 * that first check is redundant on a default install.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return true|\WP_Error
	 */
	public function can_onboard( $request ) {
		if ( ! $this->verify_rest_nonce( $request ) ) {
			return new \WP_Error(
				'itsdz_rest_invalid_nonce',
				__( 'Invalid REST nonce.', 'itsmanzur-docs' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		if ( ! current_user_can( 'manage_options' ) && ! current_user_can( Capabilities::MANAGE_DOCS ) ) {
			return new \WP_Error(
				'itsdz_rest_forbidden',
				__( 'You are not allowed to manage documentation.', 'itsmanzur-docs' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		return true;
	}

	/**
	 * Current wizard progress and opt-in drop-off counts.
	 *
	 * @return \WP_REST_Response
	 */
	public function get_status() {
		return rest_ensure_response( Onboarding::status_payload() );
	}

	/**
	 * Save a finished step, optionally create the shortcode page, or re-open the wizard.
	 *
	 * Restart only resets wizard UI state. Existing sections and articles stay.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function complete_step( $request ) {
		if ( $request->get_param( 'restart' ) ) {
			Onboarding::restart();

			return rest_ensure_response( Onboarding::status_payload() );
		}

		$step = $request->get_param( 'step' );

		if ( null === $step ) {
			return new \WP_Error(
				'itsdz_rest_invalid_step',
				__( 'A wizard step number is required.', 'itsmanzur-docs' ),
				array( 'status' => 400 )
			);
		}

		$opt_in  = $request->get_param( 'analytics_opt_in' );
		$opt_in  = null === $opt_in ? null : (bool) $opt_in;
		$page_id = 0;

		if ( $request->get_param( 'create_page' ) ) {
			$page_id = $this->create_docs_page();

			if ( is_wp_error( $page_id ) ) {
				return $page_id;
			}
		}

		Onboarding::complete_step( (int) $step, $opt_in, (int) $page_id );

		return rest_ensure_response( Onboarding::status_payload() );
	}

	/**
	 * Dismiss the wizard without deleting content.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	public function skip( $request ) {
		$opt_in = $request->get_param( 'analytics_opt_in' );
		Onboarding::skip( null === $opt_in ? null : (bool) $opt_in );

		return rest_ensure_response( Onboarding::status_payload() );
	}

	/**
	 * Confirm the request carries a valid wp_rest nonce.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return bool
	 */
	private function verify_rest_nonce( $request ) {
		$nonce = $request->get_header( 'X-WP-Nonce' );

		if ( ! is_string( $nonce ) || '' === $nonce ) {
			$param = $request->get_param( '_wpnonce' );
			$nonce = is_string( $param ) ? $param : '';
		}

		return (bool) wp_verify_nonce( $nonce, 'wp_rest' );
	}

	/**
	 * Publish a page that embeds the public documentation shortcode.
	 *
	 * Reuses an existing onboarding page when it is still in the database.
	 *
	 * @return int|\WP_Error
	 */
	private function create_docs_page() {
		$state = Onboarding::get_state();

		if ( $state['page_id'] ) {
			$existing = get_post( $state['page_id'] );

			if ( $existing instanceof \WP_Post && 'trash' !== $existing->post_status ) {
				return (int) $existing->ID;
			}
		}

		$page_id = wp_insert_post(
			array(
				'post_content' => '[nirdeshio_docs]',
				'post_status'  => 'publish',
				'post_title'   => __( 'Documentation', 'itsmanzur-docs' ),
				'post_type'    => 'page',
			),
			true
		);

		return $page_id;
	}

	/**
	 * Shared boolean argument schema.
	 *
	 * @return array<string, mixed>
	 */
	private function bool_arg() {
		return array(
			'type'              => 'boolean',
			'sanitize_callback' => 'rest_sanitize_boolean',
			'validate_callback' => 'rest_validate_request_arg',
		);
	}
}
