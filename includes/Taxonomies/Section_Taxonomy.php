<?php
/**
 * Documentation section taxonomy.
 *
 * @package ItsDZ\Doczur\Taxonomies
 */

namespace ItsDZ\Doczur\Taxonomies;

use ItsDZ\Doczur\Core\Service;
use ItsDZ\Doczur\PostTypes\Article_Post_Type;
use ItsDZ\Doczur\Security\Capabilities;

defined( 'ABSPATH' ) || exit;

/**
 * Registers hierarchical article sections.
 */
final class Section_Taxonomy implements Service {
	/**
	 * Taxonomy key.
	 */
	const TAXONOMY = 'itsdz_section';

	/**
	 * Register WordPress hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'init', array( $this, 'register_taxonomy' ), 5 );
	}

	/**
	 * Register the section taxonomy.
	 *
	 * @return void
	 */
	public function register_taxonomy() {
		register_taxonomy(
			self::TAXONOMY,
			array( Article_Post_Type::POST_TYPE ),
			array(
				'labels'            => array(
					'name'              => _x( 'Sections', 'taxonomy general name', 'doczur' ),
					'singular_name'     => _x( 'Section', 'taxonomy singular name', 'doczur' ),
					'search_items'      => __( 'Search Sections', 'doczur' ),
					'all_items'         => __( 'All Sections', 'doczur' ),
					'parent_item'       => __( 'Parent Section', 'doczur' ),
					'parent_item_colon' => __( 'Parent Section:', 'doczur' ),
					'edit_item'         => __( 'Edit Section', 'doczur' ),
					'update_item'       => __( 'Update Section', 'doczur' ),
					'add_new_item'      => __( 'Add New Section', 'doczur' ),
					'new_item_name'     => __( 'New Section Name', 'doczur' ),
					'menu_name'         => __( 'Sections', 'doczur' ),
				),
				'public'            => true,
				'hierarchical'      => true,
				'show_ui'           => true,
				'show_admin_column' => true,
				'show_in_rest'      => true,
				'capabilities'      => Capabilities::taxonomy_map(),
				'rewrite'           => array(
					'slug'       => 'docs/section',
					'with_front' => false,
				),
			)
		);
	}
}
