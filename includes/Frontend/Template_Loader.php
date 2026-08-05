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
		add_filter( 'the_content', array( $this, 'inject_lazy_images' ) );
		add_action( 'wp_head', array( $this, 'inject_schema_org_jsonld' ) );
	}

	/**
	 * Inject Schema.org TechArticle JSON-LD on single article pages.
	 *
	 * @return void
	 */
	public function inject_schema_org_jsonld() {
		if ( ! is_singular( Article_Post_Type::POST_TYPE ) ) {
			return;
		}

		$post = get_queried_object();

		if ( ! $post instanceof \WP_Post ) {
			return;
		}

		$author_name = get_the_author_meta( 'display_name', (int) $post->post_author );
		$site_name   = get_bloginfo( 'name' );

		$schema = array(
			'@context'         => 'https://schema.org',
			'@type'            => 'TechArticle',
			'headline'         => get_the_title( $post ),
			'description'      => wp_strip_all_tags( get_the_excerpt( $post ) ),
			'datePublished'    => get_the_date( 'c', $post ),
			'dateModified'     => get_the_modified_date( 'c', $post ),
			'mainEntityOfPage' => get_permalink( $post ),
			'author'           => array(
				'@type' => 'Person',
				'name'  => $author_name ? $author_name : $site_name,
			),
			'publisher'        => array(
				'@type' => 'Organization',
				'name'  => $site_name,
			),
		);

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '<script type="application/ld+json">' . (string) wp_json_encode( $schema, JSON_UNESCAPED_UNICODE ) . '</script>' . "\n";
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

	/**
	 * Inject loading="lazy" on images inside article content.
	 *
	 * Runs only on Doczur article singles so no other post types are affected.
	 * Images that already carry a loading attribute are left untouched.
	 *
	 * @param string $content Post content.
	 * @return string
	 */
	public function inject_lazy_images( $content ) {
		if ( ! is_singular( Article_Post_Type::POST_TYPE ) ) {
			return $content;
		}

		if ( ! str_contains( $content, '<img' ) ) {
			return $content;
		}

		return (string) preg_replace_callback(
			'/<img(?![^>]*\bloading=)[^>]*>/i',
			static function ( $matches ) {
				// Insert loading="lazy" just before the closing >.
				return substr_replace( $matches[0], ' loading="lazy"', -1, 0 );
			},
			$content
		);
	}
}
