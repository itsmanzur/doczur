<?php
/**
 * Plugin container tests.
 *
 * @package ItsDZ\Doczur\Tests\Unit
 */

namespace ItsDZ\Doczur\Tests\Unit;

use ItsDZ\Doczur\Core\Plugin;
use PHPUnit\Framework\TestCase;

/**
 * Verifies the Phase 0 service container contract.
 */
final class PluginTest extends TestCase {
	/**
	 * The container returns one stable instance.
	 *
	 * @return void
	 */
	public function test_instance_is_shared() {
		$this->assertSame( Plugin::instance(), Plugin::instance() );
	}
}
