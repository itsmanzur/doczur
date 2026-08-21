<?php
/**
 * Save-path-independent content sanitization tests.
 *
 * The Nirdeshio REST API sanitizes article content on its own, but that only
 * covers saves made through Nirdeshio's admin screen. Since article content is
 * now edited through the native WordPress block editor, these tests prove
 * the same rules apply when a post is saved by calling wp_insert_post() /
 * wp_update_post() directly — the exact code path Gutenberg, WP-CLI, and any
 * importer ultimately go through, none of which touch Nirdeshio's REST
 * controller at all.
 *
 * @package ItsDZ\Doczur\Tests\Integration
 */

namespace ItsDZ\Doczur\Tests\Integration;

use ItsDZ\Doczur\PostTypes\Article_Post_Type;
use ItsDZ\Doczur\PostTypes\KB_Post_Type;
use WP_UnitTestCase;

/**
 * Proves Content_Validator applies regardless of who is saving.
 */
final class ContentSanitizerTest extends WP_UnitTestCase {

	/**
	 * Log in as an Administrator before every test.
	 *
	 * WordPress's own content_save_pre kses filter already strips iframes
	 * for any user lacking unfiltered_html — that is a separate, earlier,
	 * even stricter layer than Content_Validator, and the default test user
	 * (ID 0) is one such user. Testing against it would prove nothing about
	 * this fix. The scenario this fix exists for is the opposite one: an
	 * Administrator, who has unfiltered_html by default and so bypasses
	 * WordPress's own layer entirely — leaving Content_Validator as the only
	 * thing still applying the iframe host allowlist.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();

		$admin_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );

		$this->assertTrue(
			current_user_can( 'unfiltered_html' ),
			'This suite requires a user who bypasses WordPress\'s own content_save_pre kses layer.'
		);
	}

	/**
	 * A disallowed iframe is stripped even on a plain wp_insert_post() call.
	 *
	 * This is the exact gap the reviewer flagged: an Administrator editing
	 * an article through post.php (native Gutenberg) has unfiltered_html by
	 * default, so nothing but this filter stands between them and an
	 * arbitrary iframe embed.
	 *
	 * @return void
	 */
	public function test_disallowed_iframe_is_stripped_on_native_save() {
		$post_id = wp_insert_post(
			array(
				'post_content' => '<p>See below.</p><iframe src="https://evil.example.com/track"></iframe>',
				'post_status'  => 'publish',
				'post_title'   => 'Native save test',
				'post_type'    => Article_Post_Type::POST_TYPE,
			),
			true
		);

		$this->assertIsInt( $post_id );

		$saved = get_post( $post_id );

		$this->assertStringNotContainsString( 'evil.example.com', $saved->post_content );
		$this->assertStringContainsString( '<p>See below.</p>', $saved->post_content );
	}

	/**
	 * An allowlisted iframe (YouTube) survives a native save unchanged.
	 *
	 * @return void
	 */
	public function test_allowlisted_iframe_survives_native_save() {
		$post_id = wp_insert_post(
			array(
				'post_content' => '<iframe src="https://www.youtube.com/embed/dQw4w9WgXcQ"></iframe>',
				'post_status'  => 'publish',
				'post_title'   => 'Native save allowlist test',
				'post_type'    => Article_Post_Type::POST_TYPE,
			),
			true
		);

		$saved = get_post( $post_id );

		$this->assertStringContainsString( 'youtube.com/embed/dQw4w9WgXcQ', $saved->post_content );
	}

	/**
	 * A disallowed iframe is stripped on wp_update_post() too, not only on create.
	 *
	 * @return void
	 */
	public function test_disallowed_iframe_is_stripped_on_native_update() {
		$post_id = self::factory()->post->create(
			array(
				'post_content' => '<p>Original.</p>',
				'post_status'  => 'draft',
				'post_type'    => Article_Post_Type::POST_TYPE,
			)
		);

		wp_update_post(
			array(
				'ID'           => $post_id,
				'post_content' => '<iframe src="https://evil.example.com/track"></iframe>',
			)
		);

		$saved = get_post( $post_id );

		$this->assertStringNotContainsString( 'evil.example.com', $saved->post_content );
	}

	/**
	 * The filter only touches Nirdeshio articles — other post types are untouched.
	 *
	 * @return void
	 */
	public function test_other_post_types_are_not_sanitized() {
		$post_id = wp_insert_post(
			array(
				'post_content' => '<iframe src="https://evil.example.com/track"></iframe>',
				'post_status'  => 'publish',
				'post_title'   => 'Ordinary post',
				'post_type'    => 'post',
			),
			true
		);

		$saved = get_post( $post_id );

		$this->assertStringContainsString( 'evil.example.com', $saved->post_content );
	}

	/**
	 * A knowledge base project post is also left untouched.
	 *
	 * Content_Validator's rules (the iframe allowlist, the itsdz-* class
	 * allowlist) are article-specific; KB project posts carry only a short
	 * description and should not be forced through the same filter.
	 *
	 * @return void
	 */
	public function test_kb_post_type_is_not_sanitized() {
		$post_id = wp_insert_post(
			array(
				'post_content' => '<iframe src="https://evil.example.com/track"></iframe>',
				'post_status'  => 'publish',
				'post_title'   => 'Handbook',
				'post_type'    => KB_Post_Type::POST_TYPE,
			),
			true
		);

		$saved = get_post( $post_id );

		$this->assertStringContainsString( 'evil.example.com', $saved->post_content );
	}

	/**
	 * Content with no HTML at all round-trips unchanged.
	 *
	 * @return void
	 */
	public function test_plain_text_content_is_unaffected() {
		$post_id = wp_insert_post(
			array(
				'post_content' => 'Just plain text, nothing fancy.',
				'post_status'  => 'publish',
				'post_title'   => 'Plain text test',
				'post_type'    => Article_Post_Type::POST_TYPE,
			),
			true
		);

		$saved = get_post( $post_id );

		$this->assertSame( 'Just plain text, nothing fancy.', $saved->post_content );
	}
}
