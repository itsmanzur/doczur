<?php
/**
 * Plugin deactivation.
 *
 * @package ItsDZ\Doczur\Core
 */

namespace ItsDZ\Doczur\Core;

use ItsDZ\Doczur\Analytics\View_Tracker;
use ItsDZ\Doczur\Core\Migrations\Migrator;

defined( 'ABSPATH' ) || exit;

/**
 * Cleans up scheduled runtime state without deleting user content.
 */
final class Deactivator {
	/**
	 * Deactivate Doczur.
	 *
	 * @return void
	 */
	public static function deactivate() {
		// Unschedule WP-Cron view-flush event.
		$cron_timestamp = wp_next_scheduled( View_Tracker::CRON_HOOK );
		if ( $cron_timestamp ) {
			wp_unschedule_event( $cron_timestamp, View_Tracker::CRON_HOOK );
		}

		// Unschedule Action Scheduler recurring action when AS is available.
		if ( function_exists( 'as_unschedule_all_actions' ) ) {
			as_unschedule_all_actions( View_Tracker::AS_HOOK, array(), 'itsdz' );
		}

		// Drop the pending view buffer so stale counts are not flushed later.
		delete_transient( View_Tracker::BUFFER_TRANSIENT );

		// Allow the migration check to re-run on next activation.
		delete_transient( Migrator::CHECK_TRANSIENT );

		flush_rewrite_rules();
	}
}
