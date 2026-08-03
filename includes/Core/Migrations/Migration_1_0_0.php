<?php
/**
 * Initial Doczur database schema.
 *
 * @package ItsDZ\Doczur\Core\Migrations
 */

namespace ItsDZ\Doczur\Core\Migrations;

defined( 'ABSPATH' ) || exit;

/**
 * Creates the high-volume Doczur data tables.
 */
final class Migration_1_0_0 {
	/**
	 * Migration version.
	 */
	const VERSION = '1.0.0';

	/**
	 * Run the migration through WordPress dbDelta.
	 *
	 * @return array<int, string>
	 */
	public function up() {
		if ( ! function_exists( 'dbDelta' ) ) {
			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		}

		$queries = self::schema_queries();
		$changes = array();

		foreach ( $queries as $query ) {
			$changes = array_merge( $changes, dbDelta( $query ) );
		}

		return $changes;
	}

	/**
	 * Build the SQL statements consumed by dbDelta.
	 *
	 * @return string[]
	 */
	public static function schema_queries() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();
		$tables          = self::table_names();

		return array(
			"CREATE TABLE {$tables['search_index']} (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				article_id bigint(20) unsigned NOT NULL,
				kb_id bigint(20) unsigned NOT NULL,
				title text NOT NULL,
				content_plain longtext NOT NULL,
				keywords text NULL,
				weight float NOT NULL DEFAULT 1,
				updated_at datetime NOT NULL,
				PRIMARY KEY  (id),
				UNIQUE KEY article_id (article_id),
				KEY kb_id (kb_id),
				FULLTEXT KEY search_ft (title, content_plain, keywords)
			) {$charset_collate};",
			"CREATE TABLE {$tables['search_log']} (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				kb_id bigint(20) unsigned NOT NULL,
				query varchar(255) NOT NULL,
				results_count smallint(5) unsigned NOT NULL DEFAULT 0,
				clicked_article_id bigint(20) unsigned NULL,
				ip_hash char(64) NULL,
				created_at datetime NOT NULL,
				PRIMARY KEY  (id),
				KEY kb_id_created (kb_id, created_at),
				KEY results_count (results_count)
			) {$charset_collate};",
			"CREATE TABLE {$tables['feedback']} (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				article_id bigint(20) unsigned NOT NULL,
				kb_id bigint(20) unsigned NOT NULL,
				helpful tinyint(1) NOT NULL,
				comment text NULL,
				ip_hash char(64) NULL,
				created_at datetime NOT NULL,
				PRIMARY KEY  (id),
				KEY article_id (article_id)
			) {$charset_collate};",
			"CREATE TABLE {$tables['views']} (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				article_id bigint(20) unsigned NOT NULL,
				kb_id bigint(20) unsigned NOT NULL,
				view_date date NOT NULL,
				view_count int(10) unsigned NOT NULL DEFAULT 0,
				PRIMARY KEY  (id),
				UNIQUE KEY article_date (article_id, view_date),
				KEY kb_id (kb_id)
			) {$charset_collate};",
		);
	}

	/**
	 * Return prefixed table names.
	 *
	 * @return array<string, string>
	 */
	public static function table_names() {
		global $wpdb;

		return array(
			'search_index' => $wpdb->prefix . 'itsdz_search_index',
			'search_log'   => $wpdb->prefix . 'itsdz_search_log',
			'feedback'     => $wpdb->prefix . 'itsdz_feedback',
			'views'        => $wpdb->prefix . 'itsdz_views',
		);
	}

	/**
	 * Check that every core table exists before advancing the DB version.
	 *
	 * @return bool
	 */
	public static function is_complete() {
		global $wpdb;

		foreach ( self::table_names() as $table_name ) {
			// A live schema check must bypass cache so failed migrations can self-repair.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$found_table = $wpdb->get_var(
				$wpdb->prepare(
					'SHOW TABLES LIKE %s',
					$wpdb->esc_like( $table_name )
				)
			);

			if ( $table_name !== $found_table ) {
				return false;
			}
		}

		return true;
	}
}
