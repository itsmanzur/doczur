<?php
/**
 * WordPress taxonomy term test stub.
 *
 * @package ItsDZ\Doczur\Tests
 */

if ( ! class_exists( 'WP_Term' ) ) {
	/**
	 * Minimal taxonomy term for isolated hierarchy tests.
	 */
	class WP_Term {
		/**
		 * Term ID.
		 *
		 * @var int
		 */
		public $term_id;

		/**
		 * Term name.
		 *
		 * @var string
		 */
		public $name;

		/**
		 * Parent term ID.
		 *
		 * @var int
		 */
		public $parent;

		/**
		 * Build a test term.
		 *
		 * @param int    $term_id   Term ID.
		 * @param string $name      Term name.
		 * @param int    $parent_id Parent ID.
		 */
		public function __construct( $term_id, $name, $parent_id = 0 ) {
			$this->term_id = $term_id;
			$this->name    = $name;
			$this->parent  = $parent_id;
		}
	}
}
