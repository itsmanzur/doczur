<?php
/**
 * Glossary taxonomy and rendering wiring tests.
 *
 * GlossaryHighlighterTest covers the replacement algorithm in isolation; this
 * file proves the surrounding plumbing — taxonomy, term meta and the rendered
 * list — actually works against real WordPress.
 *
 * @package ItsDZ\Doczur\Tests\Integration
 */

namespace ItsDZ\Doczur\Tests\Integration;

use ItsDZ\Doczur\Frontend\Renderers;
use ItsDZ\Doczur\Taxonomies\Glossary_Taxonomy;
use WP_UnitTestCase;

/**
 * Proves glossary terms round-trip through WordPress correctly.
 */
final class GlossaryIntegrationTest extends WP_UnitTestCase {

	/**
	 * Create a glossary term with aliases.
	 *
	 * @param string $name       Term name.
	 * @param string $definition Definition.
	 * @param string $aliases    Comma separated aliases.
	 * @return int Term ID.
	 */
	private function make_term( $name, $definition, $aliases = '' ) {
		$term = wp_insert_term(
			$name,
			Glossary_Taxonomy::TAXONOMY,
			array( 'description' => $definition )
		);

		$this->assertIsArray( $term, 'The glossary term could not be created.' );

		if ( '' !== $aliases ) {
			update_term_meta( $term['term_id'], Glossary_Taxonomy::ALIASES_META, $aliases );
		}

		return (int) $term['term_id'];
	}

	/**
	 * The taxonomy is registered and hidden from the public.
	 *
	 * @return void
	 */
	public function test_taxonomy_is_registered_privately() {
		$taxonomy = get_taxonomy( Glossary_Taxonomy::TAXONOMY );

		$this->assertNotFalse( $taxonomy );
		$this->assertFalse( $taxonomy->public, 'Glossary terms should not have public archives.' );
		$this->assertTrue( $taxonomy->show_in_rest, 'The admin screen needs REST access.' );
	}

	/**
	 * Entries come back with their definition and parsed aliases.
	 *
	 * @return void
	 */
	public function test_entries_include_definition_and_aliases() {
		$this->make_term( 'Webhook', 'An HTTP callback.', 'webhooks, web hook' );

		$entries = Glossary_Taxonomy::get_entries();

		$this->assertCount( 1, $entries );
		$this->assertSame( 'Webhook', $entries[0]['term'] );
		$this->assertSame( 'An HTTP callback.', $entries[0]['definition'] );
		$this->assertSame( array( 'webhooks', 'web hook' ), $entries[0]['aliases'] );
	}

	/**
	 * Alias input is normalized and de-duplicated on save.
	 *
	 * @return void
	 */
	public function test_alias_meta_is_sanitized() {
		$term_id = $this->make_term( 'API', 'An interface.', '  APIs ,, APIs ,  api  ' );

		$this->assertSame(
			'APIs, api',
			get_term_meta( $term_id, Glossary_Taxonomy::ALIASES_META, true )
		);
	}

	/**
	 * The glossary list renders every defined term.
	 *
	 * @return void
	 */
	public function test_glossary_list_renders_terms() {
		$this->make_term( 'Webhook', 'An HTTP callback.', 'webhooks' );
		$this->make_term( 'Token', 'A credential.' );

		$html = Renderers::glossary( 'Reference', true );

		$this->assertStringContainsString( 'Reference', $html );
		$this->assertStringContainsString( 'Webhook', $html );
		$this->assertStringContainsString( 'An HTTP callback.', $html );
		$this->assertStringContainsString( 'Token', $html );
		$this->assertStringContainsString( 'webhooks', $html );
	}

	/**
	 * Terms without a definition are left out of the list.
	 *
	 * @return void
	 */
	public function test_glossary_list_skips_terms_without_definitions() {
		$this->make_term( 'Defined', 'Has a definition.' );
		$this->make_term( 'Undefined', '' );

		$html = Renderers::glossary();

		$this->assertStringContainsString( 'Defined', $html );
		$this->assertStringNotContainsString( 'Undefined', $html );
	}

	/**
	 * An empty glossary renders a notice rather than an empty list.
	 *
	 * @return void
	 */
	public function test_empty_glossary_renders_a_notice() {
		$html = Renderers::glossary();

		$this->assertStringContainsString( 'itsdz-block-notice', $html );
	}

	/**
	 * The shortcode is registered.
	 *
	 * @return void
	 */
	public function test_shortcode_is_registered() {
		$this->assertTrue( shortcode_exists( 'doczur_glossary' ) );
	}

	/**
	 * Definitions are escaped, so a term cannot inject markup.
	 *
	 * @return void
	 */
	public function test_definitions_are_escaped_in_the_list() {
		$this->make_term( 'Risky', '<script>alert(1)</script> plain text' );

		$html = Renderers::glossary();

		$this->assertStringNotContainsString( '<script>', $html );
		$this->assertStringContainsString( 'plain text', $html );
	}
}
