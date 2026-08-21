<?php
/**
 * Documentation article post type.
 *
 * @package ItsDZ\Doczur\PostTypes
 */

namespace ItsDZ\Doczur\PostTypes;

use ItsDZ\Doczur\Core\Service;
use ItsDZ\Doczur\Security\Capabilities;

defined( 'ABSPATH' ) || exit;

/**
 * Registers documentation articles.
 */
final class Article_Post_Type implements Service {
	/**
	 * Post type key.
	 */
	const POST_TYPE = 'itsdz_doc';

	/**
	 * Register WordPress hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'init', array( $this, 'register_post_type' ), 5 );
	}

	/**
	 * Register the article post type.
	 *
	 * @return void
	 */
	public function register_post_type() {
		$labels = array(
			'name'               => _x( 'Documentation Articles', 'post type general name', 'itsmanzur-docs' ),
			'singular_name'      => _x( 'Documentation Article', 'post type singular name', 'itsmanzur-docs' ),
			'menu_name'          => _x( 'Articles', 'admin menu', 'itsmanzur-docs' ),
			'name_admin_bar'     => _x( 'Documentation Article', 'add new from admin bar', 'itsmanzur-docs' ),
			'add_new'            => __( 'Add New', 'itsmanzur-docs' ),
			'add_new_item'       => __( 'Add New Documentation Article', 'itsmanzur-docs' ),
			'new_item'           => __( 'New Documentation Article', 'itsmanzur-docs' ),
			'edit_item'          => __( 'Edit Documentation Article', 'itsmanzur-docs' ),
			'view_item'          => __( 'View Documentation Article', 'itsmanzur-docs' ),
			'all_items'          => __( 'All Documentation Articles', 'itsmanzur-docs' ),
			'search_items'       => __( 'Search Documentation Articles', 'itsmanzur-docs' ),
			'not_found'          => __( 'No documentation articles found.', 'itsmanzur-docs' ),
			'not_found_in_trash' => __( 'No documentation articles found in Trash.', 'itsmanzur-docs' ),
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
					'slug'       => 'docs/article',
					'with_front' => false,
				),
				'menu_icon'          => 'dashicons-text-page',
				// 'custom-fields' is required for WP_REST_Posts_Controller to
				// expose a `meta` property at all — without it, none of the
				// registered post meta (however show_in_rest is set) is
				// readable or writable through the core REST API, which is
				// what the Nirdeshio sidebar panel in the block editor uses.
				'supports'           => array( 'title', 'editor', 'excerpt', 'author', 'thumbnail', 'revisions', 'page-attributes', 'custom-fields' ),
				'taxonomies'         => array( 'itsdz_section', 'itsdz_tag', 'itsdz_version' ),
				'capabilities'       => Capabilities::post_type_map(),
				'map_meta_cap'       => false,
			)
		);

		// Keep the article route ahead of the KB attachment rewrite rules.
		add_rewrite_rule(
			'^docs/article/([^/]+)/?$',
			'index.php?itsdz_doc=$matches[1]',
			'top'
		);
	}
}
