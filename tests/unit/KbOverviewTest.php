<?php
/**
 * KB overview attention-reason classification tests.
 *
 * @package ItsDZ\Doczur\Tests\Unit
 */

namespace ItsDZ\Doczur\Tests\Unit;

use ItsDZ\Doczur\REST\KB_Controller;
use PHPUnit\Framework\TestCase;

/**
 * Proves each article is classified into the right "needs attention" reason.
 *
 * KB_Controller::attention_reason() is private, exercised via reflection —
 * the same pattern TransferFormatTest.php uses for Transfer_Controller.
 */
final class KbOverviewTest extends TestCase {

	/**
	 * Call the private reason-classification method.
	 *
	 * @param object $article   Article-shaped test object.
	 * @param string $threshold ISO date threshold.
	 * @return string|null
	 */
	private function reason_for( $article, $threshold = '2026-01-01' ) {
		$method = new \ReflectionMethod( KB_Controller::class, 'attention_reason' );
		$method->setAccessible( true );

		return $method->invoke( new KB_Controller(), $article, $threshold );
	}

	/**
	 * A draft article always needs attention, regardless of review state.
	 *
	 * @return void
	 */
	public function test_draft_article_is_flagged_as_draft() {
		$GLOBALS['itsdz_test_post_meta_values'] = array(
			1 => array( '_itsdz_last_reviewed' => '2026-01-15' ),
		);

		$this->assertSame( 'draft', $this->reason_for( $this->article( 1, 'draft' ) ) );
	}

	/**
	 * A published article with no review date has never been reviewed.
	 *
	 * @return void
	 */
	public function test_published_unreviewed_article_is_flagged() {
		$GLOBALS['itsdz_test_post_meta_values'] = array(
			2 => array( '_itsdz_last_reviewed' => '' ),
		);

		$this->assertSame( 'never_reviewed', $this->reason_for( $this->article( 2, 'publish' ) ) );
	}

	/**
	 * A published article reviewed before the threshold date is stale.
	 *
	 * @return void
	 */
	public function test_published_article_reviewed_before_threshold_is_stale() {
		$GLOBALS['itsdz_test_post_meta_values'] = array(
			3 => array( '_itsdz_last_reviewed' => '2025-01-01' ),
		);

		$this->assertSame( 'stale_review', $this->reason_for( $this->article( 3, 'publish' ), '2026-01-01' ) );
	}

	/**
	 * A published article reviewed on or after the threshold needs nothing.
	 *
	 * @return void
	 */
	public function test_published_article_reviewed_recently_needs_no_attention() {
		$GLOBALS['itsdz_test_post_meta_values'] = array(
			4 => array( '_itsdz_last_reviewed' => '2026-06-01' ),
		);

		$this->assertNull( $this->reason_for( $this->article( 4, 'publish' ), '2026-01-01' ) );
	}

	/**
	 * A review dated exactly on the threshold is not yet stale.
	 *
	 * The threshold is built as "today minus N days" and compared with a
	 * strict less-than, so the boundary day itself must still count as
	 * current — this is the off-by-one every date-threshold check risks.
	 *
	 * @return void
	 */
	public function test_review_exactly_on_the_threshold_is_not_stale() {
		$GLOBALS['itsdz_test_post_meta_values'] = array(
			5 => array( '_itsdz_last_reviewed' => '2026-01-01' ),
		);

		$this->assertNull( $this->reason_for( $this->article( 5, 'publish' ), '2026-01-01' ) );
	}

	/**
	 * Counting draft + never-reviewed + stale-reviewed into one total, the
	 * same way build_overview() accumulates `needs_review`.
	 *
	 * @return void
	 */
	public function test_needs_review_sums_all_three_reasons() {
		$GLOBALS['itsdz_test_post_meta_values'] = array(
			10 => array( '_itsdz_last_reviewed' => '2026-06-01' ), // draft — reviewed doesn't matter.
			11 => array( '_itsdz_last_reviewed' => '' ),           // never reviewed.
			12 => array( '_itsdz_last_reviewed' => '2025-01-01' ), // stale.
			13 => array( '_itsdz_last_reviewed' => '2026-06-01' ), // fine, not counted.
		);

		$threshold = '2026-01-01';
		$articles  = array(
			$this->article( 10, 'draft' ),
			$this->article( 11, 'publish' ),
			$this->article( 12, 'publish' ),
			$this->article( 13, 'publish' ),
		);

		$needs_review = 0;

		foreach ( $articles as $article ) {
			if ( null !== $this->reason_for( $article, $threshold ) ) {
				++$needs_review;
			}
		}

		$this->assertSame( 3, $needs_review );
	}

	/**
	 * Build an article-shaped test object.
	 *
	 * @param int    $post_id Article ID.
	 * @param string $status  Post status.
	 * @return object
	 */
	private function article( $post_id, $status ) {
		return (object) array(
			'ID'          => $post_id,
			'post_status' => $status,
		);
	}
}
