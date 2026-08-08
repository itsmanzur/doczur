<?php
/**
 * Harness smoke test.
 *
 * @package ItsDZ\Doczur\Tests\Integration
 */

namespace ItsDZ\Doczur\Tests\Integration;

use ItsDZ\Doczur\Core\Migrations\Migration_1_0_0;
use ItsDZ\Doczur\PostTypes\Article_Post_Type;
use ItsDZ\Doczur\PostTypes\KB_Post_Type;
use WP_UnitTestCase;

/**
 * Proves WordPress boots with Doczur fully registered.
 */
final class BootstrapTest extends WP_UnitTestCase {

	/**
	 * The plugin's post types are registered against real WordPress.
	 *
	 * @return void
	 */
	public function test_post_types_are_registered() {
		$this->assertTrue( post_type_exists( KB_Post_Type::POST_TYPE ) );
		$this->assertTrue( post_type_exists( Article_Post_Type::POST_TYPE ) );
	}

	/**
	 * The plugin's taxonomies are registered.
	 *
	 * @return void
	 */
	public function test_taxonomies_are_registered() {
		$this->assertTrue( taxonomy_exists( 'itsdz_section' ) );
		$this->assertTrue( taxonomy_exists( 'itsdz_tag' ) );
		$this->assertTrue( taxonomy_exists( 'itsdz_version' ) );
	}

	/**
	 * Activation created the plugin's custom tables.
	 *
	 * @return void
	 */
	public function test_custom_tables_exist() {
		global $wpdb;

		foreach ( Migration_1_0_0::table_names() as $table ) {
			$found = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

			$this->assertSame( $table, $found, "Missing table {$table}." );
		}
	}

	/**
	 * The REST routes are mounted under the plugin namespace.
	 *
	 * @return void
	 */
	public function test_rest_routes_are_registered() {
		do_action( 'rest_api_init' );

		$routes = rest_get_server()->get_routes();

		$this->assertArrayHasKey( '/itsdz/v1/articles', $routes );
		$this->assertArrayHasKey( '/itsdz/v1/sample-data', $routes );
	}
}
