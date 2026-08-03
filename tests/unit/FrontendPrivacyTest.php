<?php
/**
 * Frontend publication-boundary tests.
 *
 * @package ItsDZ\Doczur\Tests\Unit
 */

namespace ItsDZ\Doczur\Tests\Unit;

use ItsDZ\Doczur\Frontend\Documentation;
use PHPUnit\Framework\TestCase;

/**
 * Proves frontend helpers never resolve an unpublished project.
 */
final class FrontendPrivacyTest extends TestCase {
	/**
	 * Only a published documentation project is publicly resolvable.
	 *
	 * @return void
	 */
	public function test_only_published_kb_is_resolved() {
		$GLOBALS['itsdz_test_posts'] = array(
			10 => $this->kb( 10, 'publish' ),
			11 => $this->kb( 11, 'draft' ),
		);

		$this->assertSame( 10, Documentation::get_kb( 10 )->ID );
		$this->assertNull( Documentation::get_kb( 11 ) );
		$this->assertNull( Documentation::get_kb( 999 ) );
	}

	/**
	 * An article inherits the publication boundary of its project.
	 *
	 * @return void
	 */
	public function test_article_requires_a_published_kb() {
		$GLOBALS['itsdz_test_posts']            = array(
			10 => $this->kb( 10, 'publish' ),
			11 => $this->kb( 11, 'draft' ),
		);
		$GLOBALS['itsdz_test_post_meta_values'] = array(
			20 => array( '_itsdz_kb_id' => 10 ),
			21 => array( '_itsdz_kb_id' => 11 ),
		);

		$this->assertSame( 10, Documentation::get_article_kb( 20 )->ID );
		$this->assertNull( Documentation::get_article_kb( 21 ) );
	}

	/**
	 * Build a project-shaped test object.
	 *
	 * @param int    $post_id Project ID.
	 * @param string $status  Publication status.
	 * @return object
	 */
	private function kb( $post_id, $status ) {
		return (object) array(
			'ID'          => $post_id,
			'post_status' => $status,
			'post_type'   => 'itsdz_kb',
		);
	}
}
