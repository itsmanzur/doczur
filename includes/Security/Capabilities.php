<?php
/**
 * Doczur capability management.
 *
 * @package ItsDZ\Doczur\Security
 */

namespace ItsDZ\Doczur\Security;

use ItsDZ\Doczur\Core\Service;

defined( 'ABSPATH' ) || exit;

/**
 * Defines and installs Doczur capabilities.
 */
final class Capabilities implements Service {
	/**
	 * Primary capability used by the Free plugin.
	 */
	const MANAGE_DOCS = 'itsdz_manage_docs';

	/**
	 * Installed capability schema version option.
	 */
	const VERSION_OPTION = 'itsdz_capabilities_version';

	/**
	 * Current capability schema version.
	 */
	const VERSION = '1.0.0';

	/**
	 * Register WordPress hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'admin_init', array( $this, 'maybe_install' ) );
	}

	/**
	 * Repair capabilities after plugin updates that do not run activation hooks.
	 *
	 * @return void
	 */
	public function maybe_install() {
		if ( self::VERSION !== get_option( self::VERSION_OPTION, '' ) ) {
			self::install();
		}
	}

	/**
	 * Grant Doczur management to roles that can manage site options.
	 *
	 * @return void
	 */
	public static function install() {
		$roles = wp_roles();

		foreach ( $roles->role_objects as $role ) {
			if ( $role->has_cap( 'manage_options' ) ) {
				$role->add_cap( self::MANAGE_DOCS );
			}
		}

		update_option( self::VERSION_OPTION, self::VERSION, false );
	}

	/**
	 * Return the explicit post type capability map.
	 *
	 * A single primitive capability keeps Phase 1 predictable. Granular workflow
	 * capabilities can be introduced later without changing stored content.
	 *
	 * @return array<string, string>
	 */
	public static function post_type_map() {
		return array(
			'edit_post'              => self::MANAGE_DOCS,
			'read_post'              => self::MANAGE_DOCS,
			'delete_post'            => self::MANAGE_DOCS,
			'edit_posts'             => self::MANAGE_DOCS,
			'edit_others_posts'      => self::MANAGE_DOCS,
			'publish_posts'          => self::MANAGE_DOCS,
			'read_private_posts'     => self::MANAGE_DOCS,
			'delete_posts'           => self::MANAGE_DOCS,
			'delete_private_posts'   => self::MANAGE_DOCS,
			'delete_published_posts' => self::MANAGE_DOCS,
			'delete_others_posts'    => self::MANAGE_DOCS,
			'edit_private_posts'     => self::MANAGE_DOCS,
			'edit_published_posts'   => self::MANAGE_DOCS,
			'create_posts'           => self::MANAGE_DOCS,
		);
	}

	/**
	 * Return the taxonomy capability map.
	 *
	 * @return array<string, string>
	 */
	public static function taxonomy_map() {
		return array(
			'manage_terms' => self::MANAGE_DOCS,
			'edit_terms'   => self::MANAGE_DOCS,
			'delete_terms' => self::MANAGE_DOCS,
			'assign_terms' => self::MANAGE_DOCS,
		);
	}
}
