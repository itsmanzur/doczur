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
			'name'               => _x( 'Documentation Articles', 'post type general name', 'doczur' ),
			'singular_name'      => _x( 'Documentation Article', 'post type singular name', 'doczur' ),
			'menu_name'          => _x( 'Articles', 'admin menu', 'doczur' ),
			'name_admin_bar'     => _x( 'Documentation Article', 'add new from admin bar', 'doczur' ),
			'add_new'            => __( 'Add New', 'doczur' ),
			'add_new_item'       => __( 'Add New Documentation Article', 'doczur' ),
			'new_item'           => __( 'New Documentation Article', 'doczur' ),
			'edit_item'          => __( 'Edit Documentation Article', 'doczur' ),
			'view_item'          => __( 'View Documentation Article', 'doczur' ),
			'all_items'          => __( 'All Documentation Articles', 'doczur' ),
			'search_items'       => __( 'Search Documentation Articles', 'doczur' ),
			'not_found'          => __( 'No documentation articles found.', 'doczur' ),
			'not_found_in_trash' => __( 'No documentation articles found in Trash.', 'doczur' ),
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
				'supports'           => array( 'title', 'editor', 'excerpt', 'author', 'thumbnail', 'revisions', 'page-attributes' ),
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
