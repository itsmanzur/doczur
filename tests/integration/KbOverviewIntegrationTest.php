<?php
/**
 * KB overview REST endpoint tests.
 *
 * KbOverviewTest (unit) covers the attention-reason classification logic in
 * isolation. This file drives the real `GET /kb/{id}/overview` route against
 * WordPress, proving permissions, KB validation and cache invalidation.
 *
 * @package ItsDZ\Doczur\Tests\Integration
 */

namespace ItsDZ\Doczur\Tests\Integration;

use ItsDZ\Doczur\PostTypes\Article_Post_Type;
use ItsDZ\Doczur\PostTypes\KB_Post_Type;
use ItsDZ\Doczur\Security\Capabilities;
use WP_REST_Request;
use WP_UnitTestCase;

/**
 * Proves the KB overview endpoint behaves correctly end to end.
 */
final class KbOverviewIntegrationTest extends WP_UnitTestCase {

	/**
	 * Published project used by most tests.
	 *
	 * @var int
	 */
	private $kb_id = 0;

	/**
	 * Create an admin user and a project to point requests at.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();

		Capabilities::install();

		$admin_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );

		$this->kb_id = self::factory()->post->create(
			array(
				'post_status' => 'publish',
				'post_title'  => 'Handbook',
				'post_type'   => KB_Post_Type::POST_TYPE,
			)
		);

		do_action( 'rest_api_init' );
	}

	/**
	 * Dispatch a GET to the overview route for a given KB id.
	 *
	 * @param int $kb_id Project ID.
	 * @return \WP_REST_Response
	 */
	private function get_overview( $kb_id ) {
		return rest_get_server()->dispatch( new WP_REST_Request( 'GET', "/itsdz/v1/kb/{$kb_id}/overview" ) );
	}

	/**
	 * Create an article attached to the fixture project.
	 *
	 * @param array<string, mixed> $args Post factory overrides.
	 * @return int
	 */
	private function make_article( $args = array() ) {
		$article_id = self::factory()->post->create(
			array_merge(
				array(
					'post_status' => 'publish',
					'post_type'   => Article_Post_Type::POST_TYPE,
				),
				$args
			)
		);
		update_post_meta( $article_id, '_itsdz_kb_id', $this->kb_id );

		return $article_id;
	}

	/**
	 * An anonymous request is rejected.
	 *
	 * @return void
	 */
	public function test_anonymous_request_is_rejected() {
		wp_set_current_user( 0 );

		$response = $this->get_overview( $this->kb_id );

		$this->assertContains( $response->get_status(), array( 401, 403 ) );
	}

	/**
	 * A logged-in user without the Doczur capability is rejected.
	 *
	 * @return void
	 */
	public function test_user_without_capability_is_rejected() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'subscriber' ) ) );

		$response = $this->get_overview( $this->kb_id );

		$this->assertSame( 403, $response->get_status() );
	}

	/**
	 * A non-existent KB id is rejected, matching Content_Validator::get_kb().
	 *
	 * @return void
	 */
	public function test_nonexistent_kb_is_rejected() {
		$response = $this->get_overview( 999999 );

		$this->assertSame( 400, $response->get_status() );
		$this->assertSame( 'itsdz_invalid_kb', $response->as_error()->get_error_code() );
	}

	/**
	 * A post ID that exists but is not a KB is rejected the same way.
	 *
	 * @return void
	 */
	public function test_wrong_post_type_id_is_rejected() {
		$article_id = $this->make_article();

		$response = $this->get_overview( $article_id );

		$this->assertSame( 400, $response->get_status() );
	}

	/**
	 * A KB with no articles reports all-zero counts and empty (not null) lists.
	 *
	 * @return void
	 */
	public function test_empty_kb_reports_zero_counts_and_empty_lists() {
		$response = $this->get_overview( $this->kb_id );
		$data     = $response->get_data();

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame(
			array(
				'total'        => 0,
				'published'    => 0,
				'draft'        => 0,
				'needs_review' => 0,
			),
			$data['counts']
		);
		$this->assertSame( array(), $data['attention'] );
		$this->assertSame( array(), $data['recent'] );
		$this->assertSame( array(), $data['extra'] );
	}

	/**
	 * A plugin can add its own data via the itsdz_kb_overview_extra filter.
	 *
	 * @return void
	 */
	public function test_extra_field_is_filterable() {
		add_filter(
			'itsdz_kb_overview_extra',
			static function ( $extra, $kb_id ) {
				$extra['from_filter'] = $kb_id;
				return $extra;
			},
			10,
			2
		);

		$data = $this->get_overview( $this->kb_id )->get_data();

		$this->assertSame( array( 'from_filter' => $this->kb_id ), $data['extra'] );
	}

	/**
	 * Draft, never-reviewed and stale articles are all counted and listed.
	 *
	 * @return void
	 */
	public function test_counts_and_attention_reflect_real_articles() {
		$this->make_article( array( 'post_status' => 'draft' ) );
		$reviewed = $this->make_article();
		update_post_meta( $reviewed, '_itsdz_last_reviewed', gmdate( 'Y-m-d' ) );
		$this->make_article(); // Published, never reviewed.

		$response = $this->get_overview( $this->kb_id );
		$data     = $response->get_data();

		$this->assertSame( 3, $data['counts']['total'] );
		$this->assertSame( 2, $data['counts']['published'] );
		$this->assertSame( 1, $data['counts']['draft'] );
		$this->assertSame( 2, $data['counts']['needs_review'] ); // Draft + never-reviewed.
		$this->assertCount( 2, $data['attention'] );

		$reasons = wp_list_pluck( $data['attention'], 'reason' );
		sort( $reasons );
		$this->assertSame( array( 'draft', 'never_reviewed' ), $reasons );
	}

	/**
	 * The stale-review threshold can be overridden by a filter.
	 *
	 * @return void
	 */
	public function test_stale_review_threshold_is_filterable() {
		$article = $this->make_article();
		update_post_meta( $article, '_itsdz_last_reviewed', gmdate( 'Y-m-d', strtotime( '-10 days' ) ) );

		// Ten days is not stale at the 90-day default.
		$default_run = $this->get_overview( $this->kb_id )->get_data();
		$this->assertSame( 0, $default_run['counts']['needs_review'] );

		// A 5-day threshold makes the same article stale.
		add_filter(
			'itsdz_stale_review_days',
			static function () {
				return 5;
			}
		);

		$filtered_run = $this->get_overview( $this->kb_id )->get_data();
		$this->assertSame( 1, $filtered_run['counts']['needs_review'] );
		$this->assertSame( 'stale_review', $filtered_run['attention'][0]['reason'] );
	}

	/**
	 * The recent list is capped at five, newest edit first.
	 *
	 * @return void
	 */
	public function test_recent_list_is_capped_at_five_newest_first() {
		global $wpdb;

		// Explicit, minute-spaced timestamps rather than real-clock sleeps —
		// post_modified only has second-level resolution, so creating posts
		// back to back in a tight loop cannot be trusted to land in order.
		// A direct database write is required: wp_update_post() always
		// resets post_modified to the current time, ignoring any value
		// passed to it.
		for ( $i = 0; $i < 7; $i++ ) {
			$article_id = $this->make_article( array( 'post_title' => "Article {$i}" ) );
			$modified   = gmdate( 'Y-m-d H:i:s', strtotime( "2026-01-01 00:00:00 +{$i} minutes" ) );

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- wp_update_post() cannot set post_modified; it always overwrites it with the current time.
			$wpdb->update(
				$wpdb->posts,
				array(
					'post_modified'     => $modified,
					'post_modified_gmt' => $modified,
				),
				array( 'ID' => $article_id )
			);
		}

		$data = $this->get_overview( $this->kb_id )->get_data();

		$this->assertCount( 5, $data['recent'] );
		$this->assertSame( 'Article 6', $data['recent'][0]['title'] );
		$this->assertSame( 'Article 2', $data['recent'][4]['title'] );
	}

	/**
	 * Saving an article invalidates the cached overview for its project.
	 *
	 * @return void
	 */
	public function test_saving_an_article_invalidates_the_cache() {
		$before = $this->get_overview( $this->kb_id )->get_data();
		$this->assertSame( 0, $before['counts']['total'] );

		$this->make_article();

		$after = $this->get_overview( $this->kb_id )->get_data();
		$this->assertSame( 1, $after['counts']['total'], 'The overview was served from a stale cache after a new article was saved.' );
	}

	/**
	 * Marking an article reviewed invalidates the cache too.
	 *
	 * _itsdz_last_reviewed is written via a direct update_post_meta() call
	 * (Article_Controller::update_optional_meta()), which never fires
	 * save_post — so this depends on the added/updated_post_meta hook
	 * picking it up specifically, not just the save_post one.
	 *
	 * @return void
	 */
	public function test_marking_an_article_reviewed_invalidates_the_cache() {
		$article = $this->make_article();

		$before = $this->get_overview( $this->kb_id )->get_data();
		$this->assertSame( 1, $before['counts']['needs_review'] );

		update_post_meta( $article, '_itsdz_last_reviewed', gmdate( 'Y-m-d' ) );

		$after = $this->get_overview( $this->kb_id )->get_data();
		$this->assertSame( 0, $after['counts']['needs_review'], 'The overview was served from a stale cache after the article was marked reviewed.' );
	}
}
