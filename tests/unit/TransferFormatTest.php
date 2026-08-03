<?php
/**
 * Import/export format tests.
 *
 * @package ItsDZ\Doczur\Tests\Unit
 */

namespace ItsDZ\Doczur\Tests\Unit;

use ItsDZ\Doczur\REST\Transfer_Controller;
use PHPUnit\Framework\TestCase;

/**
 * Proves section hierarchy remains portable and depth-limited.
 */
final class TransferFormatTest extends TestCase {
	/**
	 * Exported sections retain their complete root-to-leaf path.
	 *
	 * @return void
	 */
	public function test_export_builds_complete_section_path() {
		$GLOBALS['itsdz_test_terms'] = array(
			1 => new \WP_Term( 1, 'Guides' ),
			2 => new \WP_Term( 2, 'Setup', 1 ),
			3 => new \WP_Term( 3, 'Windows', 2 ),
		);
		$method                      = new \ReflectionMethod( Transfer_Controller::class, 'get_section_path' );
		$method->setAccessible( true );

		$this->assertSame(
			array( 'Guides', 'Setup', 'Windows' ),
			$method->invoke( new Transfer_Controller(), $GLOBALS['itsdz_test_terms'][3] )
		);
	}

	/**
	 * Legacy flat section names remain importable.
	 *
	 * @return void
	 */
	public function test_legacy_flat_sections_are_normalized_to_paths() {
		$this->assertSame(
			array( array( 'Guides' ), array( 'API' ) ),
			$this->normalize( array( ' Guides ', 'API', 'Guides' ) )
		);
	}

	/**
	 * Version-2 nested paths preserve all three supported levels.
	 *
	 * @return void
	 */
	public function test_three_level_section_paths_are_preserved() {
		$paths = array(
			array( 'Guides', 'Setup', 'Windows' ),
			array( 'Reference', 'REST API' ),
		);

		$this->assertSame( $paths, $this->normalize( $paths ) );
	}

	/**
	 * Import validation rejects paths deeper than the product limit.
	 *
	 * @return void
	 */
	public function test_section_paths_deeper_than_three_levels_are_rejected() {
		$this->assertNull( $this->normalize( array( array( 'One', 'Two', 'Three', 'Four' ) ) ) );
	}

	/**
	 * Call the controller's normalization boundary.
	 *
	 * @param mixed $sections Raw sections.
	 * @return array<int, string[]>|null
	 */
	private function normalize( $sections ) {
		$method = new \ReflectionMethod( Transfer_Controller::class, 'normalize_section_paths' );
		$method->setAccessible( true );

		return $method->invoke( new Transfer_Controller(), $sections );
	}
}
