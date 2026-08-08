<?php
/**
 * Doczur metadata panel for the native block editor.
 *
 * @package ItsDZ\Doczur\Admin
 */

namespace ItsDZ\Doczur\Admin;

use ItsDZ\Doczur\Core\Service;
use ItsDZ\Doczur\PostTypes\Article_Post_Type;

defined( 'ABSPATH' ) || exit;

/**
 * Loads the Doczur sidebar panel only on the article edit screen.
 */
final class Editor_Panel_Assets implements Service {
	/**
	 * Script handle.
	 */
	const HANDLE = 'itsdz-editor-panel';

	/**
	 * Register WordPress hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue' ) );
	}

	/**
	 * Enqueue the panel bundle on the itsdz_doc block editor screen only.
	 *
	 * `@wordpress/api-fetch`'s nonce and root-URL middleware are already
	 * configured by WordPress core for every block editor screen (via the
	 * `wp-api-fetch` script it enqueues itself), so nothing further needs
	 * to be localized here for the panel's REST calls to work.
	 *
	 * @return void
	 */
	public function enqueue() {
		$screen = get_current_screen();

		if ( ! $screen || Article_Post_Type::POST_TYPE !== $screen->post_type ) {
			return;
		}

		$asset_file  = ITSDZ_PLUGIN_DIR . 'build/editor-panel.asset.php';
		$script_file = ITSDZ_PLUGIN_DIR . 'build/editor-panel.js';

		if ( ! is_readable( $asset_file ) || ! is_readable( $script_file ) ) {
			return;
		}

		$asset = require $asset_file;

		wp_enqueue_script(
			self::HANDLE,
			ITSDZ_PLUGIN_URL . 'build/editor-panel.js',
			$asset['dependencies'],
			$asset['version'],
			true
		);

		wp_set_script_translations( self::HANDLE, 'doczur', ITSDZ_PLUGIN_DIR . 'languages' );
	}
}
