<?php
/**
 * Site-wide Doczur settings REST endpoints.
 *
 * @package ItsDZ\Doczur\REST
 */

namespace ItsDZ\Doczur\REST;

defined( 'ABSPATH' ) || exit;

/**
 * Manages settings that apply to the whole install, not a single project.
 *
 * Currently just the uninstall data-deletion opt-in — uninstall.php has
 * always read `itsdz_delete_data_on_uninstall`, but until this route
 * existed nothing ever wrote it, so the opt-in readme.txt promises was
 * unreachable from the UI.
 */
final class Settings_Controller extends REST_Controller {
	/**
	 * Option name backing the uninstall opt-in.
	 */
	const DELETE_DATA_OPTION = 'itsdz_delete_data_on_uninstall';

	/**
	 * Register routes.
	 *
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/settings',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_settings' ),
					'permission_callback' => array( $this, 'can_manage' ),
				),
				array(
					'methods'             => \WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_settings' ),
					'permission_callback' => array( $this, 'can_manage' ),
					'args'                => array(
						'delete_data_on_uninstall' => array(
							'required' => true,
							'type'     => 'boolean',
						),
					),
				),
			)
		);
	}

	/**
	 * Get the current settings.
	 *
	 * @return \WP_REST_Response
	 */
	public function get_settings() {
		return rest_ensure_response(
			array(
				'delete_data_on_uninstall' => (bool) get_option( self::DELETE_DATA_OPTION, false ),
			)
		);
	}

	/**
	 * Update the settings.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	public function update_settings( $request ) {
		$delete_data = (bool) $request->get_param( 'delete_data_on_uninstall' );

		update_option( self::DELETE_DATA_OPTION, $delete_data, false );

		return rest_ensure_response( array( 'delete_data_on_uninstall' => $delete_data ) );
	}
}
