<?php
/**
 * Sample content definition tests.
 *
 * The demo content is a large hand-written HTML blob, so these tests guard
 * against the mistakes that are easy to make while editing it — a mistyped
 * section key, an empty field, or an unbalanced tag that would render the
 * generated documentation broken on a brand new install.
 *
 * @package ItsDZ\Doczur\Tests\Unit
 */

namespace ItsDZ\Doczur\Tests\Unit;

use ItsDZ\Doczur\Utils\Sample_Data;
use PHPUnit\Framework\TestCase;

/**
 * Proves the bundled sample documentation is structurally sound.
 */
final class SampleDataTest extends TestCase {

	/**
	 * Every article carries the fields the generator writes to the database.
	 *
	 * @return void
	 */
	public function test_every_article_defines_the_required_fields() {
		foreach ( Sample_Data::articles() as $index => $article ) {
			$this->assertNotEmpty( $article['title'], "Article {$index} is missing a title." );
			$this->assertNotEmpty( $article['content'], "Article {$index} is missing content." );
			$this->assertIsArray( $article['tags'], "Article {$index} must declare a tag array." );
		}
	}

	/**
	 * Articles may only reference sections the generator actually creates.
	 *
	 * A typo here would silently produce unsectioned articles.
	 *
	 * @return void
	 */
	public function test_articles_only_reference_known_sections() {
		$sections = array_keys( Sample_Data::sections() );

		foreach ( Sample_Data::articles() as $index => $article ) {
			$this->assertContains(
				$article['section'],
				$sections,
				"Article {$index} points at an unknown section '{$article['section']}'."
			);
		}
	}

	/**
	 * Each generated section is used, so the demo has no empty navigation entries.
	 *
	 * @return void
	 */
	public function test_every_section_holds_at_least_one_article() {
		$used = array_column( Sample_Data::articles(), 'section' );

		foreach ( array_keys( Sample_Data::sections() ) as $section ) {
			$this->assertContains( $section, $used, "Section '{$section}' would render empty." );
		}
	}

	/**
	 * Container markup is balanced.
	 *
	 * An unclosed div or details element leaks into the rest of the page and
	 * visibly breaks the layout, which is exactly the first impression this
	 * feature exists to protect.
	 *
	 * @return void
	 */
	public function test_article_markup_is_balanced() {
		foreach ( Sample_Data::articles() as $index => $article ) {
			foreach ( array( 'div', 'details', 'table', 'ul', 'ol' ) as $tag ) {
				$opened = preg_match_all( '/<' . $tag . '[\s>]/', $article['content'] );
				$closed = substr_count( $article['content'], '</' . $tag . '>' );

				$this->assertSame(
					$opened,
					$closed,
					"Article {$index} has {$opened} <{$tag}> but {$closed} </{$tag}>."
				);
			}
		}
	}

	/**
	 * Callouts use the variant classes the frontend stylesheet ships.
	 *
	 * @return void
	 */
	public function test_callouts_use_supported_variants() {
		$supported = array( 'info', 'warning', 'danger', 'tip' );

		foreach ( Sample_Data::articles() as $index => $article ) {
			preg_match_all( '/itsdz-callout-([a-z]+)/', $article['content'], $matches );

			foreach ( $matches[1] as $variant ) {
				$this->assertContains(
					$variant,
					$supported,
					"Article {$index} uses unsupported callout variant '{$variant}'."
				);
			}
		}
	}
}
