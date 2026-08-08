<?php
/**
 * Site-wide settings REST endpoint tests.
 *
 * @package ItsDZ\Doczur\Tests\Integration
 */

namespace ItsDZ\Doczur\Tests\Integration;

use ItsDZ\Doczur\REST\Settings_Controller;
use ItsDZ\Doczur\Security\Capabilities;
use WP_REST_Request;
use WP_UnitTestCase;

/**
 * Proves the uninstall data-deletion opt-in is actually reachable from the
 * REST API — readme.txt has always promised "delete data only if you opt
 * in via the plugin settings", but until this controller existed nothing
 * ever wrote itsdz_delete_data_on_uninstall, so the promise was false.
 */
final class SettingsControllerTest extends WP_UnitTestCase {
	/**
	 * Log in as an admin and reset the option before each test.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();

		Capabilities::install();

		$admin_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );

		delete_option( Settings_Controller::DELETE_DATA_OPTION );

		do_action( 'rest_api_init' );
	}

	/**
	 * The option defaults to false when never set.
	 *
	 * @return void
	 */
	public function test_get_settings_defaults_to_false() {
		$response = rest_get_server()->dispatch( new WP_REST_Request( 'GET', '/itsdz/v1/settings' ) );

		$this->assertSame( 200, $response->get_status() );
		$this->assertFalse( $response->get_data()['delete_data_on_uninstall'] );
	}

	/**
	 * Turning the setting on persists it, and it round-trips through GET.
	 *
	 * @return void
	 */
	public function test_update_settings_persists_the_opt_in() {
		$request = new WP_REST_Request( 'PUT', '/itsdz/v1/settings' );
		$request->set_param( 'delete_data_on_uninstall', true );

		$response = rest_get_server()->dispatch( $request );

		$this->assertSame( 200, $response->get_status() );
		$this->assertTrue( $response->get_data()['delete_data_on_uninstall'] );
		$this->assertTrue( (bool) get_option( Settings_Controller::DELETE_DATA_OPTION ) );

		$get_response = rest_get_server()->dispatch( new WP_REST_Request( 'GET', '/itsdz/v1/settings' ) );
		$this->assertTrue( $get_response->get_data()['delete_data_on_uninstall'] );
	}

	/**
	 * Turning it back off is just as reachable.
	 *
	 * @return void
	 */
	public function test_update_settings_can_opt_back_out() {
		update_option( Settings_Controller::DELETE_DATA_OPTION, true );

		$request = new WP_REST_Request( 'PUT', '/itsdz/v1/settings' );
		$request->set_param( 'delete_data_on_uninstall', false );

		$response = rest_get_server()->dispatch( $request );

		$this->assertFalse( $response->get_data()['delete_data_on_uninstall'] );
		$this->assertFalse( (bool) get_option( Settings_Controller::DELETE_DATA_OPTION ) );
	}

	/**
	 * GET is rejected without the management capability.
	 *
	 * @return void
	 */
	public function test_get_settings_requires_capability() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'subscriber' ) ) );

		$response = rest_get_server()->dispatch( new WP_REST_Request( 'GET', '/itsdz/v1/settings' ) );

		$this->assertSame( 403, $response->get_status() );
	}

	/**
	 * PUT is rejected without the management capability, and nothing is saved.
	 *
	 * @return void
	 */
	public function test_update_settings_requires_capability() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'subscriber' ) ) );

		$request = new WP_REST_Request( 'PUT', '/itsdz/v1/settings' );
		$request->set_param( 'delete_data_on_uninstall', true );

		$response = rest_get_server()->dispatch( $request );

		$this->assertSame( 403, $response->get_status() );
		$this->assertFalse( (bool) get_option( Settings_Controller::DELETE_DATA_OPTION ) );
	}
}
