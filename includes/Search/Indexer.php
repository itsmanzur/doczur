<?php
/**
 * Documentation search index maintenance.
 *
 * @package ItsDZ\Doczur\Search
 */

namespace ItsDZ\Doczur\Search;

use ItsDZ\Doczur\Core\Migrations\Migration_1_0_0;
use ItsDZ\Doczur\Core\Service;
use ItsDZ\Doczur\PostTypes\Article_Post_Type;
use ItsDZ\Doczur\PostTypes\KB_Post_Type;
use ItsDZ\Doczur\Utils\Cache;
use ItsDZ\Doczur\Utils\Content_Validator;

defined( 'ABSPATH' ) || exit;

/**
 * Keeps the FULLTEXT table synchronized with public articles.
 */
final class Indexer implements Service {
	/**
	 * Register indexing hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'save_post_' . Article_Post_Type::POST_TYPE, array( $this, 'handle_article_save' ), 20, 3 );
		add_action( 'save_post_' . KB_Post_Type::POST_TYPE, array( $this, 'handle_kb_save' ), 20, 3 );
		add_action( 'before_delete_post', array( $this, 'handle_delete' ), 10, 2 );
		add_action( 'set_object_terms', array( $this, 'handle_term_change' ), 10, 6 );
		add_action( 'added_post_meta', array( $this, 'handle_meta_change' ), 10, 4 );
		add_action( 'updated_post_meta', array( $this, 'handle_meta_change' ), 10, 4 );
		add_action( 'deleted_post_meta', array( $this, 'handle_meta_change' ), 10, 4 );
	}

	/**
	 * Handle an article save.
	 *
	 * @param int      $post_id Article ID.
	 * @param \WP_Post $post    Article object.
	 * @param bool     $update  Whether this is an update.
	 * @return void
	 */
	public function handle_article_save( $post_id, $post, $update ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return;
		}

		$this->index_article( $post_id );
	}

	/**
	 * Reconcile child articles when a project status changes.
	 *
	 * @param int      $post_id Project ID.
	 * @param \WP_Post $post    Project object.
	 * @param bool     $update  Whether this is an update.
	 * @return void
	 */
	public function handle_kb_save( $post_id, $post, $update ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return;
		}

		if ( 'publish' !== $post->post_status ) {
			$this->delete_kb( $post_id );

			return;
		}

		$article_ids = get_posts(
			array(
				'fields'         => 'ids',
				'meta_key'       => '_itsdz_kb_id', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value'     => $post_id, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
				'no_found_rows'  => true,
				'nopaging'       => true,
				'posts_per_page' => -1, // phpcs:ignore WordPress.WP.PostsPerPage.posts_per_page_posts_per_page
				'post_status'    => array( 'publish', 'draft', 'pending', 'private', 'future' ),
				'post_type'      => Article_Post_Type::POST_TYPE,
			)
		);

		foreach ( $article_ids as $article_id ) {
			$this->index_article( $article_id );
		}

		Cache::bump_generation( 'search', $post_id );
	}

	/**
	 * Remove deleted articles from the index.
	 *
	 * @param int      $post_id Post ID.
	 * @param \WP_Post $post    Post object.
	 * @return void
	 */
	public function handle_delete( $post_id, $post ) {
		if ( Article_Post_Type::POST_TYPE === $post->post_type ) {
			$this->delete_article( $post_id );
		}

		if ( KB_Post_Type::POST_TYPE === $post->post_type ) {
			$this->delete_kb( $post_id );
		}
	}

	/**
	 * Re-index after section, tag, or version changes.
	 *
	 * @param int    $object_id  Object ID.
	 * @param array  $terms      Terms.
	 * @param array  $term_tt_ids Term taxonomy IDs.
	 * @param string $taxonomy   Taxonomy name.
	 * @return void
	 */
	public function handle_term_change( $object_id, $terms, $term_tt_ids, $taxonomy ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		if ( in_array( $taxonomy, array( 'itsdz_section', 'itsdz_tag', 'itsdz_version' ), true ) && Article_Post_Type::POST_TYPE === get_post_type( $object_id ) ) {
			$this->index_article( $object_id );
		}
	}

	/**
	 * Re-index when an article is assigned to a project.
	 *
	 * @param int    $meta_id    Meta row ID.
	 * @param int    $object_id  Post ID.
	 * @param string $meta_key   Meta key.
	 * @param mixed  $meta_value Meta value.
	 * @return void
	 */
	public function handle_meta_change( $meta_id, $object_id, $meta_key, $meta_value ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		if ( '_itsdz_kb_id' === $meta_key && Article_Post_Type::POST_TYPE === get_post_type( $object_id ) ) {
			$this->index_article( $object_id );
		}
	}

	/**
	 * Add or update one article in the public search index.
	 *
	 * @param int $article_id Article ID.
	 * @return bool
	 */
	public function index_article( $article_id ) {
		global $wpdb;

		$article = get_post( absint( $article_id ) );
		$kb_id   = absint( get_post_meta( $article_id, '_itsdz_kb_id', true ) );

		if ( ! $article || Article_Post_Type::POST_TYPE !== $article->post_type || 'publish' !== $article->post_status || is_wp_error( Content_Validator::get_kb( $kb_id, true ) ) ) {
			$this->delete_article( $article_id, $kb_id );

			return false;
		}

		$table = Migration_1_0_0::table_names()['search_index'];
		// The current relation is needed to invalidate both cache namespaces.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$old_kb_id  = (int) $wpdb->get_var(
			$wpdb->prepare( 'SELECT kb_id FROM %i WHERE article_id = %d', $table, $article_id )
		);
		$keywords   = $this->get_keywords( $article_id );
		$content    = wp_strip_all_tags( strip_shortcodes( $article->post_content ), true );
		$word_count = str_word_count( $content );

		update_post_meta( $article_id, '_itsdz_reading_time', max( 1, (int) ceil( $word_count / 200 ) ) );

		$query = $wpdb->prepare(
			'INSERT INTO %i (article_id, kb_id, title, content_plain, keywords, weight, updated_at)
			VALUES (%d, %d, %s, %s, %s, %f, %s)
			ON DUPLICATE KEY UPDATE kb_id = VALUES(kb_id), title = VALUES(title), content_plain = VALUES(content_plain), keywords = VALUES(keywords), weight = VALUES(weight), updated_at = VALUES(updated_at)',
			$table,
			$article_id,
			$kb_id,
			$article->post_title,
			$content,
			$keywords,
			1.0,
			current_time( 'mysql', true )
		);

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$indexed = false !== $wpdb->query( $query );

		Cache::bump_generation( 'search', $kb_id );

		if ( $old_kb_id && $old_kb_id !== $kb_id ) {
			Cache::bump_generation( 'search', $old_kb_id );
		}

		return $indexed;
	}

	/**
	 * Delete one article index row.
	 *
	 * @param int $article_id Article ID.
	 * @param int $known_kb_id Known project ID, if available.
	 * @return void
	 */
	public function delete_article( $article_id, $known_kb_id = 0 ) {
		global $wpdb;

		$table = Migration_1_0_0::table_names()['search_index'];
		$kb_id = absint( $known_kb_id );

		if ( ! $kb_id ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$kb_id = (int) $wpdb->get_var(
				$wpdb->prepare( 'SELECT kb_id FROM %i WHERE article_id = %d', $table, $article_id )
			);
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query( $wpdb->prepare( 'DELETE FROM %i WHERE article_id = %d', $table, $article_id ) );

		if ( $kb_id ) {
			Cache::bump_generation( 'search', $kb_id );
		}
	}

	/**
	 * Delete all index rows for a project.
	 *
	 * @param int $kb_id Project ID.
	 * @return void
	 */
	private function delete_kb( $kb_id ) {
		global $wpdb;

		$table = Migration_1_0_0::table_names()['search_index'];

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query( $wpdb->prepare( 'DELETE FROM %i WHERE kb_id = %d', $table, $kb_id ) );
		Cache::bump_generation( 'search', $kb_id );
	}

	/**
	 * Build weighted keyword text from article terms.
	 *
	 * @param int $article_id Article ID.
	 * @return string
	 */
	private function get_keywords( $article_id ) {
		$terms = wp_get_object_terms( $article_id, array( 'itsdz_section', 'itsdz_tag', 'itsdz_version' ), array( 'fields' => 'names' ) );

		return is_wp_error( $terms ) ? '' : implode( ' ', $terms );
	}
}
