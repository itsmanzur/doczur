<?php
/**
 * Conditional public documentation assets.
 *
 * @package ItsDZ\Doczur\Frontend
 */

namespace ItsDZ\Doczur\Frontend;

use ItsDZ\Doczur\Core\Service;
use ItsDZ\Doczur\PostTypes\Article_Post_Type;
use ItsDZ\Doczur\PostTypes\KB_Post_Type;
use ItsDZ\Doczur\Taxonomies\Section_Taxonomy;

defined( 'ABSPATH' ) || exit;

/**
 * Loads the small frontend bundle only on Doczur pages.
 */
final class Assets implements Service {
	/**
	 * Asset handle.
	 */
	const HANDLE = 'itsdz-frontend';

	/**
	 * Register WordPress hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue' ) );
	}

	/**
	 * Enqueue compiled assets on documentation requests only.
	 *
	 * @return void
	 */
	public function enqueue() {
		$is_docs_singular = is_singular( array( KB_Post_Type::POST_TYPE, Article_Post_Type::POST_TYPE ) );
		$is_section       = is_tax( Section_Taxonomy::TAXONOMY );

		if ( ! $is_docs_singular && ! $is_section ) {
			return;
		}

		$asset_file  = ITSDZ_PLUGIN_DIR . 'build/frontend.asset.php';
		$script_file = ITSDZ_PLUGIN_DIR . 'build/frontend.js';
		$style_file  = ITSDZ_PLUGIN_DIR . 'build/style-frontend.css';

		if ( ! is_readable( $asset_file ) || ! is_readable( $script_file ) || ! is_readable( $style_file ) ) {
			return;
		}

		$asset = require $asset_file;

		wp_enqueue_script(
			self::HANDLE,
			ITSDZ_PLUGIN_URL . 'build/frontend.js',
			$asset['dependencies'],
			$asset['version'],
			true
		);
		wp_enqueue_style(
			self::HANDLE,
			ITSDZ_PLUGIN_URL . 'build/style-frontend.css',
			array(),
			(string) filemtime( $style_file )
		);

		if ( is_singular( KB_Post_Type::POST_TYPE ) ) {
			$kb_id = get_queried_object_id();
		} elseif ( $is_section ) {
			$kb    = Documentation::get_published_kb();
			$kb_id = $kb ? $kb->ID : 0;
		} else {
			$kb_id = absint( get_post_meta( get_queried_object_id(), '_itsdz_kb_id', true ) );
		}

		$color = sanitize_hex_color( get_post_meta( $kb_id, '_itsdz_kb_brand_color', true ) );

		if ( $color ) {
			wp_add_inline_style(
				self::HANDLE,
				'.itsdz-docs{--itsdz-brand:' . $color . ';--itsdz-brand-strong:color-mix(in srgb,' . $color . ' 78%,#000);}'
			);
		}

		$custom_css = (string) get_post_meta( $kb_id, '_itsdz_kb_custom_css', true );

		if ( $custom_css ) {
			wp_add_inline_style( self::HANDLE, $custom_css );
		}

		wp_set_script_translations( self::HANDLE, 'doczur', ITSDZ_PLUGIN_DIR . 'languages' );
	}
}
