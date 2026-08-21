<?php
/**
 * Site-wide Nirdeshio settings REST endpoints.
 *
 * @package ItsDZ\Doczur\REST
 */

namespace ItsDZ\Doczur\REST;

defined( 'ABSPATH' ) || exit;

/**
 * Manages settings that apply to the whole install, not a single project.
 *
 * Each setting is saved independently (the Settings screen sends one field
 * at a time), so update_settings() only writes whichever params were
 * actually present on the request rather than requiring the full set.
 */
final class Settings_Controller extends REST_Controller {
	/**
	 * Option name backing the uninstall opt-in.
	 */
	const DELETE_DATA_OPTION = 'itsdz_delete_data_on_uninstall';

	/**
	 * Option name backing the "Powered by Nirdeshio" footer credit opt-in.
	 */
	const SHOW_POWERED_BY_OPTION = 'itsdz_show_powered_by';

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
							'type' => 'boolean',
						),
						'show_powered_by'          => array(
							'type' => 'boolean',
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
				'show_powered_by'          => (bool) get_option( self::SHOW_POWERED_BY_OPTION, false ),
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
		if ( null !== $request->get_param( 'delete_data_on_uninstall' ) ) {
			update_option( self::DELETE_DATA_OPTION, (bool) $request->get_param( 'delete_data_on_uninstall' ), false );
		}

		if ( null !== $request->get_param( 'show_powered_by' ) ) {
			update_option( self::SHOW_POWERED_BY_OPTION, (bool) $request->get_param( 'show_powered_by' ), false );
		}

		return $this->get_settings();
	}
}
