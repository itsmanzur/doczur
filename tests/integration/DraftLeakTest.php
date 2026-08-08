<?php
/**
 * Draft and private content exposure tests.
 *
 * The roadmap calls this the single most important automated test in the
 * project: unpublished documentation must never reach an anonymous visitor,
 * through search or through any public REST response.
 *
 * @package ItsDZ\Doczur\Tests\Integration
 */

namespace ItsDZ\Doczur\Tests\Integration;

use ItsDZ\Doczur\PostTypes\Article_Post_Type;
use ItsDZ\Doczur\PostTypes\KB_Post_Type;
use ItsDZ\Doczur\Search\Search_Service;
use WP_REST_Request;
use WP_UnitTestCase;

/**
 * Proves unpublished articles stay invisible to the public.
 */
final class DraftLeakTest extends WP_UnitTestCase {

	/**
	 * Published knowledge base holding the fixtures.
	 *
	 * @var int
	 */
	private $kb_id = 0;

	/**
	 * Prepare a published project with one article per status.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();

		wp_set_current_user( 0 );

		$this->kb_id = self::factory()->post->create(
			array(
				'post_status' => 'publish',
				'post_title'  => 'Handbook',
				'post_type'   => KB_Post_Type::POST_TYPE,
			)
		);

		foreach ( array( 'publish', 'draft', 'private', 'pending' ) as $status ) {
			$article_id = self::factory()->post->create(
				array(
					'post_content' => 'Configuring the zephyr widget correctly.',
					'post_status'  => $status,
					'post_title'   => "Zephyr {$status} article",
					'post_type'    => Article_Post_Type::POST_TYPE,
				)
			);

			// Assigning the project triggers the indexer through meta hooks.
			update_post_meta( $article_id, '_itsdz_kb_id', $this->kb_id );
		}

		do_action( 'rest_api_init' );
	}

	/**
	 * Only the published article is present in the search index.
	 *
	 * @return void
	 */
	public function test_search_index_contains_published_articles_only() {
		global $wpdb;

		$table = $wpdb->prefix . 'itsdz_search_index';

		$indexed = $wpdb->get_col( $wpdb->prepare( 'SELECT article_id FROM %i', $table ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		foreach ( $indexed as $article_id ) {
			$this->assertSame(
				'publish',
				get_post_status( (int) $article_id ),
				'An unpublished article reached the search index.'
			);
		}
	}

	/**
	 * A stale index cannot leak unpublished articles.
	 *
	 * This drives the last line of defence directly. Rather than relying on
	 * the FULLTEXT query — whose index is not visible inside the transaction
	 * each test runs in — it hands the hydrator index rows that point at
	 * unpublished articles, exactly what a stale or tampered index looks like,
	 * and asserts none of them survive.
	 *
	 * @return void
	 */
	public function test_stale_index_rows_for_unpublished_articles_are_dropped() {
		$service = new Search_Service();

		$rows      = array();
		$published = 0;

		foreach ( get_posts(
			array(
				'fields'         => 'ids',
				'post_status'    => array( 'publish', 'draft', 'private', 'pending' ),
				'post_type'      => Article_Post_Type::POST_TYPE,
				'posts_per_page' => -1,
			)
		) as $article_id ) {
			$rows[] = (object) array(
				'article_id' => $article_id,
				'relevance'  => 1.0,
			);

			if ( 'publish' === get_post_status( $article_id ) ) {
				++$published;
			}
		}

		// Guard against a vacuous pass: the fixture must contain both kinds.
		$this->assertGreaterThan( 0, $published, 'No published fixture article was created.' );
		$this->assertGreaterThan( $published, count( $rows ), 'No unpublished fixture article was created.' );

		$results = $service->hydrate_public_results( $rows, $this->kb_id );

		$this->assertCount( $published, $results, 'The hydrator returned an unexpected number of articles.' );

		foreach ( $results as $result ) {
			$this->assertSame(
				'publish',
				get_post_status( (int) $result['id'] ),
				'A stale index row leaked an unpublished article to the public.'
			);
		}
	}

	/**
	 * Results from another project never bleed into this one.
	 *
	 * @return void
	 */
	public function test_hydrator_rejects_articles_from_another_project() {
		$other_kb_id = self::factory()->post->create(
			array(
				'post_status' => 'publish',
				'post_title'  => 'Other handbook',
				'post_type'   => KB_Post_Type::POST_TYPE,
			)
		);

		$foreign_article = self::factory()->post->create(
			array(
				'post_content' => 'Belongs somewhere else entirely.',
				'post_status'  => 'publish',
				'post_title'   => 'Foreign article',
				'post_type'    => Article_Post_Type::POST_TYPE,
			)
		);
		update_post_meta( $foreign_article, '_itsdz_kb_id', $other_kb_id );

		$row = (object) array(
			'article_id' => $foreign_article,
			'relevance'  => 1.0,
		);

		$results = ( new Search_Service() )->hydrate_public_results( array( $row ), $this->kb_id );

		$this->assertSame( array(), $results, 'An article from another project was returned.' );
	}

	/**
	 * The public search endpoint responds without exposing anything extra.
	 *
	 * @return void
	 */
	public function test_public_search_endpoint_responds_successfully() {
		$request = new WP_REST_Request( 'GET', '/itsdz/v1/search' );
		$request->set_param( 'kb_id', $this->kb_id );
		$request->set_param( 'q', 'zephyr' );

		$response = rest_get_server()->dispatch( $request );

		$this->assertSame( 200, $response->get_status() );

		foreach ( $response->get_data()['results'] as $result ) {
			$this->assertSame(
				'publish',
				get_post_status( (int) $result['id'] ),
				'The public search endpoint leaked an unpublished article.'
			);
		}
	}

	/**
	 * Unpublishing an article removes it from the index straight away.
	 *
	 * @return void
	 */
	public function test_unpublishing_removes_the_article_from_the_index() {
		global $wpdb;

		$table      = $wpdb->prefix . 'itsdz_search_index';
		$article_id = self::factory()->post->create(
			array(
				'post_content' => 'Temporarily published content about zephyrs.',
				'post_status'  => 'publish',
				'post_title'   => 'Soon to be a draft',
				'post_type'    => Article_Post_Type::POST_TYPE,
			)
		);
		update_post_meta( $article_id, '_itsdz_kb_id', $this->kb_id );

		$before = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM %i WHERE article_id = %d', $table, $article_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$this->assertSame( 1, $before, 'The published article was never indexed.' );

		wp_update_post(
			array(
				'ID'          => $article_id,
				'post_status' => 'draft',
			)
		);

		$after = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM %i WHERE article_id = %d', $table, $article_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$this->assertSame( 0, $after, 'Reverting an article to draft left it in the search index.' );
	}

	/**
	 * Anonymous visitors cannot list articles through the admin endpoint.
	 *
	 * @return void
	 */
	public function test_admin_article_endpoint_rejects_anonymous_requests() {
		$request = new WP_REST_Request( 'GET', '/itsdz/v1/articles' );
		$request->set_param( 'kb_id', $this->kb_id );

		$response = rest_get_server()->dispatch( $request );

		$this->assertContains(
			$response->get_status(),
			array( 401, 403 ),
			'The admin article endpoint answered an anonymous request.'
		);
	}

	/**
	 * Articles belonging to an unpublished project are not searchable.
	 *
	 * @return void
	 */
	public function test_articles_of_an_unpublished_project_are_not_indexed() {
		global $wpdb;

		$table       = $wpdb->prefix . 'itsdz_search_index';
		$draft_kb_id = self::factory()->post->create(
			array(
				'post_status' => 'draft',
				'post_title'  => 'Unreleased handbook',
				'post_type'   => KB_Post_Type::POST_TYPE,
			)
		);

		$article_id = self::factory()->post->create(
			array(
				'post_content' => 'Secret zephyr documentation.',
				'post_status'  => 'publish',
				'post_title'   => 'Hidden article',
				'post_type'    => Article_Post_Type::POST_TYPE,
			)
		);
		update_post_meta( $article_id, '_itsdz_kb_id', $draft_kb_id );

		$rows = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM %i WHERE article_id = %d', $table, $article_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		$this->assertSame( 0, $rows, 'An article inside an unpublished project was indexed.' );
	}
}
