<?php
/**
 * Service contract.
 *
 * @package ItsDZ\Doczur\Core
 */

namespace ItsDZ\Doczur\Core;

defined( 'ABSPATH' ) || exit;

/**
 * A bootable Nirdeshio service.
 */
interface Service {
	/**
	 * Register WordPress hooks for the service.
	 *
	 * @return void
	 */
	public function register();
}
