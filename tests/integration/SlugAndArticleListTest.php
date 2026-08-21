<?php
/**
 * Slug-base rewrite and article list pagination tests.
 *
 * @package ItsDZ\Doczur\Tests\Integration
 */

namespace ItsDZ\Doczur\Tests\Integration;

use ItsDZ\Doczur\Frontend\Rewrite_Manager;
use ItsDZ\Doczur\PostTypes\Article_Post_Type;
use ItsDZ\Doczur\PostTypes\KB_Post_Type;
use ItsDZ\Doczur\Security\Capabilities;
use WP_REST_Request;
use WP_UnitTestCase;

/**
 * Proves the three high-severity fixes against real WordPress.
 */
final class SlugAndArticleListTest extends WP_UnitTestCase {

	/**
	 * Published project used by the tests.
	 *
	 * @var int
	 */
	private $kb_id = 0;

	/**
	 * Log in as an admin and create a project.
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
	 * Saving the URL slug base persists it and flags a rewrite flush.
	 *
	 * @return void
	 */
	public function test_updating_slug_base_persists_the_rewrite_prefix() {
		delete_option( KB_Post_Type::SLUG_OPTION );
		delete_option( Rewrite_Manager::FLUSH_OPTION );

		$request = new WP_REST_Request( 'PUT', '/itsdz/v1/kb/' . $this->kb_id );
		$request->set_param(
			'meta',
			array(
				'_itsdz_kb_slug_base' => 'help-center',
			)
		);

		$response = rest_get_server()->dispatch( $request );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( 'help-center', get_option( KB_Post_Type::SLUG_OPTION ) );
		$this->assertSame( 'help-center', get_post_meta( $this->kb_id, '_itsdz_kb_slug_base', true ) );
		$this->assertSame( '1', get_option( Rewrite_Manager::FLUSH_OPTION ) );
	}

	/**
	 * Article list pages instead of silently dropping rows after 100.
	 *
	 * @return void
	 */
	public function test_article_list_reports_pagination_headers() {
		foreach ( array( 'One', 'Two', 'Three' ) as $title ) {
			$article_id = self::factory()->post->create(
				array(
					'post_status' => 'publish',
					'post_title'  => $title,
					'post_type'   => Article_Post_Type::POST_TYPE,
				)
			);
			update_post_meta( $article_id, '_itsdz_kb_id', $this->kb_id );
		}

		$request = new WP_REST_Request( 'GET', '/itsdz/v1/articles' );
		$request->set_param( 'kb_id', $this->kb_id );
		$request->set_param( 'page', 1 );
		$request->set_param( 'per_page', 2 );

		$response = rest_get_server()->dispatch( $request );
		$headers  = $response->get_headers();

		$this->assertSame( 200, $response->get_status() );
		$this->assertCount( 2, $response->get_data() );
		$this->assertSame( '3', (string) $headers['X-WP-Total'] );
		$this->assertSame( '2', (string) $headers['X-WP-TotalPages'] );

		$page_two = new WP_REST_Request( 'GET', '/itsdz/v1/articles' );
		$page_two->set_param( 'kb_id', $this->kb_id );
		$page_two->set_param( 'page', 2 );
		$page_two->set_param( 'per_page', 2 );

		$second = rest_get_server()->dispatch( $page_two );

		$this->assertCount( 1, $second->get_data() );
	}
}
