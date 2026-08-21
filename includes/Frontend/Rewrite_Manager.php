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
	 * Flag requesting a flush on the next `init`.
	 *
	 * Set when the public slug base changes. Flushing in the same request
	 * would persist the old CPT slug because register_post_type() already ran.
	 */
	const FLUSH_OPTION = 'itsdz_rewrite_flush';

	/**
	 * Current rewrite schema version.
	 *
	 * Bump this whenever a rewrite rule is added or changed so existing
	 * installs flush their rules once on the next request.
	 *
	 * 1.1.0 — added the /llms.txt and /llms-full.txt routes.
	 * 1.2.0 — slug base is read from settings; flush runs on init so the
	 *         first request after a slug change picks up the new rules.
	 * 1.3.0 — section archives follow the configured slug base.
	 */
	const VERSION = '1.3.0';

	/**
	 * Register WordPress hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'init', array( $this, 'maybe_flush' ), 20 );
	}

	/**
	 * Refresh the rules only when their schema or slug base changes.
	 *
	 * Runs after post types register (priority 5) so flushed rules match
	 * the slug that is actually live for this request.
	 *
	 * @return void
	 */
	public function maybe_flush() {
		$version_stale = self::VERSION !== get_option( self::VERSION_OPTION, '' );
		$slug_stale    = '1' === (string) get_option( self::FLUSH_OPTION, '' );

		if ( ! $version_stale && ! $slug_stale ) {
			return;
		}

		flush_rewrite_rules( false );
		update_option( self::VERSION_OPTION, self::VERSION, false );
		delete_option( self::FLUSH_OPTION );
	}
}
