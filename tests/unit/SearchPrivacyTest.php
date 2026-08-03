<?php
/**
 * Public search privacy tests.
 *
 * @package ItsDZ\Doczur\Tests\Unit
 */

namespace ItsDZ\Doczur\Tests\Unit;

use ItsDZ\Doczur\Search\Search_Service;
use PHPUnit\Framework\TestCase;

/**
 * Proves stale index rows cannot leak non-public articles.
 */
final class SearchPrivacyTest extends TestCase {
	/**
	 * Only a published article belonging to the requested KB is returned.
	 *
	 * @return void
	 */
	public function test_draft_private_and_wrong_kb_rows_are_excluded() {
		$GLOBALS['itsdz_test_posts']            = array(
			1 => $this->post( 1, 'publish' ),
			2 => $this->post( 2, 'draft' ),
			3 => $this->post( 3, 'private' ),
			4 => $this->post( 4, 'publish' ),
		);
		$GLOBALS['itsdz_test_post_meta_values'] = array(
			1 => array( '_itsdz_kb_id' => 50 ),
			2 => array( '_itsdz_kb_id' => 50 ),
			3 => array( '_itsdz_kb_id' => 50 ),
			4 => array( '_itsdz_kb_id' => 99 ),
		);
		$rows                                   = array(
			(object) array(
				'article_id' => 1,
				'relevance'  => 9.5,
			),
			(object) array(
				'article_id' => 2,
				'relevance'  => 9.0,
			),
			(object) array(
				'article_id' => 3,
				'relevance'  => 8.5,
			),
			(object) array(
				'article_id' => 4,
				'relevance'  => 8.0,
			),
			(object) array(
				'article_id' => 999,
				'relevance'  => 7.5,
			),
		);

		$results = ( new Search_Service() )->hydrate_public_results( $rows, 50 );

		$this->assertSame( array( 1 ), array_column( $results, 'id' ) );
	}

	/**
	 * Build an article-shaped test object.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $status  Post status.
	 * @return object
	 */
	private function post( $post_id, $status ) {
		return (object) array(
			'ID'           => $post_id,
			'post_content' => 'Documentation article content.',
			'post_status'  => $status,
			'post_title'   => 'Article ' . $post_id,
			'post_type'    => 'itsdz_doc',
		);
	}
}
