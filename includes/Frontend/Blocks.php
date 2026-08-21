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
 * Registers Gutenberg blocks provided by Nirdeshio.
 */
final class Blocks implements Service {

	/**
	 * Script handle shared by every block's `editorScript`.
	 */
	const EDITOR_HANDLE = 'itsdz-blocks';

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

		$this->register_editor_script();
		$this->register_view_style();

		foreach ( (array) glob( $blocks_dir . '/*/block.json' ) as $block_json ) {
			if ( is_string( $block_json ) && is_readable( $block_json ) ) {
				register_block_type( dirname( $block_json ) );
			}
		}
	}

	/**
	 * Register the shared block editor bundle.
	 *
	 * Every block.json points its `editorScript` at this handle, so the edit
	 * components ship as one chunk instead of one per block. Registering (not
	 * enqueuing) means WordPress only loads it when a Nirdeshio block is used.
	 *
	 * @return void
	 */
	private function register_editor_script() {
		$asset_file  = ITSDZ_PLUGIN_DIR . 'build/blocks.asset.php';
		$script_file = ITSDZ_PLUGIN_DIR . 'build/blocks.js';

		if ( ! is_readable( $asset_file ) || ! is_readable( $script_file ) ) {
			return;
		}

		$asset = require $asset_file;

		wp_register_script(
			self::EDITOR_HANDLE,
			ITSDZ_PLUGIN_URL . 'build/blocks.js',
			$asset['dependencies'],
			$asset['version'],
			true
		);

		wp_set_script_translations( self::EDITOR_HANDLE, 'itsmanzur-docs', ITSDZ_PLUGIN_DIR . 'languages' );
	}

	/**
	 * Register the frontend stylesheet under the shared Nirdeshio handle.
	 *
	 * Blocks can be placed on ordinary pages where Frontend\Assets does not
	 * run, so each block.json declares this handle as its `style`. WordPress
	 * then loads the stylesheet only on pages that actually contain a block.
	 *
	 * Registering the same handle Frontend\Assets enqueues is intentional —
	 * a doc page that also embeds a block still loads exactly one copy.
	 *
	 * @return void
	 */
	private function register_view_style() {
		if ( wp_style_is( Assets::HANDLE, 'registered' ) ) {
			return;
		}

		$style_file = ITSDZ_PLUGIN_DIR . 'build/style-frontend.css';

		if ( ! is_readable( $style_file ) ) {
			return;
		}

		wp_register_style(
			Assets::HANDLE,
			ITSDZ_PLUGIN_URL . 'build/style-frontend.css',
			array(),
			(string) filemtime( $style_file )
		);
	}
}
