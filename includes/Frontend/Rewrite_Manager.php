<?php
/**
 * Versioned documentation rewrite refresh.
 *
 * @package ItsDZ\Doczur\Frontend
 */

namespace ItsDZ\Doczur\Frontend;

use ItsDZ\Doczur\Core\Service;

defined( 'ABSPATH' ) || exit;

/**
 * Flushes rewrite rules once after a route schema update.
 */
final class Rewrite_Manager implements Service {
	/**
	 * Stored rewrite schema option.
	 */
	const VERSION_OPTION = 'itsdz_rewrite_version';

	/**
	 * Current rewrite schema version.
	 *
	 * Bump this whenever a rewrite rule is added or changed so existing
	 * installs flush their rules once on the next admin request.
	 *
	 * 1.1.0 — added the /llms.txt and /llms-full.txt routes.
	 */
	const VERSION = '1.1.0';

	/**
	 * Register WordPress hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'admin_init', array( $this, 'maybe_flush' ) );
	}

	/**
	 * Refresh the rules only when their schema changes.
	 *
	 * @return void
	 */
	public function maybe_flush() {
		if ( self::VERSION === get_option( self::VERSION_OPTION, '' ) ) {
			return;
		}

		flush_rewrite_rules( false );
		update_option( self::VERSION_OPTION, self::VERSION, false );
	}
}
