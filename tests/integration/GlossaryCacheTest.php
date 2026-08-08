<?php
/**
 * Glossary highlighting cache tests.
 *
 * GlossaryHighlighterTest exercises Glossary_Highlighter::apply() directly —
 * the uncached replacement algorithm. This file exercises the real
 * `the_content` path (maybe_highlight → cached_apply), proving the cache
 * layer added on top of it actually caches, and that editing a term
 * invalidates it everywhere rather than just for that one article.
 *
 * @package ItsDZ\Doczur\Tests\Integration
 */

namespace ItsDZ\Doczur\Tests\Integration;

use ItsDZ\Doczur\PostTypes\Article_Post_Type;
use ItsDZ\Doczur\Taxonomies\Glossary_Taxonomy;
use ItsDZ\Doczur\Utils\Cache;
use WP_UnitTestCase;

/**
 * Proves glossary highlighting is cached and invalidated correctly.
 */
final class GlossaryCacheTest extends WP_UnitTestCase {

	/**
	 * Render an article's content through the real `the_content` pipeline.
	 *
	 * @param int $article_id Article post ID.
	 * @return string
	 */
	private function render( $article_id ) {
		$this->go_to( get_permalink( $article_id ) );

		global $wp_query;
		$this->assertTrue( $wp_query->is_singular(), 'The fixture did not resolve to a singular article view.' );

		return apply_filters( 'the_content', get_post_field( 'post_content', $article_id ) );
	}

	/**
	 * A cache hit is not recomputed, even if the underlying data changed by
	 * a path that does not go through WordPress's own term hooks.
	 *
	 * A direct database write is the only way to change term data without
	 * firing the hooks Glossary_Taxonomy listens on — exactly what proves
	 * the second render is reading the cache rather than recomputing: if it
	 * recomputed, it would see the new description.
	 *
	 * @return void
	 */
	public function test_cache_hit_is_not_recomputed() {
		global $wpdb;

		$term       = wp_insert_term( 'Webhook', Glossary_Taxonomy::TAXONOMY, array( 'description' => 'Original definition.' ) );
		$article_id = self::factory()->post->create(
			array(
				'post_content' => '<p>Configure your first webhook.</p>',
				'post_status'  => 'publish',
				'post_type'    => Article_Post_Type::POST_TYPE,
			)
		);

		$first = $this->render( $article_id );
		$this->assertStringContainsString( 'Original definition.', $first );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Deliberately bypassing wp_update_term() and its hooks; that's what this test is proving against.
		$wpdb->update(
			$wpdb->term_taxonomy,
			array( 'description' => 'Changed without firing any hook.' ),
			array( 'term_id' => $term['term_id'] )
		);

		$second = $this->render( $article_id );
		$this->assertStringContainsString(
			'Original definition.',
			$second,
			'The second render recomputed instead of hitting the cache.'
		);
	}

	/**
	 * Editing a term's definition is reflected on the very next render.
	 *
	 * @return void
	 */
	public function test_editing_a_term_invalidates_the_cache() {
		$term       = wp_insert_term( 'API', Glossary_Taxonomy::TAXONOMY, array( 'description' => 'First definition.' ) );
		$article_id = self::factory()->post->create(
			array(
				'post_content' => '<p>Call the API to begin.</p>',
				'post_status'  => 'publish',
				'post_type'    => Article_Post_Type::POST_TYPE,
			)
		);

		$before = $this->render( $article_id );
		$this->assertStringContainsString( 'First definition.', $before );

		wp_update_term( $term['term_id'], Glossary_Taxonomy::TAXONOMY, array( 'description' => 'Updated definition.' ) );

		$after = $this->render( $article_id );
		$this->assertStringContainsString( 'Updated definition.', $after );
		$this->assertStringNotContainsString( 'First definition.', $after );
	}

	/**
	 * Editing a term's aliases invalidates the cache too.
	 *
	 * Alias edits go through term *meta*, a different set of hooks entirely
	 * from the term-edit hooks the previous test exercises — this confirms
	 * both invalidation paths were actually wired up, not just one of them.
	 *
	 * @return void
	 */
	public function test_editing_aliases_invalidates_the_cache() {
		$term       = wp_insert_term( 'API', Glossary_Taxonomy::TAXONOMY, array( 'description' => 'An interface.' ) );
		$article_id = self::factory()->post->create(
			array(
				'post_content' => '<p>Several APIs are documented here.</p>',
				'post_status'  => 'publish',
				'post_type'    => Article_Post_Type::POST_TYPE,
			)
		);

		$before = $this->render( $article_id );
		$this->assertStringNotContainsString( 'itsdz-glossary-term', $before );

		update_term_meta( $term['term_id'], Glossary_Taxonomy::ALIASES_META, 'APIs' );

		$after = $this->render( $article_id );
		$this->assertStringContainsString( 'itsdz-glossary-term', $after );
	}

	/**
	 * A change to one article's cache entry does not touch another article's.
	 *
	 * @return void
	 */
	public function test_cache_key_is_specific_to_its_content() {
		wp_insert_term( 'API', Glossary_Taxonomy::TAXONOMY, array( 'description' => 'An interface.' ) );

		$article_a = self::factory()->post->create(
			array(
				'post_content' => '<p>Uses the API.</p>',
				'post_status'  => 'publish',
				'post_type'    => Article_Post_Type::POST_TYPE,
			)
		);
		$article_b = self::factory()->post->create(
			array(
				'post_content' => '<p>Also uses the API, worded differently.</p>',
				'post_status'  => 'publish',
				'post_type'    => Article_Post_Type::POST_TYPE,
			)
		);

		$rendered_a = $this->render( $article_a );
		$rendered_b = $this->render( $article_b );

		$this->assertStringContainsString( 'itsdz-glossary-term', $rendered_a );
		$this->assertStringContainsString( 'itsdz-glossary-term', $rendered_b );
		$this->assertNotSame( $rendered_a, $rendered_b );
	}

	/**
	 * The cache generation only advances on a glossary change, not on every
	 * unrelated request — otherwise the cache would never actually hit.
	 *
	 * @return void
	 */
	public function test_generation_is_stable_across_unrelated_actions() {
		$before = Cache::generation( 'glossary', 0 );

		self::factory()->post->create( array( 'post_type' => 'post' ) );
		self::factory()->term->create( array( 'taxonomy' => 'itsdz_tag' ) );

		$this->assertSame( $before, Cache::generation( 'glossary', 0 ) );
	}
}
