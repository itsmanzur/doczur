<?php
/**
 * Capability map tests.
 *
 * @package ItsDZ\Doczur\Tests\Unit
 */

namespace ItsDZ\Doczur\Tests\Unit;

use ItsDZ\Doczur\Security\Capabilities;
use PHPUnit\Framework\TestCase;

/**
 * Verifies the Phase 1 authorization contract.
 */
final class CapabilitiesTest extends TestCase {
	/**
	 * Every content mutation maps to the Doczur capability.
	 *
	 * @return void
	 */
	public function test_post_type_map_uses_doczur_capability() {
		$capabilities = Capabilities::post_type_map();

		$this->assertArrayHasKey( 'create_posts', $capabilities );
		$this->assertArrayHasKey( 'read_private_posts', $capabilities );
		$this->assertSame(
			array( Capabilities::MANAGE_DOCS ),
			array_values( array_unique( $capabilities ) )
		);
	}

	/**
	 * Every taxonomy mutation maps to the Doczur capability.
	 *
	 * @return void
	 */
	public function test_taxonomy_map_uses_doczur_capability() {
		$this->assertSame(
			array( Capabilities::MANAGE_DOCS ),
			array_values( array_unique( Capabilities::taxonomy_map() ) )
		);
	}
}
