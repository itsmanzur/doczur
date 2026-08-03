<?php
/**
 * Documentation tag taxonomy.
 *
 * @package ItsDZ\Doczur\Taxonomies
 */

namespace ItsDZ\Doczur\Taxonomies;

use ItsDZ\Doczur\Core\Service;
use ItsDZ\Doczur\PostTypes\Article_Post_Type;
use ItsDZ\Doczur\Security\Capabilities;

defined( 'ABSPATH' ) || exit;

/**
 * Registers article tags.
 */
final class Tag_Taxonomy implements Service {
	/**
	 * Taxonomy key.
	 */
	const TAXONOMY = 'itsdz_tag';

	/**
	 * Register WordPress hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'init', array( $this, 'register_taxonomy' ), 5 );
	}

	/**
	 * Register the tag taxonomy.
	 *
	 * @return void
	 */
	public function register_taxonomy() {
		register_taxonomy(
			self::TAXONOMY,
			array( Article_Post_Type::POST_TYPE ),
			array(
				'labels'            => array(
					'name'                       => _x( 'Documentation Tags', 'taxonomy general name', 'doczur' ),
					'singular_name'              => _x( 'Documentation Tag', 'taxonomy singular name', 'doczur' ),
					'search_items'               => __( 'Search Documentation Tags', 'doczur' ),
					'popular_items'              => __( 'Popular Documentation Tags', 'doczur' ),
					'all_items'                  => __( 'All Documentation Tags', 'doczur' ),
					'edit_item'                  => __( 'Edit Documentation Tag', 'doczur' ),
					'update_item'                => __( 'Update Documentation Tag', 'doczur' ),
					'add_new_item'               => __( 'Add New Documentation Tag', 'doczur' ),
					'new_item_name'              => __( 'New Documentation Tag Name', 'doczur' ),
					'separate_items_with_commas' => __( 'Separate documentation tags with commas', 'doczur' ),
					'add_or_remove_items'        => __( 'Add or remove documentation tags', 'doczur' ),
					'choose_from_most_used'      => __( 'Choose from the most used documentation tags', 'doczur' ),
					'menu_name'                  => __( 'Tags', 'doczur' ),
				),
				'public'            => true,
				'hierarchical'      => false,
				'show_ui'           => true,
				'show_admin_column' => true,
				'show_in_rest'      => true,
				'capabilities'      => Capabilities::taxonomy_map(),
				'rewrite'           => array(
					'slug'       => 'docs/tag',
					'with_front' => false,
				),
			)
		);
	}
}
