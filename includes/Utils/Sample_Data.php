<?php
/**
 * Demo content generator.
 *
 * Creates a small, realistic documentation set so a brand new install can be
 * seen working end to end without anyone writing an article first. Everything
 * it creates is tagged with a marker meta key so it can be removed again in
 * one click — nothing the user authored is ever touched.
 *
 * @package ItsDZ\Doczur\Utils
 */

namespace ItsDZ\Doczur\Utils;

use ItsDZ\Doczur\PostTypes\Article_Post_Type;

defined( 'ABSPATH' ) || exit;

/**
 * Builds and removes the bundled sample documentation.
 */
final class Sample_Data {

	/**
	 * Marker stored on every generated post and term.
	 */
	const MARKER_META = '_itsdz_is_sample';

	/**
	 * Create the sample articles and sections for a project.
	 *
	 * Existing sample content for the project is removed first so repeated
	 * clicks refresh the demo instead of stacking duplicates.
	 *
	 * @param int $kb_id Project post ID.
	 * @return array{articles: int, sections: int}
	 */
	public static function create( $kb_id ) {
		$kb_id = absint( $kb_id );

		self::remove( $kb_id );

		$section_ids = array();
		$sections    = 0;

		foreach ( self::sections() as $key => $label ) {
			$term_id = self::create_section( $label );

			if ( $term_id ) {
				$section_ids[ $key ] = $term_id;
				++$sections;
			}
		}

		$articles = 0;
		$order    = 0;

		foreach ( self::articles() as $article ) {
			$post_id = wp_insert_post(
				array(
					'menu_order'   => $order,
					'post_content' => $article['content'],
					'post_status'  => 'publish',
					'post_title'   => $article['title'],
					'post_type'    => Article_Post_Type::POST_TYPE,
				),
				true
			);

			if ( is_wp_error( $post_id ) ) {
				continue;
			}

			update_post_meta( $post_id, '_itsdz_kb_id', $kb_id );
			update_post_meta( $post_id, self::MARKER_META, 1 );

			if ( isset( $section_ids[ $article['section'] ] ) ) {
				wp_set_object_terms( $post_id, array( $section_ids[ $article['section'] ] ), 'itsdz_section' );
			}

			if ( ! empty( $article['tags'] ) ) {
				wp_set_object_terms( $post_id, $article['tags'], 'itsdz_tag' );
			}

			++$articles;
			++$order;
		}

		return array(
			'articles' => $articles,
			'sections' => $sections,
		);
	}

	/**
	 * Delete every piece of generated content for a project.
	 *
	 * Sections are only removed once they hold no articles at all, so a
	 * section the user has since filled with real articles survives.
	 *
	 * @param int $kb_id Project post ID.
	 * @return array{articles: int, sections: int}
	 */
	public static function remove( $kb_id ) {
		$kb_id = absint( $kb_id );

		$post_ids = get_posts(
			array(
				'fields'         => 'ids',
				'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					'relation' => 'AND',
					array(
						'key'   => self::MARKER_META,
						'value' => 1,
					),
					array(
						'key'   => '_itsdz_kb_id',
						'value' => $kb_id,
					),
				),
				'posts_per_page' => -1,
				'post_status'    => 'any',
				'post_type'      => Article_Post_Type::POST_TYPE,
			)
		);

		$articles = 0;

		foreach ( $post_ids as $post_id ) {
			if ( wp_delete_post( (int) $post_id, true ) ) {
				++$articles;
			}
		}

		$sections = 0;
		$terms    = get_terms(
			array(
				'hide_empty' => false,
				'meta_key'   => self::MARKER_META, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value' => 1, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
				'taxonomy'   => 'itsdz_section',
			)
		);

		if ( is_array( $terms ) ) {
			foreach ( $terms as $term ) {
				if ( ! $term instanceof \WP_Term ) {
					continue;
				}

				// Re-read the count; deleting the posts above changed it.
				$fresh = get_term( $term->term_id, 'itsdz_section' );

				if ( ! $fresh instanceof \WP_Term ) {
					continue;
				}

				// Leave any section the user has since filled with real articles.
				if ( (int) $fresh->count > 0 ) {
					continue;
				}

				if ( ! is_wp_error( wp_delete_term( $term->term_id, 'itsdz_section' ) ) ) {
					++$sections;
				}
			}
		}

		return array(
			'articles' => $articles,
			'sections' => $sections,
		);
	}

	/**
	 * Whether a project currently holds generated content.
	 *
	 * @param int $kb_id Project post ID.
	 * @return bool
	 */
	public static function exists( $kb_id ) {
		$found = get_posts(
			array(
				'fields'         => 'ids',
				'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					'relation' => 'AND',
					array(
						'key'   => self::MARKER_META,
						'value' => 1,
					),
					array(
						'key'   => '_itsdz_kb_id',
						'value' => absint( $kb_id ),
					),
				),
				'posts_per_page' => 1,
				'post_status'    => 'any',
				'post_type'      => Article_Post_Type::POST_TYPE,
			)
		);

		return ! empty( $found );
	}

	// -------------------------------------------------------------------------
	// Content definition
	// -------------------------------------------------------------------------

	/**
	 * Create one marked section, reusing a matching term when present.
	 *
	 * @param string $label Section name.
	 * @return int Term ID, or 0 on failure.
	 */
	private static function create_section( $label ) {
		$existing = get_term_by( 'name', $label, 'itsdz_section' );

		if ( $existing instanceof \WP_Term ) {
			update_term_meta( $existing->term_id, self::MARKER_META, 1 );

			return $existing->term_id;
		}

		$created = wp_insert_term( $label, 'itsdz_section' );

		if ( is_wp_error( $created ) ) {
			return 0;
		}

		update_term_meta( $created['term_id'], self::MARKER_META, 1 );

		return (int) $created['term_id'];
	}

	/**
	 * Section labels keyed by internal slug.
	 *
	 * Public so the content definition can be inspected and covered by tests
	 * without touching the database.
	 *
	 * @return array<string, string>
	 */
	public static function sections() {
		return array(
			'start'   => __( 'Getting Started', 'itsmanzur-docs' ),
			'writing' => __( 'Writing Docs', 'itsmanzur-docs' ),
			'help'    => __( 'Troubleshooting', 'itsmanzur-docs' ),
		);
	}

	/**
	 * The sample articles.
	 *
	 * Content deliberately exercises every formatting feature Nirdeshio ships —
	 * callouts, tables, accordions and code blocks — so the demo doubles as a
	 * live reference for what the editor can produce.
	 *
	 * Public so the content definition can be inspected and covered by tests
	 * without touching the database.
	 *
	 * @return array<int, array{title: string, section: string, tags: string[], content: string}>
	 */
	public static function articles() {
		return array(
			array(
				'title'   => __( 'Welcome to your documentation', 'itsmanzur-docs' ),
				'section' => 'start',
				'tags'    => array( __( 'intro', 'itsmanzur-docs' ) ),
				'content' => '<p>' . __( 'This is sample content created by Nirdeshio so you can see a finished documentation site straight away. Explore it, edit anything you like, and remove it in one click from the Nirdeshio settings screen when you are ready to publish your own articles.', 'itsmanzur-docs' ) . '</p>
<div class="itsdz-callout itsdz-callout-tip"><p>' . __( 'Every article here is real, editable content. Nothing is locked.', 'itsmanzur-docs' ) . '</p></div>
<h2>' . __( 'What to try first', 'itsmanzur-docs' ) . '</h2>
<ul>
<li>' . __( 'Use the search box at the top — it is instant, and powered by a dedicated index.', 'itsmanzur-docs' ) . '</li>
<li>' . __( 'Open this article on a phone to see the slide-out navigation.', 'itsmanzur-docs' ) . '</li>
<li>' . __( 'Scroll down and answer "Was this article helpful?" to see feedback collection.', 'itsmanzur-docs' ) . '</li>
</ul>',
			),
			array(
				'title'   => __( 'Installing the plugin', 'itsmanzur-docs' ),
				'section' => 'start',
				'tags'    => array( __( 'setup', 'itsmanzur-docs' ) ),
				'content' => '<p>' . __( 'Nirdeshio installs like any other WordPress plugin. Once activated, the setup wizard creates your first documentation project.', 'itsmanzur-docs' ) . '</p>
<h2>' . __( 'Install with WP-CLI', 'itsmanzur-docs' ) . '</h2>
<pre><code>wp plugin install itsmanzur-docs --activate</code></pre>
<div class="itsdz-callout itsdz-callout-info"><p>' . __( 'Nirdeshio requires WordPress 6.5 or newer and PHP 8.0 or newer.', 'itsmanzur-docs' ) . '</p></div>',
			),
			array(
				'title'   => __( 'Your first article in five minutes', 'itsmanzur-docs' ),
				'section' => 'start',
				'tags'    => array( __( 'setup', 'itsmanzur-docs' ), __( 'intro', 'itsmanzur-docs' ) ),
				'content' => '<p>' . __( 'Here is the shortest possible path from a blank install to a published article.', 'itsmanzur-docs' ) . '</p>
<ol>
<li>' . __( 'Open Nirdeshio in the admin sidebar.', 'itsmanzur-docs' ) . '</li>
<li>' . __( 'Click "New article" and give it a title.', 'itsmanzur-docs' ) . '</li>
<li>' . __( 'Write your content — the editor saves automatically as you type.', 'itsmanzur-docs' ) . '</li>
<li>' . __( 'Switch the status to Published, then hit Preview.', 'itsmanzur-docs' ) . '</li>
</ol>
<div class="itsdz-callout itsdz-callout-tip"><p>' . __( 'Drag articles in the left-hand tree to change the order they appear in your documentation.', 'itsmanzur-docs' ) . '</p></div>',
			),
			array(
				'title'   => __( 'Formatting: callouts, tables and code', 'itsmanzur-docs' ),
				'section' => 'writing',
				'tags'    => array( __( 'formatting', 'itsmanzur-docs' ) ),
				'content' => '<p>' . __( 'The editor toolbar inserts every element below. You never need to write HTML by hand.', 'itsmanzur-docs' ) . '</p>
<h2>' . __( 'Callouts', 'itsmanzur-docs' ) . '</h2>
<div class="itsdz-callout itsdz-callout-info"><p>' . __( 'Info — background detail that helps but is not critical.', 'itsmanzur-docs' ) . '</p></div>
<div class="itsdz-callout itsdz-callout-tip"><p>' . __( 'Tip — a shortcut or best practice.', 'itsmanzur-docs' ) . '</p></div>
<div class="itsdz-callout itsdz-callout-warning"><p>' . __( 'Warning — something that often trips people up.', 'itsmanzur-docs' ) . '</p></div>
<div class="itsdz-callout itsdz-callout-danger"><p>' . __( 'Danger — an action that loses data or cannot be undone.', 'itsmanzur-docs' ) . '</p></div>
<h2>' . __( 'Tables', 'itsmanzur-docs' ) . '</h2>
<table class="itsdz-article-table">
<thead><tr><th>' . __( 'Plan', 'itsmanzur-docs' ) . '</th><th>' . __( 'Projects', 'itsmanzur-docs' ) . '</th><th>' . __( 'Support', 'itsmanzur-docs' ) . '</th></tr></thead>
<tbody>
<tr><td>' . __( 'Free', 'itsmanzur-docs' ) . '</td><td>1</td><td>' . __( 'Community forum', 'itsmanzur-docs' ) . '</td></tr>
<tr><td>' . __( 'Personal', 'itsmanzur-docs' ) . '</td><td>' . __( 'Unlimited', 'itsmanzur-docs' ) . '</td><td>' . __( 'Email', 'itsmanzur-docs' ) . '</td></tr>
<tr><td>' . __( 'Agency', 'itsmanzur-docs' ) . '</td><td>' . __( 'Unlimited', 'itsmanzur-docs' ) . '</td><td>' . __( 'Priority email', 'itsmanzur-docs' ) . '</td></tr>
</tbody>
</table>
<h2>' . __( 'Code', 'itsmanzur-docs' ) . '</h2>
<pre><code>add_filter( \'itsdz_show_article_author\', \'__return_false\' );</code></pre>',
			),
			array(
				'title'   => __( 'Organising sections, tags and versions', 'itsmanzur-docs' ),
				'section' => 'writing',
				'tags'    => array( __( 'formatting', 'itsmanzur-docs' ) ),
				'content' => '<p>' . __( 'Three different tools decide where an article shows up. Use the right one and your navigation stays clean as the documentation grows.', 'itsmanzur-docs' ) . '</p>
<table class="itsdz-article-table">
<thead><tr><th>' . __( 'Tool', 'itsmanzur-docs' ) . '</th><th>' . __( 'Use it for', 'itsmanzur-docs' ) . '</th></tr></thead>
<tbody>
<tr><td>' . __( 'Section', 'itsmanzur-docs' ) . '</td><td>' . __( 'The single place an article lives in the sidebar.', 'itsmanzur-docs' ) . '</td></tr>
<tr><td>' . __( 'Tag', 'itsmanzur-docs' ) . '</td><td>' . __( 'Cross-cutting topics that span several sections.', 'itsmanzur-docs' ) . '</td></tr>
<tr><td>' . __( 'Version', 'itsmanzur-docs' ) . '</td><td>' . __( 'Marking which release an article applies to.', 'itsmanzur-docs' ) . '</td></tr>
</tbody>
</table>
<div class="itsdz-callout itsdz-callout-warning"><p>' . __( 'An article belongs to exactly one section. Reach for tags when you want it to appear in more than one place.', 'itsmanzur-docs' ) . '</p></div>',
			),
			array(
				'title'   => __( 'Keeping documentation up to date', 'itsmanzur-docs' ),
				'section' => 'writing',
				'tags'    => array( __( 'maintenance', 'itsmanzur-docs' ) ),
				'content' => '<p>' . __( 'Stale documentation costs more support time than missing documentation. Nirdeshio tracks a review date for every article.', 'itsmanzur-docs' ) . '</p>
<p>' . __( 'Open any article in the editor and use "Mark reviewed today" once you have checked it is still accurate. Articles that go 90 days without a review get a "Needs review" badge in the article list, so nothing quietly rots.', 'itsmanzur-docs' ) . '</p>
<div class="itsdz-callout itsdz-callout-tip"><p>' . __( 'Pair the review date with the "Was this helpful?" results to decide what to rewrite first.', 'itsmanzur-docs' ) . '</p></div>',
			),
			array(
				'title'   => __( 'Frequently asked questions', 'itsmanzur-docs' ),
				'section' => 'help',
				'tags'    => array( __( 'faq', 'itsmanzur-docs' ) ),
				'content' => '<p>' . __( 'The answers below use collapsible blocks — handy whenever a page has many short answers.', 'itsmanzur-docs' ) . '</p>
<details class="itsdz-accordion"><summary>' . __( 'Will Nirdeshio slow down my site?', 'itsmanzur-docs' ) . '</summary><div class="itsdz-accordion-body"><p>' . __( 'No. Scripts and styles load only on documentation pages, and view counts are written in batches rather than on every page load.', 'itsmanzur-docs' ) . '</p></div></details>
<details class="itsdz-accordion"><summary>' . __( 'Does it work with my theme?', 'itsmanzur-docs' ) . '</summary><div class="itsdz-accordion-body"><p>' . __( 'Yes. Every style is scoped so it cannot leak into your theme, and you can choose between a full-page canvas layout or one that keeps your header and footer.', 'itsmanzur-docs' ) . '</p></div></details>
<details class="itsdz-accordion"><summary>' . __( 'Can I move my content in from another plugin?', 'itsmanzur-docs' ) . '</summary><div class="itsdz-accordion-body"><p>' . __( 'You can import and export your whole project as JSON from the Import / Export screen.', 'itsmanzur-docs' ) . '</p></div></details>',
			),
			array(
				'title'   => __( 'Troubleshooting checklist', 'itsmanzur-docs' ),
				'section' => 'help',
				'tags'    => array( __( 'faq', 'itsmanzur-docs' ), __( 'maintenance', 'itsmanzur-docs' ) ),
				'content' => '<p>' . __( 'Work through these in order — they resolve the large majority of reports.', 'itsmanzur-docs' ) . '</p>
<h2>' . __( 'Documentation pages return a 404', 'itsmanzur-docs' ) . '</h2>
<p>' . __( 'Visit Settings → Permalinks and save once. That rebuilds the URL rules WordPress uses for documentation pages.', 'itsmanzur-docs' ) . '</p>
<h2>' . __( 'Search returns nothing', 'itsmanzur-docs' ) . '</h2>
<p>' . __( 'Only published articles are indexed. Open a draft, publish it, and try again.', 'itsmanzur-docs' ) . '</p>
<div class="itsdz-callout itsdz-callout-danger"><p>' . __( 'Removing sample content deletes those articles permanently. Any edits you made to them go too, so copy anything worth keeping first.', 'itsmanzur-docs' ) . '</p></div>',
			),
		);
	}
}
