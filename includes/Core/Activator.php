<?php
/**
 * Plugin activation.
 *
 * @package ItsDZ\Doczur\Core
 */

namespace ItsDZ\Doczur\Core;

use ItsDZ\Doczur\Core\Migrations\Migrator;
use ItsDZ\Doczur\PostTypes\Article_Post_Type;
use ItsDZ\Doczur\PostTypes\KB_Post_Type;
use ItsDZ\Doczur\Security\Capabilities;
use ItsDZ\Doczur\Taxonomies\Section_Taxonomy;
use ItsDZ\Doczur\Taxonomies\Tag_Taxonomy;
use ItsDZ\Doczur\Taxonomies\Version_Taxonomy;

defined( 'ABSPATH' ) || exit;

/**
 * Runs lightweight activation tasks.
 */
final class Activator {
	/**
	 * Activate Nirdeshio.
	 *
	 * @return void
	 */
	public static function activate() {
		if ( version_compare( PHP_VERSION, '8.0', '<' ) ) {
			deactivate_plugins( plugin_basename( ITSDZ_PLUGIN_FILE ) );

			wp_die(
				esc_html__( 'Nirdeshio requires PHP 8.0 or newer.', 'itsmanzur-docs' ),
				esc_html__( 'Plugin activation failed', 'itsmanzur-docs' ),
				array( 'back_link' => true )
			);
		}

		Capabilities::install();

		$kb_post_type      = new KB_Post_Type();
		$article_post_type = new Article_Post_Type();
		$section_taxonomy  = new Section_Taxonomy();
		$tag_taxonomy      = new Tag_Taxonomy();
		$version_taxonomy  = new Version_Taxonomy();

		$kb_post_type->register_post_type();
		$article_post_type->register_post_type();
		$section_taxonomy->register_taxonomy();
		$tag_taxonomy->register_taxonomy();
		$version_taxonomy->register_taxonomy();

		$migrator = new Migrator();
		$migrator->run();

		flush_rewrite_rules();
	}
}
