<?php
/**
 * Frontend documentation data access.
 *
 * @package ItsDZ\Doczur\Frontend
 */

namespace ItsDZ\Doczur\Frontend;

use ItsDZ\Doczur\PostTypes\Article_Post_Type;
use ItsDZ\Doczur\PostTypes\KB_Post_Type;

defined( 'ABSPATH' ) || exit;

/**
 * Supplies public templates with bounded, reusable query results.
 */
final class Documentation {
	/**
	 * Per-request article cache.
	 *
	 * @var array<int, \WP_Post[]>
	 */
	private static $articles = array();

	/**
	 * Resolve a published project.
	 *
	 * @param int $kb_id Project ID.
	 * @return \WP_Post|null
	 */
	public static function get_kb( $kb_id ) {
		$kb = get_post( $kb_id );

		return $kb && KB_Post_Type::POST_TYPE === $kb->post_type && 'publish' === $kb->post_status ? $kb : null;
	}

	/**
	 * Resolve the published project for an article.
	 *
	 * @param int $article_id Article ID.
	 * @return \WP_Post|null
	 */
	public static function get_article_kb( $article_id ) {
		return self::get_kb( absint( get_post_meta( $article_id, '_itsdz_kb_id', true ) ) );
	}

	/**
	 * Get all public articles for a project in navigation order.
	 *
	 * @param int $kb_id Project ID.
	 * @return \WP_Post[]
	 */
	public static function get_articles( $kb_id ) {
		$kb_id = absint( $kb_id );

		if ( ! isset( self::$articles[ $kb_id ] ) ) {
			self::$articles[ $kb_id ] = get_posts(
				array(
					'meta_key'       => '_itsdz_kb_id', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
					'meta_value'     => $kb_id, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
					'orderby'        => array(
						'menu_order' => 'ASC',
						'date'       => 'ASC',
					),
					'posts_per_page' => 500, // phpcs:ignore WordPress.WP.PostsPerPage.posts_per_page_posts_per_page -- One cached tree query is required for complete navigation.
					'post_status'    => 'publish',
					'post_type'      => Article_Post_Type::POST_TYPE,
				)
			);
		}

		return self::$articles[ $kb_id ];
	}

	/**
	 * Group articles by their primary section.
	 *
	 * @param int $kb_id Project ID.
	 * @return array<int, array{term: \WP_Term|null, articles: \WP_Post[]}>
	 */
	public static function get_groups( $kb_id ) {
		$groups = array();

		foreach ( self::get_articles( $kb_id ) as $article ) {
			$terms      = get_the_terms( $article, 'itsdz_section' );
			$term       = is_array( $terms ) ? reset( $terms ) : null;
			$section_id = $term instanceof \WP_Term ? $term->term_id : 0;

			if ( ! isset( $groups[ $section_id ] ) ) {
				$groups[ $section_id ] = array(
					'term'     => $term instanceof \WP_Term ? $term : null,
					'articles' => array(),
				);
			}

			$groups[ $section_id ]['articles'][] = $article;
		}

		uasort(
			$groups,
			static function ( $left, $right ) {
				if ( null === $left['term'] ) {
					return 1;
				}
				if ( null === $right['term'] ) {
					return -1;
				}
				return strcasecmp( $left['term']->name, $right['term']->name );
			}
		);

		return $groups;
	}

	/**
	 * Find previous and next articles in project order.
	 *
	 * @param int $kb_id     Project ID.
	 * @param int $article_id Current article ID.
	 * @return array{previous: \WP_Post|null, next: \WP_Post|null}
	 */
	public static function get_adjacent( $kb_id, $article_id ) {
		$articles = self::get_articles( $kb_id );
		$ids      = wp_list_pluck( $articles, 'ID' );
		$position = array_search( absint( $article_id ), $ids, true );

		if ( false === $position ) {
			return array(
				'previous' => null,
				'next'     => null,
			);
		}

		return array(
			'previous' => $position > 0 ? $articles[ $position - 1 ] : null,
			'next'     => isset( $articles[ $position + 1 ] ) ? $articles[ $position + 1 ] : null,
		);
	}

	/**
	 * Select related articles sharing a section or tag.
	 *
	 * @param int $kb_id      Project ID.
	 * @param int $article_id Current article ID.
	 * @param int $limit      Maximum results.
	 * @return \WP_Post[]
	 */
	public static function get_related( $kb_id, $article_id, $limit = 3 ) {
		$current_terms = self::get_term_ids( $article_id );

		if ( ! $current_terms ) {
			return array();
		}

		$related = array();

		foreach ( self::get_articles( $kb_id ) as $candidate ) {
			if ( absint( $article_id ) === $candidate->ID ) {
				continue;
			}

			$candidate_terms = self::get_term_ids( $candidate->ID );

			if ( array_intersect( $current_terms, $candidate_terms ) ) {
				$related[] = $candidate;
			}

			if ( count( $related ) >= $limit ) {
				break;
			}
		}

		return $related;
	}

	/**
	 * Read cached section and tag IDs for one article.
	 *
	 * WP_Query primes the term cache for the complete navigation result, so this
	 * avoids issuing a term query for every related-article candidate.
	 *
	 * @param int $article_id Article ID.
	 * @return string[]
	 */
	private static function get_term_ids( $article_id ) {
		$term_ids = array();

		foreach ( array( 'itsdz_section', 'itsdz_tag' ) as $taxonomy ) {
			$terms = get_the_terms( $article_id, $taxonomy );

			if ( is_array( $terms ) ) {
				foreach ( $terms as $term ) {
					$term_ids[] = $taxonomy . ':' . absint( $term->term_id );
				}
			}
		}

		return array_values( array_unique( $term_ids ) );
	}
}
