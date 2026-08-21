<?php
/**
 * Glossary term taxonomy.
 *
 * A glossary entry is stored as a term: the term name is the word itself and
 * the term description is its definition. Aliases (plurals, abbreviations,
 * alternative spellings) live in term meta.
 *
 * @package ItsDZ\Doczur\Taxonomies
 */

namespace ItsDZ\Doczur\Taxonomies;

use ItsDZ\Doczur\Core\Service;
use ItsDZ\Doczur\PostTypes\Article_Post_Type;
use ItsDZ\Doczur\Security\Capabilities;
use ItsDZ\Doczur\Utils\Cache;

defined( 'ABSPATH' ) || exit;

/**
 * Registers documentation glossary terms.
 */
final class Glossary_Taxonomy implements Service {
	/**
	 * Taxonomy key.
	 */
	const TAXONOMY = 'itsdz_glossary';

	/**
	 * Term meta holding comma-separated alternative spellings.
	 */
	const ALIASES_META = 'itsdz_glossary_aliases';

	/**
	 * Register WordPress hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'init', array( $this, 'register_taxonomy' ), 5 );
		add_action( 'init', array( $this, 'register_term_meta' ), 6 );

		// Invalidate Glossary_Highlighter's cache whenever a term, its
		// definition, or its aliases change.
		add_action( 'created_' . self::TAXONOMY, array( $this, 'bump_cache' ) );
		add_action( 'edited_' . self::TAXONOMY, array( $this, 'bump_cache' ) );
		add_action( 'delete_' . self::TAXONOMY, array( $this, 'bump_cache' ) );
		add_action( 'added_term_meta', array( $this, 'bump_cache_on_alias_change' ), 10, 3 );
		add_action( 'updated_term_meta', array( $this, 'bump_cache_on_alias_change' ), 10, 3 );
		add_action( 'deleted_term_meta', array( $this, 'bump_cache_on_alias_change' ), 10, 3 );
	}

	/**
	 * Bump the glossary highlighting cache generation.
	 *
	 * @return void
	 */
	public function bump_cache() {
		Cache::bump_generation( 'glossary', 0 );
	}

	/**
	 * Bump the cache only when the meta key changed is the alias list.
	 *
	 * Added/updated/deleted_term_meta fire for every taxonomy's term meta,
	 * not just this one, so the key must be checked before invalidating.
	 *
	 * @param int    $meta_id Meta row ID (unused).
	 * @param int    $term_id Term ID (unused).
	 * @param string $meta_key Meta key.
	 * @return void
	 */
	public function bump_cache_on_alias_change( $meta_id, $term_id, $meta_key ) {
		unset( $meta_id, $term_id );

		if ( self::ALIASES_META === $meta_key ) {
			$this->bump_cache();
		}
	}

	/**
	 * Register the glossary taxonomy.
	 *
	 * @return void
	 */
	public function register_taxonomy() {
		register_taxonomy(
			self::TAXONOMY,
			array( Article_Post_Type::POST_TYPE ),
			array(
				'labels'            => array(
					'name'          => _x( 'Glossary Terms', 'taxonomy general name', 'itsmanzur-docs' ),
					'singular_name' => _x( 'Glossary Term', 'taxonomy singular name', 'itsmanzur-docs' ),
					'search_items'  => __( 'Search Glossary Terms', 'itsmanzur-docs' ),
					'all_items'     => __( 'All Glossary Terms', 'itsmanzur-docs' ),
					'edit_item'     => __( 'Edit Glossary Term', 'itsmanzur-docs' ),
					'update_item'   => __( 'Update Glossary Term', 'itsmanzur-docs' ),
					'add_new_item'  => __( 'Add New Glossary Term', 'itsmanzur-docs' ),
					'new_item_name' => __( 'New Glossary Term', 'itsmanzur-docs' ),
					'menu_name'     => __( 'Glossary', 'itsmanzur-docs' ),
				),
				'public'            => false,
				'show_ui'           => true,
				'show_admin_column' => false,
				'show_in_rest'      => true,
				'hierarchical'      => false,
				'capabilities'      => Capabilities::taxonomy_map(),
				'rewrite'           => false,
			)
		);
	}

	/**
	 * Register the alias term meta.
	 *
	 * @return void
	 */
	public function register_term_meta() {
		register_term_meta(
			self::TAXONOMY,
			self::ALIASES_META,
			array(
				'auth_callback'     => array( $this, 'can_edit_meta' ),
				'default'           => '',
				'sanitize_callback' => array( $this, 'sanitize_aliases' ),
				'show_in_rest'      => true,
				'single'            => true,
				'type'              => 'string',
			)
		);
	}

	/**
	 * Authorize glossary meta writes.
	 *
	 * @return bool
	 */
	public function can_edit_meta() {
		return current_user_can( Capabilities::MANAGE_DOCS );
	}

	/**
	 * Normalize the comma-separated alias list.
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	public function sanitize_aliases( $value ) {
		if ( ! is_scalar( $value ) ) {
			return '';
		}

		$aliases = array_filter(
			array_map( 'trim', explode( ',', sanitize_text_field( (string) $value ) ) )
		);

		return implode( ', ', array_unique( $aliases ) );
	}

	/**
	 * Return every glossary entry as label/definition pairs.
	 *
	 * @return array<int, array{term_id: int, term: string, definition: string, aliases: string[]}>
	 */
	public static function get_entries() {
		$terms = get_terms(
			array(
				'hide_empty' => false,
				'orderby'    => 'name',
				'order'      => 'ASC',
				'taxonomy'   => self::TAXONOMY,
			)
		);

		if ( ! is_array( $terms ) ) {
			return array();
		}

		$entries = array();

		foreach ( $terms as $term ) {
			if ( ! $term instanceof \WP_Term ) {
				continue;
			}

			$raw_aliases = (string) get_term_meta( $term->term_id, self::ALIASES_META, true );
			$aliases     = array_filter( array_map( 'trim', explode( ',', $raw_aliases ) ) );

			$entries[] = array(
				'term_id'    => $term->term_id,
				'term'       => $term->name,
				'definition' => $term->description,
				'aliases'    => array_values( $aliases ),
			);
		}

		return $entries;
	}
}
