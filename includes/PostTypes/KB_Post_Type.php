<?php
/**
 * Documentation project post type.
 *
 * @package ItsDZ\Doczur\PostTypes
 */

namespace ItsDZ\Doczur\PostTypes;

use ItsDZ\Doczur\Core\Service;
use ItsDZ\Doczur\Frontend\Rewrite_Manager;
use ItsDZ\Doczur\Security\Capabilities;

defined( 'ABSPATH' ) || exit;

/**
 * Registers documentation projects.
 */
final class KB_Post_Type implements Service {
	/**
	 * Post type key.
	 */
	const POST_TYPE = 'itsdz_kb';

	/**
	 * Default public URL prefix for documentation.
	 */
	const DEFAULT_SLUG = 'docs';

	/**
	 * Option storing the active rewrite slug base.
	 */
	const SLUG_OPTION = 'itsdz_kb_slug_base';

	/**
	 * Register WordPress hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'init', array( $this, 'hydrate_rewrite_slug' ), 4 );
		add_action( 'init', array( $this, 'register_post_type' ), 5 );
	}

	/**
	 * Copy a previously saved slug base from post meta into the rewrite option.
	 *
	 * Existing installs stored the value only as `_itsdz_kb_slug_base`, which
	 * register_post_type() never read. One option keeps init cheap and lets
	 * both CPTs share the same prefix.
	 *
	 * @return void
	 */
	public function hydrate_rewrite_slug() {
		$stored = get_option( self::SLUG_OPTION, false );

		if ( is_string( $stored ) && '' !== sanitize_title( $stored ) ) {
			return;
		}

		$slug  = self::DEFAULT_SLUG;
		$posts = get_posts(
			array(
				'fields'         => 'ids',
				'orderby'        => 'ID',
				'order'          => 'ASC',
				'posts_per_page' => 1,
				'post_status'    => array( 'publish', 'draft', 'pending', 'private' ),
				'post_type'      => self::POST_TYPE,
			)
		);

		if ( $posts ) {
			$meta  = get_post_meta( (int) $posts[0], '_itsdz_kb_slug_base', true );
			$clean = is_string( $meta ) ? sanitize_title( $meta ) : '';

			if ( '' !== $clean ) {
				$slug = $clean;
			}
		}

		update_option( self::SLUG_OPTION, $slug, false );

		if ( self::DEFAULT_SLUG !== $slug ) {
			update_option( Rewrite_Manager::FLUSH_OPTION, '1', false );
		}
	}

	/**
	 * Public URL prefix used by both the project and article post types.
	 *
	 * @return string
	 */
	public static function rewrite_slug() {
		$stored = get_option( self::SLUG_OPTION, self::DEFAULT_SLUG );
		$slug   = is_string( $stored ) ? sanitize_title( $stored ) : '';

		return '' !== $slug ? $slug : self::DEFAULT_SLUG;
	}

	/**
	 * Persist a new slug base and request a rewrite flush on the next request.
	 *
	 * Post types are already registered by the time Settings REST runs, so the
	 * flush must wait until init has registered the new slug.
	 *
	 * @param mixed $slug Raw slug from settings or setup.
	 * @return void
	 */
	public static function persist_rewrite_slug( $slug ) {
		$slug = is_scalar( $slug ) ? sanitize_title( (string) $slug ) : '';

		if ( '' === $slug ) {
			$slug = self::DEFAULT_SLUG;
		}

		$current = self::rewrite_slug();
		update_option( self::SLUG_OPTION, $slug, false );

		if ( $current !== $slug ) {
			update_option( Rewrite_Manager::FLUSH_OPTION, '1', false );
		}
	}

	/**
	 * Register the documentation project post type.
	 *
	 * @return void
	 */
	public function register_post_type() {
		$labels = array(
			'name'               => _x( 'Documentation Projects', 'post type general name', 'itsmanzur-docs' ),
			'singular_name'      => _x( 'Documentation Project', 'post type singular name', 'itsmanzur-docs' ),
			'menu_name'          => _x( 'Nirdeshio', 'admin menu', 'itsmanzur-docs' ),
			'name_admin_bar'     => _x( 'Documentation Project', 'add new from admin bar', 'itsmanzur-docs' ),
			'add_new'            => __( 'Add New', 'itsmanzur-docs' ),
			'add_new_item'       => __( 'Add New Documentation Project', 'itsmanzur-docs' ),
			'new_item'           => __( 'New Documentation Project', 'itsmanzur-docs' ),
			'edit_item'          => __( 'Edit Documentation Project', 'itsmanzur-docs' ),
			'view_item'          => __( 'View Documentation Project', 'itsmanzur-docs' ),
			'all_items'          => __( 'All Documentation Projects', 'itsmanzur-docs' ),
			'search_items'       => __( 'Search Documentation Projects', 'itsmanzur-docs' ),
			'not_found'          => __( 'No documentation projects found.', 'itsmanzur-docs' ),
			'not_found_in_trash' => __( 'No documentation projects found in Trash.', 'itsmanzur-docs' ),
		);

		register_post_type(
			self::POST_TYPE,
			array(
				'labels'             => $labels,
				'public'             => true,
				'publicly_queryable' => true,
				'show_ui'            => true,
				'show_in_menu'       => false,
				'show_in_rest'       => true,
				'has_archive'        => false,
				'rewrite'            => array(
					'slug'       => self::rewrite_slug(),
					'with_front' => false,
				),
				'menu_icon'          => 'dashicons-media-document',
				'supports'           => array( 'title', 'editor', 'excerpt', 'thumbnail', 'revisions' ),
				'capabilities'       => Capabilities::post_type_map(),
				'map_meta_cap'       => false,
			)
		);
	}
}
