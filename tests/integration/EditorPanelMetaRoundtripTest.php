<?php
/**
 * Metadata round-trip tests for the native-editor architecture.
 *
 * The sidebar panel never talks to Doczur's own REST API — it reads and
 * writes through @wordpress/core-data, which means saves go through
 * WordPress core's own `/wp/v2/itsdz_doc/{id}` endpoint (exactly what
 * Gutenberg itself uses to save any post). These tests drive that same core
 * endpoint directly and then read the result back through Doczur's own
 * `/itsdz/v1/articles/{id}`, proving the two REST surfaces agree about what
 * was saved.
 *
 * @package ItsDZ\Doczur\Tests\Integration
 */

namespace ItsDZ\Doczur\Tests\Integration;

use ItsDZ\Doczur\PostTypes\Article_Post_Type;
use ItsDZ\Doczur\PostTypes\KB_Post_Type;
use ItsDZ\Doczur\PostTypes\Meta_Fields;
use ItsDZ\Doczur\Security\Capabilities;
use WP_REST_Request;
use WP_UnitTestCase;

/**
 * Proves meta and taxonomy fields saved via the core REST API round-trip.
 */
final class EditorPanelMetaRoundtripTest extends WP_UnitTestCase {

	/**
	 * The article used by every test.
	 *
	 * @var int
	 */
	private $article_id = 0;

	/**
	 * Log in as an editor and create a bare draft article.
	 *
	 * Two pieces of global state this suite depends on do not survive from
	 * one test to the next and must be rebuilt every time:
	 *
	 * - The Doczur capability is normally installed on `admin_init`, which
	 *   WP_UnitTestCase never fires.
	 * - wp-phpunit's own base test case calls `unregister_all_meta_keys()`
	 *   after every single test (since `init` — where register_post_meta()
	 *   normally runs — never fires again either), so the very first test in
	 *   this class to touch REST meta leaves every later test, in any class,
	 *   with no meta registered at all unless it is re-registered here.
	 *
	 * Neither is a product bug: on a real request both `admin_init` and
	 * `init` fire on every page load, well before anyone can save anything.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();

		Capabilities::install();
		( new Meta_Fields() )->register_meta();

		$admin_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );

		$this->article_id = self::factory()->post->create(
			array(
				'post_status' => 'draft',
				'post_title'  => 'Round trip article',
				'post_type'   => Article_Post_Type::POST_TYPE,
			)
		);

		do_action( 'rest_api_init' );
	}

	/**
	 * Save meta through core's own REST endpoint for the post type.
	 *
	 * @param array<string, mixed> $body Request body.
	 * @return \WP_REST_Response
	 */
	private function save_via_core_rest( $body ) {
		$request = new WP_REST_Request( 'POST', '/wp/v2/itsdz_doc/' . $this->article_id );
		$request->set_body_params( $body );

		return rest_get_server()->dispatch( $request );
	}

	/**
	 * Read the article back through Doczur's own REST endpoint.
	 *
	 * @return array<string, mixed>
	 */
	private function read_via_doczur_rest() {
		$request  = new WP_REST_Request( 'GET', '/itsdz/v1/articles/' . $this->article_id );
		$response = rest_get_server()->dispatch( $request );

		return $response->get_data();
	}

	/**
	 * The knowledge base meta field round-trips.
	 *
	 * @return void
	 */
	public function test_kb_meta_round_trips() {
		$kb_id = self::factory()->post->create(
			array(
				'post_status' => 'publish',
				'post_type'   => KB_Post_Type::POST_TYPE,
			)
		);

		$response = $this->save_via_core_rest( array( 'meta' => array( '_itsdz_kb_id' => $kb_id ) ) );
		$this->assertSame( 200, $response->get_status() );

		$article = $this->read_via_doczur_rest();

		$this->assertSame( $kb_id, $article['kb_id'] );
	}

	/**
	 * The last-reviewed meta field round-trips.
	 *
	 * @return void
	 */
	public function test_last_reviewed_meta_round_trips() {
		$response = $this->save_via_core_rest(
			array( 'meta' => array( '_itsdz_last_reviewed' => '2026-01-15' ) )
		);
		$this->assertSame( 200, $response->get_status() );

		$article = $this->read_via_doczur_rest();

		$this->assertSame( '2026-01-15', $article['last_reviewed'] );
	}

	/**
	 * The section taxonomy round-trips.
	 *
	 * @return void
	 */
	public function test_section_taxonomy_round_trips() {
		$section_id = self::factory()->term->create( array( 'taxonomy' => 'itsdz_section' ) );

		$response = $this->save_via_core_rest( array( 'itsdz_section' => array( $section_id ) ) );
		$this->assertSame( 200, $response->get_status() );

		$article = $this->read_via_doczur_rest();

		$this->assertSame( array( $section_id ), $article['section_ids'] );
	}

	/**
	 * The version taxonomy round-trips.
	 *
	 * @return void
	 */
	public function test_version_taxonomy_round_trips() {
		$version_id = self::factory()->term->create( array( 'taxonomy' => 'itsdz_version' ) );

		$response = $this->save_via_core_rest( array( 'itsdz_version' => array( $version_id ) ) );
		$this->assertSame( 200, $response->get_status() );

		$article = $this->read_via_doczur_rest();

		$this->assertSame( $version_id, $article['version_id'] );
	}

	/**
	 * The tag taxonomy round-trips, including multiple tags.
	 *
	 * @return void
	 */
	public function test_tag_taxonomy_round_trips() {
		$tag_a = self::factory()->term->create( array( 'taxonomy' => 'itsdz_tag' ) );
		$tag_b = self::factory()->term->create( array( 'taxonomy' => 'itsdz_tag' ) );

		$response = $this->save_via_core_rest( array( 'itsdz_tag' => array( $tag_a, $tag_b ) ) );
		$this->assertSame( 200, $response->get_status() );

		$article = $this->read_via_doczur_rest();

		sort( $article['tag_ids'] );
		$expected = array( $tag_a, $tag_b );
		sort( $expected );

		$this->assertSame( $expected, $article['tag_ids'] );
	}

	/**
	 * All fields survive a single combined save, matching what the panel
	 * actually sends when several fields change at once.
	 *
	 * @return void
	 */
	public function test_combined_save_round_trips_every_field() {
		$kb_id      = self::factory()->post->create( array( 'post_type' => KB_Post_Type::POST_TYPE ) );
		$section_id = self::factory()->term->create( array( 'taxonomy' => 'itsdz_section' ) );
		$version_id = self::factory()->term->create( array( 'taxonomy' => 'itsdz_version' ) );
		$tag_id     = self::factory()->term->create( array( 'taxonomy' => 'itsdz_tag' ) );

		$response = $this->save_via_core_rest(
			array(
				'meta'          => array(
					'_itsdz_kb_id'         => $kb_id,
					'_itsdz_last_reviewed' => '2026-03-01',
				),
				'itsdz_section' => array( $section_id ),
				'itsdz_version' => array( $version_id ),
				'itsdz_tag'     => array( $tag_id ),
			)
		);
		$this->assertSame( 200, $response->get_status() );

		$article = $this->read_via_doczur_rest();

		$this->assertSame( $kb_id, $article['kb_id'] );
		$this->assertSame( '2026-03-01', $article['last_reviewed'] );
		$this->assertSame( array( $section_id ), $article['section_ids'] );
		$this->assertSame( $version_id, $article['version_id'] );
		$this->assertSame( array( $tag_id ), $article['tag_ids'] );
	}
}
