<?php
/**
 * Buffered article view tracker.
 *
 * Strategy (roadmap §6.3):
 *   - Never write to the DB on every page-view.
 *   - Buffer pending counts in the object cache / transients.
 *   - Flush to `{prefix}itsdz_views` via a WP-Cron job every 5 minutes using
 *     a date-keyed UPSERT so the row stays one record per (article, day).
 *   - If Action Scheduler is available (bundled with WooCommerce or installed
 *     standalone) we prefer it over WP-Cron for better reliability; otherwise
 *     we fall back gracefully to WP-Cron.
 *
 * @package ItsDZ\Doczur\Analytics
 */

namespace ItsDZ\Doczur\Analytics;

use ItsDZ\Doczur\Core\Migrations\Migration_1_0_0;
use ItsDZ\Doczur\Core\Service;
use ItsDZ\Doczur\PostTypes\Article_Post_Type;
use ItsDZ\Doczur\PostTypes\KB_Post_Type;
use ItsDZ\Doczur\Security\Capabilities;

defined( 'ABSPATH' ) || exit;

/**
 * Records article page-views with a buffered write strategy.
 */
final class View_Tracker implements Service {

	/**
	 * WP-Cron hook name.
	 */
	const CRON_HOOK = 'itsdz_flush_views';

	/**
	 * Action Scheduler hook name (used when AS is available).
	 */
	const AS_HOOK = 'itsdz_flush_views_as';

	/**
	 * Transient key that holds the pending buffer.
	 *
	 * Format: array<string, int>  where key = "{article_id}:{kb_id}:{Y-m-d}"
	 */
	const BUFFER_TRANSIENT = 'itsdz_view_buffer';

	/**
	 * How long the buffer transient lives (seconds).
	 * Longer than the cron interval so no data is lost between flush cycles.
	 */
	const BUFFER_TTL = 15 * MINUTE_IN_SECONDS;

	/**
	 * WP-Cron interval (every 5 minutes).
	 */
	const CRON_INTERVAL = 'itsdz_every_5min';

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register() {
		// Register custom cron interval.
		add_filter( 'cron_schedules', array( $this, 'add_cron_interval' ) ); // phpcs:ignore WordPress.WP.CronInterval.CronSchedulesInterval

		// Track views on the frontend.
		add_action( 'wp', array( $this, 'maybe_track' ) );

		// WP-Cron flush callback.
		add_action( self::CRON_HOOK, array( $this, 'flush' ) );

		// Action Scheduler flush callback (no-op when AS is absent).
		add_action( self::AS_HOOK, array( $this, 'flush' ) );

		// Schedule the recurring flush when it is not yet scheduled.
		add_action( 'init', array( $this, 'maybe_schedule' ) );
	}

	// -------------------------------------------------------------------------
	// Cron interval
	// -------------------------------------------------------------------------

	/**
	 * Add the every-5-minutes cron schedule.
	 *
	 * @param array<string, array<string, mixed>> $schedules Existing schedules.
	 * @return array<string, array<string, mixed>>
	 */
	public function add_cron_interval( $schedules ) {
		if ( ! isset( $schedules[ self::CRON_INTERVAL ] ) ) {
			$schedules[ self::CRON_INTERVAL ] = array(
				'interval' => 5 * MINUTE_IN_SECONDS,
				'display'  => __( 'Every 5 minutes', 'doczur' ),
			);
		}

		return $schedules;
	}

	// -------------------------------------------------------------------------
	// Scheduling
	// -------------------------------------------------------------------------

	/**
	 * Schedule the flush job if it is not already running.
	 *
	 * Prefers Action Scheduler when available; falls back to WP-Cron.
	 *
	 * @return void
	 */
	public function maybe_schedule() {
		if ( $this->is_action_scheduler_available() ) {
			$this->schedule_action_scheduler();
		} else {
			$this->schedule_wp_cron();
		}
	}

	/**
	 * Remove all scheduled flush events (called on plugin deactivation).
	 *
	 * @return void
	 */
	public function unschedule() {
		$timestamp = wp_next_scheduled( self::CRON_HOOK );

		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, self::CRON_HOOK );
		}

		if ( $this->is_action_scheduler_available() ) {
			as_unschedule_all_actions( self::AS_HOOK, array(), 'itsdz' );
		}
	}

	// -------------------------------------------------------------------------
	// Tracking
	// -------------------------------------------------------------------------

	/**
	 * Buffer a view for the current request if it is a public article page.
	 *
	 * Skipped for: admin, REST, search-bots via X-Robots-Tag, logged-in users
	 * with manage capability (prevents editors from inflating counts).
	 *
	 * @return void
	 */
	public function maybe_track() {
		if (
			is_admin() ||
			( defined( 'REST_REQUEST' ) && REST_REQUEST ) ||
			! is_singular( Article_Post_Type::POST_TYPE ) ||
			current_user_can( Capabilities::MANAGE_DOCS )
		) {
			return;
		}

		$article = get_queried_object();

		if (
			! $article instanceof \WP_Post ||
			'publish' !== $article->post_status
		) {
			return;
		}

		$kb_id = absint( get_post_meta( $article->ID, '_itsdz_kb_id', true ) );

		if ( ! $kb_id ) {
			return;
		}

		$this->buffer_view( $article->ID, $kb_id );
	}

	// -------------------------------------------------------------------------
	// Buffer
	// -------------------------------------------------------------------------

	/**
	 * Add one view to the in-memory buffer.
	 *
	 * The buffer is a transient-backed associative array keyed by
	 * "{article_id}:{kb_id}:{Y-m-d}" so each flush can UPSERT a single row
	 * per (article, day).
	 *
	 * @param int $article_id Article post ID.
	 * @param int $kb_id      KB post ID.
	 * @return void
	 */
	public function buffer_view( $article_id, $kb_id ) {
		$date   = gmdate( 'Y-m-d' );
		$key    = "{$article_id}:{$kb_id}:{$date}";
		$buffer = $this->read_buffer();

		$buffer[ $key ] = ( isset( $buffer[ $key ] ) ? (int) $buffer[ $key ] : 0 ) + 1;

		$this->write_buffer( $buffer );
	}

	// -------------------------------------------------------------------------
	// Flush
	// -------------------------------------------------------------------------

	/**
	 * Flush all buffered views to the database.
	 *
	 * Called by WP-Cron or Action Scheduler every 5 minutes.
	 * Uses INSERT … ON DUPLICATE KEY UPDATE so each (article_id, view_date)
	 * pair maps to exactly one row and concurrent flush calls are safe.
	 *
	 * @return void
	 */
	public function flush() {
		$buffer = $this->read_buffer();

		if ( empty( $buffer ) ) {
			return;
		}

		// Clear the buffer immediately so a parallel flush does not double-count.
		$this->write_buffer( array() );

		global $wpdb;
		$table = Migration_1_0_0::table_names()['views'];

		foreach ( $buffer as $key => $count ) {
			$count = absint( $count );

			if ( ! $count ) {
				continue;
			}

			$parts = explode( ':', $key );

			if ( 3 !== count( $parts ) ) {
				continue;
			}

			list( $article_id, $kb_id, $view_date ) = $parts;
			$article_id                             = absint( $article_id );
			$kb_id                                  = absint( $kb_id );

			// Basic date validation.
			if ( ! $article_id || ! $kb_id || ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $view_date ) ) {
				continue;
			}

			$query = $wpdb->prepare(
				'INSERT INTO %i (article_id, kb_id, view_date, view_count)
				VALUES (%d, %d, %s, %d)
				ON DUPLICATE KEY UPDATE view_count = view_count + VALUES(view_count)',
				$table,
				$article_id,
				$kb_id,
				$view_date,
				$count
			);

			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->query( $query );
		}
	}

	// -------------------------------------------------------------------------
	// Public query helpers
	// -------------------------------------------------------------------------

	/**
	 * Return the total recorded views for a single article.
	 *
	 * @param int $article_id Article post ID.
	 * @return int
	 */
	public static function get_total( $article_id ) {
		global $wpdb;

		$table = Migration_1_0_0::table_names()['views'];

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$total = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT SUM(view_count) FROM %i WHERE article_id = %d',
				$table,
				absint( $article_id )
			)
		);

		return absint( $total );
	}

	/**
	 * Return total views for all articles in a KB, grouped by article.
	 *
	 * Returns an array keyed by article_id with integer view counts.
	 * Result is object-cache backed with a 5-minute TTL.
	 *
	 * @param int $kb_id KB post ID.
	 * @return array<int, int>
	 */
	public static function get_kb_totals( $kb_id ) {
		$kb_id     = absint( $kb_id );
		$cache_key = 'view_totals_kb_' . $kb_id;
		$cached    = wp_cache_get( $cache_key, 'doczur' );

		if ( false !== $cached ) {
			return (array) $cached;
		}

		global $wpdb;
		$table = Migration_1_0_0::table_names()['views'];

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT article_id, SUM(view_count) AS total FROM %i WHERE kb_id = %d GROUP BY article_id',
				$table,
				$kb_id
			),
			ARRAY_A
		);

		$totals = array();

		if ( is_array( $rows ) ) {
			foreach ( $rows as $row ) {
				$totals[ (int) $row['article_id'] ] = (int) $row['total'];
			}
		}

		wp_cache_set( $cache_key, $totals, 'doczur', 5 * MINUTE_IN_SECONDS );

		return $totals;
	}

	// -------------------------------------------------------------------------
	// Internal helpers
	// -------------------------------------------------------------------------

	/**
	 * Read the pending buffer from the transient store.
	 *
	 * @return array<string, int>
	 */
	private function read_buffer() {
		$buffer = get_transient( self::BUFFER_TRANSIENT );

		return is_array( $buffer ) ? $buffer : array();
	}

	/**
	 * Persist the buffer back to the transient store.
	 *
	 * @param array<string, int> $buffer Updated buffer.
	 * @return void
	 */
	private function write_buffer( $buffer ) {
		set_transient( self::BUFFER_TRANSIENT, $buffer, self::BUFFER_TTL );
	}

	/**
	 * Whether Action Scheduler is loaded and available.
	 *
	 * @return bool
	 */
	private function is_action_scheduler_available() {
		return function_exists( 'as_has_scheduled_action' );
	}

	/**
	 * Schedule the WP-Cron recurring event.
	 *
	 * @return void
	 */
	private function schedule_wp_cron() {
		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_event( time(), self::CRON_INTERVAL, self::CRON_HOOK );
		}
	}

	/**
	 * Schedule a recurring Action Scheduler action (preferred over WP-Cron).
	 *
	 * @return void
	 */
	private function schedule_action_scheduler() {
		if ( ! as_has_scheduled_action( self::AS_HOOK, array(), 'itsdz' ) ) {
			as_schedule_recurring_action(
				time(),
				5 * MINUTE_IN_SECONDS,
				self::AS_HOOK,
				array(),
				'itsdz',
				true
			);
		}
	}
}
