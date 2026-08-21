<?php
/**
 * Save-path-independent content sanitization.
 *
 * @package ItsDZ\Doczur\Security
 */

namespace ItsDZ\Doczur\Security;

use ItsDZ\Doczur\Core\Service;
use ItsDZ\Doczur\PostTypes\Article_Post_Type;
use ItsDZ\Doczur\Utils\Content_Validator;

defined( 'ABSPATH' ) || exit;

/**
 * Enforces Content_Validator::sanitize_content() on every article save.
 *
 * The Nirdeshio REST API already sanitizes article content on the way in, but
 * that only covers articles saved through Nirdeshio's own admin screen. An
 * article can also be edited through the native WordPress block editor
 * (`post.php?action=edit`), the core REST API, WP-CLI, or an import script —
 * none of which pass through Nirdeshio's controller. On a single-site install
 * Administrators hold `unfiltered_html` by default, so any of those other
 * paths would otherwise save raw, unrestricted HTML: the iframe host
 * allowlist and every other rule in Content_Validator would simply not
 * apply.
 *
 * Hooking `wp_insert_post_data` closes that gap at the one point every save
 * path converges on, regardless of capability or origin.
 */
final class Content_Sanitizer implements Service {

	/**
	 * Register WordPress hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_filter( 'wp_insert_post_data', array( $this, 'sanitize' ), 10, 2 );
	}

	/**
	 * Sanitize article content before it reaches the database.
	 *
	 * @param array<string, mixed> $data    Slashed, sanitized post data to be inserted.
	 * @param array<string, mixed> $postarr Raw, unsanitized post data as originally passed.
	 * @return array<string, mixed>
	 */
	public function sanitize( $data, $postarr ) {
		unset( $postarr );

		if ( ! isset( $data['post_type'] ) || Article_Post_Type::POST_TYPE !== $data['post_type'] ) {
			return $data;
		}

		// wp_insert_post_data() runs on slashed data; sanitize_content() runs
		// wp_kses(), which expects and returns unslashed content.
		$data['post_content'] = wp_slash(
			Content_Validator::sanitize_content( wp_unslash( $data['post_content'] ?? '' ) )
		);

		return $data;
	}
}
