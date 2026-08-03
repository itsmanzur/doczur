<?php
/**
 * Public documentation template loader.
 *
 * @package ItsDZ\Doczur\Frontend
 */

namespace ItsDZ\Doczur\Frontend;

use ItsDZ\Doczur\Core\Service;
use ItsDZ\Doczur\PostTypes\Article_Post_Type;
use ItsDZ\Doczur\PostTypes\KB_Post_Type;

defined( 'ABSPATH' ) || exit;

/**
 * Routes public documentation requests to theme-independent templates.
 */
final class Template_Loader implements Service {
	/**
	 * Register WordPress hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'template_redirect', array( $this, 'guard_article' ), 1 );
		add_filter( 'template_include', array( $this, 'select_template' ), 99 );
		add_filter( 'document_title_parts', array( $this, 'document_title' ) );
	}

	/**
	 * Prevent a published article from exposing an unpublished or missing KB.
	 *
	 * @return void
	 */
	public function guard_article() {
		if ( ! is_singular( Article_Post_Type::POST_TYPE ) || Documentation::get_article_kb( get_queried_object_id() ) ) {
			return;
		}

		global $wp_query;

		$wp_query->set_404();
		status_header( 404 );
		nocache_headers();
	}

	/**
	 * Select a plugin template for documentation content.
	 *
	 * @param string $template Theme template path.
	 * @return string
	 */
	public function select_template( $template ) {
		if ( is_singular( KB_Post_Type::POST_TYPE ) ) {
			return ITSDZ_PLUGIN_DIR . 'templates/kb.php';
		}

		if ( is_singular( Article_Post_Type::POST_TYPE ) && Documentation::get_article_kb( get_queried_object_id() ) ) {
			return ITSDZ_PLUGIN_DIR . 'templates/article.php';
		}

		return $template;
	}

	/**
	 * Give article titles useful KB context.
	 *
	 * @param array<string, string> $parts Document title parts.
	 * @return array<string, string>
	 */
	public function document_title( $parts ) {
		if ( is_singular( Article_Post_Type::POST_TYPE ) ) {
			$kb = Documentation::get_article_kb( get_queried_object_id() );

			if ( $kb ) {
				$parts['site'] = get_the_title( $kb );
			}
		}

		return $parts;
	}
}
