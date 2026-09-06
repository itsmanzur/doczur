<?php
/**
 * Onboarding wizard REST tests.
 *
 * @package ItsDZ\Doczur\Tests\Integration
 */

namespace ItsDZ\Doczur\Tests\Integration;

use ItsDZ\Doczur\Admin\Onboarding;
use ItsDZ\Doczur\PostTypes\KB_Post_Type;
use ItsDZ\Doczur\Security\Capabilities;
use WP_REST_Request;
use WP_UnitTestCase;

/**
 * Covers wizard progress, skip, restart-without-deleting-content, and opt-in logs.
 */
final class OnboardingControllerTest extends WP_UnitTestCase {
	/**
	 * Administrator user ID.
	 *
	 * @var int
	 */
	private $admin_id = 0;

	/**
	 * Log in as an admin and reset wizard state.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();

		Capabilities::install();

		$this->admin_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $this->admin_id );

		delete_option( Onboarding::OPTION );
		delete_user_meta( $this->admin_id, Onboarding::NOTICE_META );

		do_action( 'rest_api_init' );
	}

	/**
	 * GET reports the default pending state.
	 *
	 * @return void
	 */
	public function test_status_defaults_to_pending() {
		$response = rest_get_server()->dispatch( $this->request( 'GET', '/itsdz/v1/onboarding/status' ) );

		$this->assertSame( 200, $response->get_status() );
		$data = $response->get_data();
		$this->assertSame( 'pending', $data['status'] );
		$this->assertSame( 1, $data['step'] );
		$this->assertFalse( $data['analytics_opt_in'] );
		$this->assertTrue( $data['should_show'] );
	}

	/**
	 * Completing a step persists progress.
	 *
	 * @return void
	 */
	public function test_complete_step_saves_progress() {
		$request  = $this->request( 'POST', '/itsdz/v1/onboarding/complete-step', array( 'step' => 2 ) );
		$response = rest_get_server()->dispatch( $request );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( 'in_progress', $response->get_data()['status'] );
		$this->assertSame( 2, $response->get_data()['step'] );
		$this->assertSame( 'in_progress', Onboarding::get_state()['status'] );
	}

	/**
	 * Skip dismisses the wizard and never nags via should_show.
	 *
	 * @return void
	 */
	public function test_skip_hides_the_wizard() {
		$response = rest_get_server()->dispatch( $this->request( 'POST', '/itsdz/v1/onboarding/skip' ) );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( 'skipped', $response->get_data()['status'] );
		$this->assertFalse( $response->get_data()['should_show'] );
		$this->assertFalse( Onboarding::should_show_wizard() );
	}

	/**
	 * Restart reopens the wizard UI without deleting existing projects.
	 *
	 * @return void
	 */
	public function test_restart_does_not_delete_content() {
		$kb_id = self::factory()->post->create(
			array(
				'post_status' => 'publish',
				'post_title'  => 'Keep this project',
				'post_type'   => KB_Post_Type::POST_TYPE,
			)
		);

		Onboarding::skip();

		$response = rest_get_server()->dispatch(
			$this->request(
				'POST',
				'/itsdz/v1/onboarding/complete-step',
				array( 'restart' => true )
			)
		);

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( 'pending', $response->get_data()['status'] );
		$this->assertSame( 1, $response->get_data()['step'] );
		$this->assertTrue( $response->get_data()['should_show'] );
		$this->assertSame( 'publish', get_post_status( $kb_id ) );
		$this->assertSame( 'Keep this project', get_the_title( $kb_id ) );
	}

	/**
	 * Opt-out means nothing is written to search_log.
	 *
	 * @return void
	 */
	public function test_skip_without_opt_in_does_not_log() {
		rest_get_server()->dispatch( $this->request( 'POST', '/itsdz/v1/onboarding/skip' ) );

		$this->assertSame( 0, $this->onboarding_log_count() );
	}

	/**
	 * Opt-in logs only a step number, empty IP hash, and the onboarding sentinel.
	 *
	 * @return void
	 */
	public function test_opt_in_logs_step_and_timestamp_only() {
		rest_get_server()->dispatch(
			$this->request(
				'POST',
				'/itsdz/v1/onboarding/complete-step',
				array(
					'step'             => 1,
					'analytics_opt_in' => true,
				)
			)
		);

		global $wpdb;

		$table = $wpdb->prefix . 'itsdz_search_log';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$row = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM %i WHERE query = %s ORDER BY id DESC LIMIT 1', $table, Onboarding::EVENT_QUERY ) );

		$this->assertNotNull( $row );
		$this->assertSame( 0, (int) $row->kb_id );
		$this->assertSame( Onboarding::EVENT_QUERY, $row->query );
		$this->assertSame( 1, (int) $row->results_count );
		$this->assertSame( '', (string) $row->ip_hash );
		$this->assertNotEmpty( $row->created_at );
		$this->assertStringNotContainsString( '@', wp_json_encode( $row ) );
	}

	/**
	 * Completing the last step marks the wizard done.
	 *
	 * @return void
	 */
	public function test_complete_final_step_hides_the_wizard() {
		$response = rest_get_server()->dispatch(
			$this->request( 'POST', '/itsdz/v1/onboarding/complete-step', array( 'step' => 5 ) )
		);

		$this->assertSame( 'completed', $response->get_data()['status'] );
		$this->assertFalse( $response->get_data()['should_show'] );
	}

	/**
	 * Subscribers cannot read onboarding status.
	 *
	 * @return void
	 */
	public function test_status_requires_capability() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'subscriber' ) ) );

		$response = rest_get_server()->dispatch( $this->request( 'GET', '/itsdz/v1/onboarding/status' ) );

		$this->assertSame( 403, $response->get_status() );
	}

	/**
	 * Missing nonce is rejected even for an administrator.
	 *
	 * @return void
	 */
	public function test_status_requires_nonce() {
		$request  = new WP_REST_Request( 'GET', '/itsdz/v1/onboarding/status' );
		$response = rest_get_server()->dispatch( $request );

		$this->assertContains( $response->get_status(), array( 401, 403 ) );
	}

	/**
	 * Build an authenticated, nonce-signed REST request.
	 *
	 * @param string               $method HTTP method.
	 * @param string               $path   Route.
	 * @param array<string, mixed> $params Body/query params.
	 * @return WP_REST_Request
	 */
	private function request( $method, $path, array $params = array() ) {
		$request = new WP_REST_Request( $method, $path );
		$nonce   = wp_create_nonce( 'wp_rest' );
		$request->set_header( 'X-WP-Nonce', $nonce );
		$request->set_param( '_wpnonce', $nonce );

		foreach ( $params as $key => $value ) {
			$request->set_param( $key, $value );
		}

		return $request;
	}

	/**
	 * Count local onboarding analytics rows.
	 *
	 * @return int
	 */
	private function onboarding_log_count() {
		global $wpdb;

		$table = $wpdb->prefix . 'itsdz_search_log';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM %i WHERE query = %s', $table, Onboarding::EVENT_QUERY ) );
	}
}
