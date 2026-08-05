<?php
/**
 * Conditional Doczur admin assets.
 *
 * @package ItsDZ\Doczur\Admin
 */

namespace ItsDZ\Doczur\Admin;

use ItsDZ\Doczur\Core\Service;

defined( 'ABSPATH' ) || exit;

/**
 * Loads the React bundle only on the dedicated Doczur screen.
 */
final class Assets implements Service {
	/**
	 * Script handle.
	 */
	const HANDLE = 'itsdz-admin';

	/**
	 * Register WordPress hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
	}

	/**
	 * Enqueue the compiled admin application on its own screen only.
	 *
	 * @param string $hook_suffix Current admin page hook.
	 * @return void
	 */
	public function enqueue( $hook_suffix ) {
		$screen_id = 'toplevel_page_' . Admin_Menu::PAGE_SLUG;
		$screen    = get_current_screen();

		if ( $screen_id !== $hook_suffix || ! $screen || $screen_id !== $screen->id ) {
			return;
		}

		wp_enqueue_media();

		$asset_file  = ITSDZ_PLUGIN_DIR . 'build/admin.asset.php';
		$script_file = ITSDZ_PLUGIN_DIR . 'build/admin.js';

		if ( ! is_readable( $asset_file ) || ! is_readable( $script_file ) ) {
			return;
		}

		$asset = require $asset_file;

		wp_enqueue_script(
			self::HANDLE,
			ITSDZ_PLUGIN_URL . 'build/admin.js',
			$asset['dependencies'],
			$asset['version'],
			true
		);

		$style_file = ITSDZ_PLUGIN_DIR . 'build/style-admin.css';

		if ( is_readable( $style_file ) ) {
			wp_enqueue_style(
				self::HANDLE,
				ITSDZ_PLUGIN_URL . 'build/style-admin.css',
				array( 'wp-components' ),
				(string) filemtime( $style_file )
			);
		}

		wp_add_inline_script(
			self::HANDLE,
			'window.itsdzAdmin = ' . wp_json_encode( $this->configuration() ) . ';',
			'before'
		);
		wp_set_script_translations( self::HANDLE, 'doczur', ITSDZ_PLUGIN_DIR . 'languages' );
	}

	/**
	 * Build runtime configuration without exposing secrets.
	 *
	 * @return array<string, mixed>
	 */
	private function configuration() {
		$current_user = wp_get_current_user();

		return array(
			'pluginVersion' => ITSDZ_VERSION,
			'restNonce'     => wp_create_nonce( 'wp_rest' ),
			'restRoot'      => esc_url_raw( rest_url() ),
			'siteName'      => get_bloginfo( 'name' ),
			'user'          => array(
				'displayName' => $current_user->display_name,
				'id'          => $current_user->ID,
			),
		);
	}
}
