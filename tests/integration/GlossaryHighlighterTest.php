<?php
/**
 * Glossary auto-linking tests.
 *
 * This filter rewrites the HTML of every article, so the tests below are
 * mostly about what it must NOT do: corrupt markup, touch attributes, or
 * reach inside links and code samples.
 *
 * @package ItsDZ\Doczur\Tests\Integration
 */

namespace ItsDZ\Doczur\Tests\Integration;

use ItsDZ\Doczur\Frontend\Glossary_Highlighter;
use WP_UnitTestCase;

/**
 * Proves glossary highlighting is safe on real article markup.
 */
final class GlossaryHighlighterTest extends WP_UnitTestCase {

	/**
	 * Glossary fixture.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	private $entries = array();

	/**
	 * Build a small glossary.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();

		$this->entries = array(
			array(
				'term_id'    => 1,
				'term'       => 'API',
				'definition' => 'Application Programming Interface.',
				'aliases'    => array( 'APIs' ),
			),
			array(
				'term_id'    => 2,
				'term'       => 'webhook',
				'definition' => 'An HTTP callback fired on an event.',
				'aliases'    => array(),
			),
		);
	}

	/**
	 * Run the highlighter over a snippet.
	 *
	 * @param string $html Source HTML.
	 * @return string
	 */
	private function apply( $html ) {
		return Glossary_Highlighter::apply( $html, $this->entries );
	}

	/**
	 * A plain mention is wrapped with its definition.
	 *
	 * @return void
	 */
	public function test_first_mention_is_wrapped() {
		$result = $this->apply( '<p>Call the API to begin.</p>' );

		$this->assertStringContainsString( '<abbr class="itsdz-glossary-term"', $result );
		$this->assertStringContainsString( 'Application Programming Interface.', $result );
	}

	/**
	 * Only the first mention of a term is wrapped.
	 *
	 * @return void
	 */
	public function test_only_the_first_mention_is_wrapped() {
		$result = $this->apply( '<p>The API is here.</p><p>The API is there.</p>' );

		$this->assertSame( 1, substr_count( $result, 'itsdz-glossary-term' ) );
	}

	/**
	 * Terms inside attributes are never rewritten.
	 *
	 * This is the failure mode a naive string replace would produce: a class
	 * name or URL containing the term would be corrupted into invalid markup.
	 *
	 * @return void
	 */
	public function test_attributes_are_never_touched() {
		$html   = '<p class="API-notice"><img src="https://example.test/API/logo.png" alt="API logo" /></p>';
		$result = $this->apply( $html );

		$this->assertStringContainsString( 'class="API-notice"', $result );
		$this->assertStringContainsString( 'src="https://example.test/API/logo.png"', $result );
		$this->assertStringContainsString( 'alt="API logo"', $result );
		$this->assertStringNotContainsString( 'itsdz-glossary-term', $result );
	}

	/**
	 * Links are left alone so anchors are never nested.
	 *
	 * @return void
	 */
	public function test_links_are_skipped() {
		$result = $this->apply( '<p><a href="https://example.test">Read the API guide</a></p>' );

		$this->assertStringNotContainsString( 'itsdz-glossary-term', $result );
	}

	/**
	 * Code samples stay verbatim.
	 *
	 * @return void
	 */
	public function test_code_and_pre_are_skipped() {
		$result = $this->apply( '<pre><code>curl https://example.test/API</code></pre>' );

		$this->assertStringNotContainsString( 'itsdz-glossary-term', $result );
		$this->assertStringContainsString( 'curl https://example.test/API', $result );
	}

	/**
	 * Headings are skipped so the table of contents stays clean.
	 *
	 * @return void
	 */
	public function test_headings_are_skipped() {
		$result = $this->apply( '<h2>API basics</h2><p>Nothing else here.</p>' );

		$this->assertStringContainsString( '<h2>API basics</h2>', $result );
		$this->assertStringNotContainsString( 'itsdz-glossary-term', $result );
	}

	/**
	 * Matching respects word boundaries.
	 *
	 * @return void
	 */
	public function test_partial_words_do_not_match() {
		$result = $this->apply( '<p>The rapidly changing APIfication trend.</p>' );

		$this->assertStringNotContainsString( 'itsdz-glossary-term', $result );
	}

	/**
	 * Aliases are matched too, keeping their own casing.
	 *
	 * @return void
	 */
	public function test_aliases_are_matched() {
		$result = $this->apply( '<p>Several APIs are available.</p>' );

		$this->assertStringContainsString( 'itsdz-glossary-term', $result );
		$this->assertStringContainsString( '>APIs<', $result );
	}

	/**
	 * The original casing of the matched text is preserved.
	 *
	 * @return void
	 */
	public function test_original_casing_is_preserved() {
		$result = $this->apply( '<p>A Webhook fires immediately.</p>' );

		$this->assertStringContainsString( '>Webhook<', $result );
		$this->assertStringNotContainsString( '>webhook<', $result );
	}

	/**
	 * Surrounding markup survives untouched.
	 *
	 * @return void
	 */
	public function test_complex_markup_is_preserved() {
		$html = '<div class="itsdz-callout itsdz-callout-info"><p>Use the API here.</p></div>'
			. '<table class="itsdz-article-table"><tbody><tr><td>Cell</td></tr></tbody></table>'
			. '<details class="itsdz-accordion"><summary>More</summary><p>Detail text.</p></details>';

		$result = $this->apply( $html );

		$this->assertStringContainsString( 'class="itsdz-callout itsdz-callout-info"', $result );
		$this->assertStringContainsString( '<table class="itsdz-article-table">', $result );
		$this->assertStringContainsString( '<details class="itsdz-accordion">', $result );
		$this->assertStringContainsString( '<summary>More</summary>', $result );
		$this->assertStringContainsString( 'itsdz-glossary-term', $result );
	}

	/**
	 * Non-ASCII content survives the round trip.
	 *
	 * @return void
	 */
	public function test_unicode_content_is_preserved() {
		$result = $this->apply( '<p>ডকুমেন্টেশন API ব্যবহার করুন — সহজ।</p>' );

		$this->assertStringContainsString( 'ডকুমেন্টেশন', $result );
		$this->assertStringContainsString( 'ব্যবহার করুন — সহজ।', $result );
		$this->assertStringContainsString( 'itsdz-glossary-term', $result );
	}

	/**
	 * Content without any glossary term comes back byte-identical.
	 *
	 * @return void
	 */
	public function test_content_without_terms_is_returned_unchanged() {
		$html = '<p>Nothing of interest here.</p>';

		$this->assertSame( $html, $this->apply( $html ) );
	}

	/**
	 * An entry with no definition is ignored.
	 *
	 * @return void
	 */
	public function test_entries_without_a_definition_are_skipped() {
		$result = Glossary_Highlighter::apply(
			'<p>Call the API now.</p>',
			array(
				array(
					'term_id'    => 9,
					'term'       => 'API',
					'definition' => '',
					'aliases'    => array(),
				),
			)
		);

		$this->assertStringNotContainsString( 'itsdz-glossary-term', $result );
	}

	/**
	 * Longer terms win over shorter ones they contain.
	 *
	 * @return void
	 */
	public function test_longer_terms_take_precedence() {
		$entries = array(
			array(
				'term_id'    => 1,
				'term'       => 'API',
				'definition' => 'Short.',
				'aliases'    => array(),
			),
			array(
				'term_id'    => 2,
				'term'       => 'API key',
				'definition' => 'A secret used to authenticate.',
				'aliases'    => array(),
			),
		);

		$result = Glossary_Highlighter::apply( '<p>Paste your API key below.</p>', $entries );

		$this->assertStringContainsString( '>API key<', $result );
		$this->assertStringContainsString( 'A secret used to authenticate.', $result );
	}

	/**
	 * Already-highlighted markup is not processed twice.
	 *
	 * @return void
	 */
	public function test_existing_markers_are_not_nested() {
		$once  = $this->apply( '<p>The API matters.</p>' );
		$twice = $this->apply( $once );

		$this->assertSame( 1, substr_count( $twice, 'itsdz-glossary-term' ) );
	}

	/**
	 * Site owners can switch auto-linking off.
	 *
	 * @return void
	 */
	public function test_autolinking_can_be_disabled_by_filter() {
		$this->assertTrue( Glossary_Highlighter::is_enabled() );

		add_filter( 'itsdz_glossary_autolink', '__return_false' );
		$this->assertFalse( Glossary_Highlighter::is_enabled() );
		remove_filter( 'itsdz_glossary_autolink', '__return_false' );
	}
}
