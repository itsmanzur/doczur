<?php
/**
 * Editor panel conditional-loading tests.
 *
 * @package ItsDZ\Doczur\Tests\Integration
 */

namespace ItsDZ\Doczur\Tests\Integration;

use ItsDZ\Doczur\Admin\Editor_Panel_Assets;
use ItsDZ\Doczur\PostTypes\Article_Post_Type;
use WP_UnitTestCase;

/**
 * Proves the Nirdeshio sidebar panel bundle loads only where it belongs.
 *
 * The bundle registers a PluginDocumentSettingPanel that assumes it is
 * editing an itsdz_doc article (see DoczurPanel.tsx's postType guard); if it
 * loaded on every post type's edit screen it would be dead weight on every
 * ordinary post and page a site has.
 */
final class EditorPanelAssetsTest extends WP_UnitTestCase {

	/**
	 * Reset enqueued/registered script state between tests.
	 *
	 * @return void
	 */
	public function tear_down() {
		wp_deregister_script( Editor_Panel_Assets::HANDLE );
		parent::tear_down();
	}

	/**
	 * The bundle is enqueued on the itsdz_doc edit screen.
	 *
	 * @return void
	 */
	public function test_enqueues_on_the_article_edit_screen() {
		set_current_screen( Article_Post_Type::POST_TYPE );

		// Confirms the fixture itself simulates the right screen before
		// trusting what it proves about Editor_Panel_Assets.
		$this->assertSame( Article_Post_Type::POST_TYPE, get_current_screen()->post_type );

		( new Editor_Panel_Assets() )->enqueue();

		$this->assertTrue( wp_script_is( Editor_Panel_Assets::HANDLE, 'registered' ) );
	}

	/**
	 * The bundle is not enqueued on the ordinary post edit screen.
	 *
	 * @return void
	 */
	public function test_does_not_enqueue_on_the_post_edit_screen() {
		set_current_screen( 'post' );

		( new Editor_Panel_Assets() )->enqueue();

		$this->assertFalse( wp_script_is( Editor_Panel_Assets::HANDLE, 'registered' ) );
	}

	/**
	 * The bundle is not enqueued on the page edit screen either.
	 *
	 * @return void
	 */
	public function test_does_not_enqueue_on_the_page_edit_screen() {
		set_current_screen( 'page' );

		( new Editor_Panel_Assets() )->enqueue();

		$this->assertFalse( wp_script_is( Editor_Panel_Assets::HANDLE, 'registered' ) );
	}

	/**
	 * Nothing fatals when no screen is set at all.
	 *
	 * @return void
	 */
	public function test_does_nothing_without_a_current_screen() {
		set_current_screen( 'front' );

		( new Editor_Panel_Assets() )->enqueue();

		$this->assertFalse( wp_script_is( Editor_Panel_Assets::HANDLE, 'registered' ) );
	}
}
