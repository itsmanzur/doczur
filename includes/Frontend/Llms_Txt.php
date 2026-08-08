<?php
/**
 * Machine-readable documentation map served at /llms.txt.
 *
 * Publishes a machine-readable map of the documentation at /llms.txt,
 * following the llmstxt.org convention. Assistants that read a site before
 * answering questions about it get an accurate index instead of guessing from
 * rendered HTML.
 *
 * Two routes are served:
 *   /llms.txt      — the index: one line per article, grouped by project.
 *   /llms-full.txt — the index plus the full plain-text body of every article.
 *
 * @package ItsDZ\Doczur\Frontend
 */

namespace ItsDZ\Doczur\Frontend;

use ItsDZ\Doczur\Core\Service;
use ItsDZ\Doczur\PostTypes\KB_Post_Type;
use ItsDZ\Doczur\Utils\Cache;

defined( 'ABSPATH' ) || exit;

/**
 * Serves the llms.txt documentation map.
 */
final class Llms_Txt implements Service {

	/**
	 * Query variable carrying the requested variant.
	 */
	const QUERY_VAR = 'itsdz_llms';

	/**
	 * Option storing whether the endpoint is enabled.
	 */
	const OPTION = 'itsdz_llms_txt_enabled';

	/**
	 * How long a generated document is cached.
	 */
	const CACHE_TTL = 12 * HOUR_IN_SECONDS;

	/**
	 * Maximum articles listed per project.
	 *
	 * The convention favours a curated index over a full sitemap dump, and an
	 * unbounded document would be expensive to build on large sites.
	 */
	const MAX_ARTICLES = 200;

	/**
	 * Register WordPress hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'init', array( $this, 'add_rewrite_rules' ), 7 );
		add_filter( 'query_vars', array( $this, 'add_query_var' ) );
		add_action( 'template_redirect', array( $this, 'maybe_render' ), 0 );

		// Any documentation change invalidates the generated document.
		add_action( 'save_post_' . KB_Post_Type::POST_TYPE, array( $this, 'flush_cache' ) );
		add_action( 'save_post_itsdz_doc', array( $this, 'flush_cache' ) );
		add_action( 'deleted_post', array( $this, 'flush_cache' ) );
	}

	/**
	 * Register the root-level routes.
	 *
	 * @return void
	 */
	public function add_rewrite_rules() {
		add_rewrite_rule( '^llms\.txt$', 'index.php?' . self::QUERY_VAR . '=index', 'top' );
		add_rewrite_rule( '^llms-full\.txt$', 'index.php?' . self::QUERY_VAR . '=full', 'top' );
	}

	/**
	 * Allow the variant query variable through.
	 *
	 * @param string[] $vars Registered public query variables.
	 * @return string[]
	 */
	public function add_query_var( $vars ) {
		$vars[] = self::QUERY_VAR;

		return $vars;
	}

	/**
	 * Whether the endpoint is switched on.
	 *
	 * Some site owners would rather not advertise their content to AI
	 * crawlers, so this can be turned off without deactivating the plugin:
	 *
	 *     add_filter( 'itsdz_llms_txt_enabled', '__return_false' );
	 *
	 * @return bool
	 */
	public static function is_enabled() {
		/**
		 * Filter whether the llms.txt endpoints are served.
		 *
		 * @param bool $enabled Whether the routes respond.
		 */
		return (bool) apply_filters( 'itsdz_llms_txt_enabled', (bool) get_option( self::OPTION, true ) );
	}

	/**
	 * Render the document when one of the routes is requested.
	 *
	 * @return void
	 */
	public function maybe_render() {
		$variant = get_query_var( self::QUERY_VAR );

		if ( 'index' !== $variant && 'full' !== $variant ) {
			return;
		}

		if ( ! self::is_enabled() ) {
			status_header( 404 );
			nocache_headers();
			exit;
		}

		$body = $this->get_document( $variant );

		status_header( 200 );
		header( 'Content-Type: text/plain; charset=utf-8' );
		header( 'X-Robots-Tag: noindex' );
		header( 'Cache-Control: public, max-age=3600' );

		echo $body; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Plain-text response; every field is escaped while building.
		exit;
	}

	/**
	 * Fetch the document, building it only when the cache is cold.
	 *
	 * @param string $variant Either `index` or `full`.
	 * @return string
	 */
	public function get_document( $variant ) {
		$key    = 'llms_txt:' . $variant . ':' . Cache::generation( 'llms', 0 );
		$cached = Cache::get( $key );

		if ( is_string( $cached ) && '' !== $cached ) {
			return $cached;
		}

		$document = $this->build( 'full' === $variant );

		Cache::set( $key, $document, self::CACHE_TTL );

		return $document;
	}

	/**
	 * Drop the cached documents.
	 *
	 * @return void
	 */
	public function flush_cache() {
		Cache::bump_generation( 'llms', 0 );
	}

	/**
	 * Build the document.
	 *
	 * @param bool $include_body Whether to append full article text.
	 * @return string
	 */
	private function build( $include_body ) {
		$lines = array();

		$lines[] = '# ' . $this->clean( get_bloginfo( 'name' ) );
		$lines[] = '';

		$tagline = $this->clean( get_bloginfo( 'description' ) );

		if ( '' !== $tagline ) {
			$lines[] = '> ' . $tagline;
			$lines[] = '';
		}

		$projects = get_posts(
			array(
				'orderby'        => 'menu_order title',
				'order'          => 'ASC',
				'post_status'    => 'publish',
				'post_type'      => KB_Post_Type::POST_TYPE,
				'posts_per_page' => -1,
			)
		);

		if ( empty( $projects ) ) {
			$lines[] = 'No public documentation has been published yet.';

			return implode( "\n", $lines ) . "\n";
		}

		foreach ( $projects as $project ) {
			$lines[] = '## ' . $this->clean( get_the_title( $project ) );
			$lines[] = '';

			$summary = $this->clean( wp_strip_all_tags( $project->post_content ) );

			if ( '' !== $summary ) {
				$lines[] = wp_trim_words( $summary, 40, '…' );
				$lines[] = '';
			}

			$articles = array_slice( Documentation::get_articles( $project->ID ), 0, self::MAX_ARTICLES );

			if ( empty( $articles ) ) {
				$lines[] = '(No published articles.)';
				$lines[] = '';
				continue;
			}

			foreach ( $articles as $article ) {
				$title   = $this->clean( get_the_title( $article ) );
				$url     = get_permalink( $article );
				$excerpt = $this->clean( wp_strip_all_tags( $article->post_content ) );
				$excerpt = wp_trim_words( $excerpt, 25, '…' );
				$lines[] = '- [' . $title . '](' . esc_url_raw( (string) $url ) . ')' . ( '' !== $excerpt ? ': ' . $excerpt : '' );
			}

			$lines[] = '';

			if ( $include_body ) {
				foreach ( $articles as $article ) {
					$lines[] = '### ' . $this->clean( get_the_title( $article ) );
					$lines[] = '';
					$lines[] = $this->plain_body( $article->post_content );
					$lines[] = '';
				}
			}
		}

		return implode( "\n", $lines ) . "\n";
	}

	/**
	 * Convert article HTML to readable plain text.
	 *
	 * Unlike clean(), this keeps paragraph and list boundaries — the full
	 * variant is meant to be read, not just indexed.
	 *
	 * @param string $html Article content.
	 * @return string
	 */
	private function plain_body( $html ) {
		// Turn block boundaries into newlines before the tags are removed.
		$text = preg_replace( '#</(p|div|h[1-6]|li|tr|pre|details|summary|figcaption|blockquote)>#i', "\n", (string) $html );
		$text = preg_replace( '#<br\s*/?>#i', "\n", (string) $text );
		$text = preg_replace( '#<li[^>]*>#i', '- ', (string) $text );

		$text = wp_strip_all_tags( (string) $text );
		$text = html_entity_decode( $text, ENT_QUOTES, 'UTF-8' );

		// Collapse runs of blank lines and trailing spaces.
		$text = preg_replace( '/[ \t]+/u', ' ', $text );
		$text = preg_replace( '/ *\n */u', "\n", (string) $text );
		$text = preg_replace( '/\n{3,}/u', "\n\n", (string) $text );

		return trim( (string) $text );
	}

	/**
	 * Collapse whitespace so one entry never spans multiple lines.
	 *
	 * @param string $text Raw text.
	 * @return string
	 */
	private function clean( $text ) {
		$text = html_entity_decode( (string) $text, ENT_QUOTES, 'UTF-8' );
		$text = preg_replace( '/\s+/u', ' ', $text );

		return trim( (string) $text );
	}
}
