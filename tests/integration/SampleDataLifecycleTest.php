<?php
/**
 * Sample content lifecycle tests.
 *
 * The generator deletes posts, so the property that matters most is that it
 * only ever deletes its own content — never anything the user wrote.
 *
 * @package ItsDZ\Doczur\Tests\Integration
 */

namespace ItsDZ\Doczur\Tests\Integration;

use ItsDZ\Doczur\PostTypes\Article_Post_Type;
use ItsDZ\Doczur\PostTypes\KB_Post_Type;
use ItsDZ\Doczur\Utils\Sample_Data;
use WP_UnitTestCase;

/**
 * Proves sample content can be created and removed safely.
 */
final class SampleDataLifecycleTest extends WP_UnitTestCase {

	/**
	 * Published knowledge base used by the fixtures.
	 *
	 * @var int
	 */
	private $kb_id = 0;

	/**
	 * Create a published project to attach sample content to.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();

		$this->kb_id = self::factory()->post->create(
			array(
				'post_status' => 'publish',
				'post_title'  => 'Handbook',
				'post_type'   => KB_Post_Type::POST_TYPE,
			)
		);
	}

	/**
	 * Count the articles attached to the fixture project.
	 *
	 * @return int
	 */
	private function count_articles() {
		return count(
			get_posts(
				array(
					'fields'         => 'ids',
					'meta_key'       => '_itsdz_kb_id', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
					'meta_value'     => $this->kb_id, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
					'post_status'    => 'any',
					'post_type'      => Article_Post_Type::POST_TYPE,
					'posts_per_page' => -1,
				)
			)
		);
	}

	/**
	 * Generation publishes the full demo set.
	 *
	 * @return void
	 */
	public function test_create_publishes_the_expected_content() {
		$result = Sample_Data::create( $this->kb_id );

		$this->assertSame( count( Sample_Data::articles() ), $result['articles'] );
		$this->assertSame( count( Sample_Data::sections() ), $result['sections'] );
		$this->assertTrue( Sample_Data::exists( $this->kb_id ) );
		$this->assertSame( $result['articles'], $this->count_articles() );
	}

	/**
	 * Regenerating refreshes the demo instead of duplicating it.
	 *
	 * @return void
	 */
	public function test_regenerating_does_not_duplicate_content() {
		Sample_Data::create( $this->kb_id );
		$after_first = $this->count_articles();

		Sample_Data::create( $this->kb_id );

		$this->assertSame( $after_first, $this->count_articles(), 'Regenerating stacked duplicate articles.' );
	}

	/**
	 * Removal clears every generated article.
	 *
	 * @return void
	 */
	public function test_remove_deletes_all_generated_articles() {
		Sample_Data::create( $this->kb_id );

		$removed = Sample_Data::remove( $this->kb_id );

		$this->assertSame( count( Sample_Data::articles() ), $removed['articles'] );
		$this->assertFalse( Sample_Data::exists( $this->kb_id ) );
		$this->assertSame( 0, $this->count_articles() );
	}

	/**
	 * Removal never touches articles the user wrote.
	 *
	 * @return void
	 */
	public function test_remove_preserves_user_authored_articles() {
		$mine = self::factory()->post->create(
			array(
				'post_content' => 'Written by hand.',
				'post_status'  => 'publish',
				'post_title'   => 'My own article',
				'post_type'    => Article_Post_Type::POST_TYPE,
			)
		);
		update_post_meta( $mine, '_itsdz_kb_id', $this->kb_id );

		Sample_Data::create( $this->kb_id );
		Sample_Data::remove( $this->kb_id );

		$this->assertSame( 'publish', get_post_status( $mine ), 'A user-authored article was deleted.' );
		$this->assertSame( 1, $this->count_articles() );
	}

	/**
	 * A section the user has filled with real articles survives removal.
	 *
	 * @return void
	 */
	public function test_remove_keeps_sections_that_still_hold_articles() {
		Sample_Data::create( $this->kb_id );

		$section_names = array_values( Sample_Data::sections() );
		$section       = get_term_by( 'name', $section_names[0], 'itsdz_section' );
		$this->assertInstanceOf( \WP_Term::class, $section );

		// The user files their own article under a generated section.
		$mine = self::factory()->post->create(
			array(
				'post_status' => 'publish',
				'post_title'  => 'My own article',
				'post_type'   => Article_Post_Type::POST_TYPE,
			)
		);
		update_post_meta( $mine, '_itsdz_kb_id', $this->kb_id );
		wp_set_object_terms( $mine, array( $section->term_id ), 'itsdz_section' );

		Sample_Data::remove( $this->kb_id );

		$this->assertInstanceOf(
			\WP_Term::class,
			get_term( $section->term_id, 'itsdz_section' ),
			'A section still holding a user article was deleted.'
		);
	}

	/**
	 * Sample articles are published, so the demo is visible immediately.
	 *
	 * @return void
	 */
	public function test_generated_articles_are_published_and_sectioned() {
		Sample_Data::create( $this->kb_id );

		$article_ids = get_posts(
			array(
				'fields'         => 'ids',
				'meta_key'       => '_itsdz_kb_id', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value'     => $this->kb_id, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
				'post_status'    => 'any',
				'post_type'      => Article_Post_Type::POST_TYPE,
				'posts_per_page' => -1,
			)
		);

		$this->assertNotEmpty( $article_ids );

		foreach ( $article_ids as $article_id ) {
			$this->assertSame( 'publish', get_post_status( $article_id ) );
			$this->assertNotEmpty(
				wp_get_object_terms( $article_id, 'itsdz_section', array( 'fields' => 'ids' ) ),
				"Article {$article_id} was left unsectioned."
			);
		}
	}
}
