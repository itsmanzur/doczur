<?php
/**
 * Documentation project post type.
 *
 * @package ItsDZ\Doczur\PostTypes
 */

namespace ItsDZ\Doczur\PostTypes;

use ItsDZ\Doczur\Core\Service;
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
	 * Register WordPress hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'init', array( $this, 'register_post_type' ), 5 );
	}

	/**
	 * Register the documentation project post type.
	 *
	 * @return void
	 */
	public function register_post_type() {
		$labels = array(
			'name'               => _x( 'Documentation Projects', 'post type general name', 'doczur' ),
			'singular_name'      => _x( 'Documentation Project', 'post type singular name', 'doczur' ),
			'menu_name'          => _x( 'Doczur', 'admin menu', 'doczur' ),
			'name_admin_bar'     => _x( 'Documentation Project', 'add new from admin bar', 'doczur' ),
			'add_new'            => __( 'Add New', 'doczur' ),
			'add_new_item'       => __( 'Add New Documentation Project', 'doczur' ),
			'new_item'           => __( 'New Documentation Project', 'doczur' ),
			'edit_item'          => __( 'Edit Documentation Project', 'doczur' ),
			'view_item'          => __( 'View Documentation Project', 'doczur' ),
			'all_items'          => __( 'All Documentation Projects', 'doczur' ),
			'search_items'       => __( 'Search Documentation Projects', 'doczur' ),
			'not_found'          => __( 'No documentation projects found.', 'doczur' ),
			'not_found_in_trash' => __( 'No documentation projects found in Trash.', 'doczur' ),
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
					'slug'       => 'docs',
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
