<?php
/**
 * Documentation map (llms.txt) tests.
 *
 * @package ItsDZ\Doczur\Tests\Integration
 */

namespace ItsDZ\Doczur\Tests\Integration;

use ItsDZ\Doczur\Frontend\Llms_Txt;
use ItsDZ\Doczur\PostTypes\Article_Post_Type;
use ItsDZ\Doczur\PostTypes\KB_Post_Type;
use WP_UnitTestCase;

/**
 * Proves the generated documentation map is correct and leak-free.
 */
final class LlmsTxtTest extends WP_UnitTestCase {

	/**
	 * Published knowledge base.
	 *
	 * @var int
	 */
	private $kb_id = 0;

	/**
	 * Build a project with published and unpublished articles.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();

		$this->kb_id = self::factory()->post->create(
			array(
				'post_content' => 'Everything about the widget platform.',
				'post_status'  => 'publish',
				'post_title'   => 'Widget Handbook',
				'post_type'    => KB_Post_Type::POST_TYPE,
			)
		);

		// The body is long enough that the 25-word index excerpt stops before
		// the marker, so the index and full variants can be told apart.
		$filler = str_repeat( 'padding ', 40 );

		foreach ( array( 'publish', 'draft', 'private' ) as $status ) {
			$article_id = self::factory()->post->create(
				array(
					'post_content' => "<p>Body of the {$status} article. {$filler}</p><p>DEEPMARKER-{$status}</p>",
					'post_status'  => $status,
					'post_title'   => ucfirst( $status ) . ' article',
					'post_type'    => Article_Post_Type::POST_TYPE,
				)
			);
			update_post_meta( $article_id, '_itsdz_kb_id', $this->kb_id );
		}

		( new Llms_Txt() )->flush_cache();
	}

	/**
	 * The index lists the project and its published article.
	 *
	 * @return void
	 */
	public function test_index_lists_published_content() {
		$document = ( new Llms_Txt() )->get_document( 'index' );

		$this->assertStringContainsString( '# ', $document, 'The document has no title heading.' );
		$this->assertStringContainsString( 'Widget Handbook', $document );
		$this->assertStringContainsString( 'Publish article', $document );
	}

	/**
	 * Unpublished articles never appear in the document.
	 *
	 * @return void
	 */
	public function test_index_excludes_unpublished_articles() {
		$document = ( new Llms_Txt() )->get_document( 'index' );

		$this->assertStringNotContainsString( 'Draft article', $document );
		$this->assertStringNotContainsString( 'Private article', $document );
	}

	/**
	 * Articles of an unpublished project never appear.
	 *
	 * @return void
	 */
	public function test_index_excludes_unpublished_projects() {
		$draft_kb = self::factory()->post->create(
			array(
				'post_status' => 'draft',
				'post_title'  => 'Unreleased Handbook',
				'post_type'   => KB_Post_Type::POST_TYPE,
			)
		);

		$article_id = self::factory()->post->create(
			array(
				'post_status' => 'publish',
				'post_title'  => 'Hidden article',
				'post_type'   => Article_Post_Type::POST_TYPE,
			)
		);
		update_post_meta( $article_id, '_itsdz_kb_id', $draft_kb );

		( new Llms_Txt() )->flush_cache();
		$document = ( new Llms_Txt() )->get_document( 'index' );

		$this->assertStringNotContainsString( 'Unreleased Handbook', $document );
		$this->assertStringNotContainsString( 'Hidden article', $document );
	}

	/**
	 * Each listed article carries a resolvable link.
	 *
	 * @return void
	 */
	public function test_entries_are_markdown_links() {
		$document = ( new Llms_Txt() )->get_document( 'index' );

		$this->assertMatchesRegularExpression(
			'/^- \[Publish article\]\(https?:\/\/\S+\)/m',
			$document,
			'The article entry is not a Markdown link.'
		);
	}

	/**
	 * The full variant appends article bodies; the index does not.
	 *
	 * @return void
	 */
	public function test_full_variant_includes_article_bodies() {
		$service = new Llms_Txt();

		$index = $service->get_document( 'index' );
		$full  = $service->get_document( 'full' );

		// The index carries only a short excerpt, so the deep marker is absent.
		$this->assertStringNotContainsString( 'DEEPMARKER-publish', $index );
		$this->assertStringContainsString( 'DEEPMARKER-publish', $full );

		// Unpublished bodies are absent from both.
		$this->assertStringNotContainsString( 'DEEPMARKER-draft', $full );
		$this->assertStringNotContainsString( 'DEEPMARKER-private', $full );
	}

	/**
	 * Generated output is plain text, never HTML.
	 *
	 * @return void
	 */
	public function test_document_contains_no_markup() {
		$full = ( new Llms_Txt() )->get_document( 'full' );

		$this->assertStringNotContainsString( '<p>', $full );
		$this->assertStringNotContainsString( '</', $full );
	}

	/**
	 * Truncated entries end with a real ellipsis, not an HTML entity.
	 *
	 * WordPress appends `&hellip;` when trimming words, which would otherwise
	 * survive into a document that is supposed to be plain text.
	 *
	 * @return void
	 */
	public function test_truncated_entries_contain_no_html_entities() {
		$document = ( new Llms_Txt() )->get_document( 'index' );

		// The fixture bodies are long enough that entries are truncated.
		$this->assertStringContainsString( '…', $document, 'No entry was truncated, so this test proves nothing.' );
		$this->assertStringNotContainsString( '&hellip;', $document );
		$this->assertStringNotContainsString( '&amp;', $document );
	}

	/**
	 * Site owners can switch the endpoint off with a filter.
	 *
	 * @return void
	 */
	public function test_endpoint_can_be_disabled_by_filter() {
		$this->assertTrue( Llms_Txt::is_enabled() );

		add_filter( 'itsdz_llms_txt_enabled', '__return_false' );
		$this->assertFalse( Llms_Txt::is_enabled() );
		remove_filter( 'itsdz_llms_txt_enabled', '__return_false' );

		$this->assertTrue( Llms_Txt::is_enabled() );
	}

	/**
	 * The routes are registered so WordPress can resolve them.
	 *
	 * @return void
	 */
	public function test_rewrite_rules_are_registered() {
		global $wp_rewrite;

		// Pretty permalinks are off by default in the test environment.
		$this->set_permalink_structure( '/%postname%/' );
		$wp_rewrite->flush_rules( false );

		$rules = $wp_rewrite->wp_rewrite_rules();

		$this->assertArrayHasKey( '^llms\.txt$', $rules );
		$this->assertArrayHasKey( '^llms-full\.txt$', $rules );

		$this->set_permalink_structure( '' );
	}
}
