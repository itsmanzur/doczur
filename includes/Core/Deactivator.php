<?php
/**
 * Plugin deactivation.
 *
 * @package ItsDZ\Doczur\Core
 */

namespace ItsDZ\Doczur\Core;

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
		wp_clear_scheduled_hook( 'itsdz_flush_view_counts' );
		delete_transient( Migrator::CHECK_TRANSIENT );
		flush_rewrite_rules();
	}
}
