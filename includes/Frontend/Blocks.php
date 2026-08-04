<?php
/**
 * Gutenberg block registration (PHP side).
 *
 * Full block implementations land in Phase 6. This stub ensures the service
 * is already wired into the Plugin service registry so Phase 6 can simply
 * add block.json files and call register_block_type() here without touching
 * Plugin.php or any other file.
 *
 * @package ItsDZ\Doczur\Frontend
 */

namespace ItsDZ\Doczur\Frontend;

use ItsDZ\Doczur\Core\Service;

defined( 'ABSPATH' ) || exit;

/**
 * Registers Gutenberg blocks provided by Doczur.
 */
final class Blocks implements Service {

	/**
	 * Register WordPress hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'init', array( $this, 'register_blocks' ), 10 );
	}

	/**
	 * Register all plugin blocks.
	 *
	 * Each block lives in its own subdirectory containing a block.json manifest.
	 * Phase 6 will add the actual block directories; this method already scans
	 * for them so no further plumbing is needed later.
	 *
	 * @return void
	 */
	public function register_blocks() {
		$blocks_dir = ITSDZ_PLUGIN_DIR . 'includes/Frontend/Blocks';

		if ( ! is_dir( $blocks_dir ) ) {
			return;
		}

		foreach ( (array) glob( $blocks_dir . '/*/block.json' ) as $block_json ) {
			if ( is_string( $block_json ) && is_readable( $block_json ) ) {
				register_block_type( dirname( $block_json ) );
			}
		}
	}
}
