<?php
/**
 * Frontend documentation data access.
 *
 * @package ItsDZ\Doczur\Frontend
 */

namespace ItsDZ\Doczur\Frontend;

use ItsDZ\Doczur\Analytics\View_Tracker;
use ItsDZ\Doczur\PostTypes\Article_Post_Type;
use ItsDZ\Doczur\PostTypes\KB_Post_Type;

defined( 'ABSPATH' ) || exit;

/**
 * Supplies public templates with bounded, reusable query results.
 */
final class Documentation {
	/**
	 * Default cap for the public navigation query.
	 */
	const DEFAULT_MAX_ARTICLES = 2000;

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
			/**
			 * Filter the maximum published articles loaded for public navigation.
			 *
			 * @param int $limit Maximum articles per project.
			 */
			$limit = (int) apply_filters( 'itsdz_max_public_articles', self::DEFAULT_MAX_ARTICLES );
			$limit = max( 1, $limit );

			self::$articles[ $kb_id ] = get_posts(
				array(
					'meta_key'       => '_itsdz_kb_id', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
					'meta_value'     => $kb_id, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
					'orderby'        => array(
						'menu_order' => 'ASC',
						'date'       => 'ASC',
					),
					'posts_per_page' => $limit, // phpcs:ignore WordPress.WP.PostsPerPage.posts_per_page_posts_per_page -- One cached tree query is required for complete navigation.
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
	 * Nested section tree for the public left nav.
	 *
	 * Empty parent sections still appear when they have children. Unsectioned
	 * articles are appended as a final group.
	 *
	 * @param int $kb_id Project ID.
	 * @return array<int, array{term: \WP_Term|null, articles: \WP_Post[], children: array}>
	 */
	public static function get_nav_tree( $kb_id ) {
		$groups = self::get_groups( $kb_id );
		$needed = array();
		$loose  = array();

		foreach ( $groups as $section_id => $group ) {
			if ( $group['term'] instanceof \WP_Term ) {
				$needed[ $section_id ] = $group['term'];
			} else {
				$loose = $group['articles'];
			}
		}

		foreach ( $needed as $term ) {
			$parent_id = (int) $term->parent;

			while ( $parent_id > 0 && ! isset( $needed[ $parent_id ] ) ) {
				$parent = get_term( $parent_id, 'itsdz_section' );

				if ( ! $parent instanceof \WP_Term ) {
					break;
				}

				$needed[ $parent_id ] = $parent;
				$parent_id            = (int) $parent->parent;
			}
		}

		$children = array();

		foreach ( $needed as $term_id => $term ) {
			$children[ (int) $term->parent ][] = (int) $term_id;
		}

		$build = static function ( $parent_id ) use ( &$build, $children, $needed, $groups ) {
			$nodes = array();

			if ( empty( $children[ $parent_id ] ) ) {
				return $nodes;
			}

			foreach ( $children[ $parent_id ] as $term_id ) {
				$nodes[] = array(
					'term'     => $needed[ $term_id ],
					'articles' => isset( $groups[ $term_id ] ) ? $groups[ $term_id ]['articles'] : array(),
					'children' => $build( $term_id ),
				);
			}

			usort(
				$nodes,
				static function ( $left, $right ) {
					return strcasecmp( $left['term']->name, $right['term']->name );
				}
			);

			return $nodes;
		};

		$tree = $build( 0 );

		if ( $loose ) {
			$tree[] = array(
				'term'     => null,
				'articles' => $loose,
				'children' => array(),
			);
		}

		return $tree;
	}

	/**
	 * Whether a nav node or any descendant contains the current article.
	 *
	 * @param array{term: \WP_Term|null, articles: \WP_Post[], children: array} $node       Nav node.
	 * @param int                                                               $article_id Current article ID.
	 * @return bool
	 */
	public static function nav_contains_article( $node, $article_id ) {
		$article_id = absint( $article_id );

		foreach ( $node['articles'] as $article ) {
			if ( (int) $article->ID === $article_id ) {
				return true;
			}
		}

		foreach ( $node['children'] as $child ) {
			if ( self::nav_contains_article( $child, $article_id ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Read a stored on/off project flag.
	 *
	 * @param int    $kb_id   Project ID.
	 * @param string $key     Meta key.
	 * @param bool   $default_value Default when the meta is empty.
	 * @return bool
	 */
	public static function ui_flag( $kb_id, $key, $default_value = true ) {
		$value = get_post_meta( absint( $kb_id ), $key, true );

		if ( '' === $value || false === $value ) {
			return $default_value;
		}

		return '1' === (string) $value;
	}

	/**
	 * Header shortcut links for the public shell.
	 *
	 * @param int $kb_id Project ID.
	 * @return array<int, array{label: string, url: string}>
	 */
	public static function header_links( $kb_id ) {
		$decoded = json_decode( (string) get_post_meta( absint( $kb_id ), '_itsdz_kb_header_links', true ), true );

		if ( ! is_array( $decoded ) ) {
			return array();
		}

		$links = array();

		foreach ( $decoded as $item ) {
			if ( ! is_array( $item ) || empty( $item['label'] ) || empty( $item['url'] ) ) {
				continue;
			}

			$links[] = array(
				'label' => (string) $item['label'],
				'url'   => (string) $item['url'],
			);
		}

		return $links;
	}

	/**
	 * First published documentation project (Free has one).
	 *
	 * @return \WP_Post|null
	 */
	public static function get_published_kb() {
		$posts = get_posts(
			array(
				'numberposts' => 1,
				'post_status' => 'publish',
				'post_type'   => KB_Post_Type::POST_TYPE,
			)
		);

		return $posts ? $posts[0] : null;
	}

	/**
	 * Popular published articles for empty-search suggestions.
	 *
	 * @param int $kb_id Project ID.
	 * @param int $limit Maximum results.
	 * @return array<int, array{title: string, url: string}>
	 */
	public static function get_popular_links( $kb_id, $limit = 5 ) {
		$articles = self::get_articles( $kb_id );
		$totals   = View_Tracker::get_kb_totals( $kb_id );

		usort(
			$articles,
			static function ( $left, $right ) use ( $totals ) {
				$left_views  = isset( $totals[ $left->ID ] ) ? (int) $totals[ $left->ID ] : 0;
				$right_views = isset( $totals[ $right->ID ] ) ? (int) $totals[ $right->ID ] : 0;

				if ( $left_views === $right_views ) {
					return strcmp( $right->post_date_gmt, $left->post_date_gmt );
				}

				return $right_views <=> $left_views;
			}
		);

		$links = array();

		foreach ( array_slice( $articles, 0, max( 1, $limit ) ) as $article ) {
			$links[] = array(
				'title' => get_the_title( $article ),
				'url'   => (string) get_permalink( $article ),
			);
		}

		return $links;
	}

	/**
	 * Published articles assigned to a section inside a project.
	 *
	 * @param int $kb_id      Project ID.
	 * @param int $section_id Section term ID.
	 * @return \WP_Post[]
	 */
	public static function get_section_articles( $kb_id, $section_id ) {
		$section_id = absint( $section_id );
		$matches    = array();

		foreach ( self::get_articles( $kb_id ) as $article ) {
			$terms = get_the_terms( $article, 'itsdz_section' );

			if ( ! is_array( $terms ) ) {
				continue;
			}

			foreach ( $terms as $term ) {
				if ( (int) $term->term_id === $section_id ) {
					$matches[] = $article;
					break;
				}
			}
		}

		return $matches;
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
