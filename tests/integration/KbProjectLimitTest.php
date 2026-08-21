<?php
/**
 * Documentation-project creation cap tests.
 *
 * @package ItsDZ\Doczur\Tests\Integration
 */

namespace ItsDZ\Doczur\Tests\Integration;

use ItsDZ\Doczur\Security\Capabilities;
use WP_REST_Request;
use WP_UnitTestCase;

/**
 * Proves the free plugin no longer caps documentation projects at one —
 * a wp.org review flagged the previous hardcoded default as trialware —
 * while the itsdz_max_documentation_projects filter itself still works
 * for a site (or another plugin) that wants to impose its own limit.
 */
final class KbProjectLimitTest extends WP_UnitTestCase {
	/**
	 * Log in as an admin.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();

		Capabilities::install();

		$admin_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );

		do_action( 'rest_api_init' );
	}

	/**
	 * Dispatch a POST to create a project.
	 *
	 * @param string $title Project title.
	 * @return \WP_REST_Response
	 */
	private function create_project( $title ) {
		$request = new WP_REST_Request( 'POST', '/itsdz/v1/kb' );
		$request->set_param( 'title', $title );

		return rest_get_server()->dispatch( $request );
	}

	/**
	 * A second (and third) documentation project can be created by default.
	 *
	 * @return void
	 */
	public function test_unlimited_projects_can_be_created_by_default() {
		$first  = $this->create_project( 'First project' );
		$second = $this->create_project( 'Second project' );
		$third  = $this->create_project( 'Third project' );

		$this->assertSame( 201, $first->get_status() );
		$this->assertSame( 201, $second->get_status() );
		$this->assertSame( 201, $third->get_status() );
	}

	/**
	 * The filter still works for a site that wants a cap of its own.
	 *
	 * @return void
	 */
	public function test_filter_can_still_impose_a_cap() {
		add_filter(
			'itsdz_max_documentation_projects',
			static function () {
				return 1;
			}
		);

		$first  = $this->create_project( 'First project' );
		$second = $this->create_project( 'Second project' );

		$this->assertSame( 201, $first->get_status() );
		$this->assertSame( 403, $second->get_status() );
		$this->assertSame( 'itsdz_kb_limit_reached', $second->as_error()->get_error_code() );
	}
}
