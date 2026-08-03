<?php
/**
 * Migration metadata tests.
 *
 * @package ItsDZ\Doczur\Tests\Unit
 */

namespace ItsDZ\Doczur\Tests\Unit;

use ItsDZ\Doczur\Core\Migrations\Migration_1_0_0;
use PHPUnit\Framework\TestCase;

/**
 * Verifies deterministic table naming.
 */
final class MigrationTest extends TestCase {
	/**
	 * Core tables use the current WordPress prefix.
	 *
	 * @return void
	 */
	public function test_core_table_names_are_prefixed() {
		global $wpdb;

		$previous_wpdb = $wpdb;
		$wpdb          = $this->create_wpdb_stub(); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

		try {
			$tables = Migration_1_0_0::table_names();
		} finally {
			$wpdb = $previous_wpdb; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		}

		$this->assertSame(
			array(
				'search_index' => 'wp_test_itsdz_search_index',
				'search_log'   => 'wp_test_itsdz_search_log',
				'feedback'     => 'wp_test_itsdz_feedback',
				'views'        => 'wp_test_itsdz_views',
			),
			$tables
		);
	}

	/**
	 * Schema retains the performance-critical indexes.
	 *
	 * @return void
	 */
	public function test_schema_contains_four_tables_and_required_indexes() {
		global $wpdb;

		$previous_wpdb = $wpdb;
		$wpdb          = $this->create_wpdb_stub(); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

		try {
			$queries = Migration_1_0_0::schema_queries();
		} finally {
			$wpdb = $previous_wpdb; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		}

		$this->assertCount( 4, $queries );
		$this->assertStringContainsString( 'FULLTEXT KEY search_ft', $queries[0] );
		$this->assertStringContainsString( 'UNIQUE KEY article_date', $queries[3] );
	}

	/**
	 * Re-running the migration remains safe for dbDelta.
	 *
	 * @return void
	 */
	public function test_migration_can_run_twice() {
		global $wpdb;

		$previous_wpdb                 = $wpdb;
		$wpdb                          = $this->create_wpdb_stub(); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$GLOBALS['itsdz_test_dbdelta'] = array();

		try {
			$migration = new Migration_1_0_0();
			$migration->up();
			$migration->up();
		} finally {
			$wpdb = $previous_wpdb; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		}

		$this->assertCount( 8, $GLOBALS['itsdz_test_dbdelta'] );
	}

	/**
	 * Create the minimal wpdb surface required by schema tests.
	 *
	 * @return object
	 */
	private function create_wpdb_stub() {
		return new class() {
			/**
			 * Test table prefix.
			 *
			 * @var string
			 */
			public $prefix = 'wp_test_';

			/**
			 * Return a deterministic test collation.
			 *
			 * @return string
			 */
			public function get_charset_collate() {
				return 'DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci';
			}
		};
	}
}
