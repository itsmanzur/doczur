<?php
/**
 * Doczur admin menu and application mount point.
 *
 * @package ItsDZ\Doczur\Admin
 */

namespace ItsDZ\Doczur\Admin;

use ItsDZ\Doczur\Core\Service;
use ItsDZ\Doczur\Security\Capabilities;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the dedicated Doczur admin screen.
 */
final class Admin_Menu implements Service {
	/**
	 * Admin page slug.
	 */
	const PAGE_SLUG = 'doczur';

	/**
	 * Register WordPress hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'admin_menu', array( $this, 'add_menu' ) );
	}

	/**
	 * Add the top-level Doczur menu.
	 *
	 * @return void
	 */
	public function add_menu() {
		add_menu_page(
			__( 'Doczur Documentation', 'doczur' ),
			__( 'Doczur', 'doczur' ),
			Capabilities::MANAGE_DOCS,
			self::PAGE_SLUG,
			array( $this, 'render' ),
			'dashicons-media-document',
			58
		);
	}

	/**
	 * Render the React application mount point.
	 *
	 * @return void
	 */
	public function render() {
		if ( ! current_user_can( Capabilities::MANAGE_DOCS ) ) {
			wp_die( esc_html__( 'You are not allowed to access Doczur.', 'doczur' ) );
		}
		?>
		<div class="wrap itsdz-admin-wrap">
			<div id="itsdz-admin-app" aria-live="polite"></div>
			<noscript>
				<p><?php esc_html_e( 'Doczur requires JavaScript to run the documentation manager.', 'doczur' ); ?></p>
			</noscript>
		</div>
		<?php
	}
}
