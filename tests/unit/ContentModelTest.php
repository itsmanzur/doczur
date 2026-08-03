<?php
/**
 * Content model registration tests.
 *
 * @package ItsDZ\Doczur\Tests\Unit
 */

namespace ItsDZ\Doczur\Tests\Unit;

use ItsDZ\Doczur\PostTypes\Article_Post_Type;
use ItsDZ\Doczur\PostTypes\KB_Post_Type;
use ItsDZ\Doczur\PostTypes\Meta_Fields;
use ItsDZ\Doczur\Security\Capabilities;
use ItsDZ\Doczur\Taxonomies\Section_Taxonomy;
use ItsDZ\Doczur\Taxonomies\Tag_Taxonomy;
use ItsDZ\Doczur\Taxonomies\Version_Taxonomy;
use PHPUnit\Framework\TestCase;

/**
 * Verifies CPT and taxonomy registration arguments.
 */
final class ContentModelTest extends TestCase {
	/**
	 * Reset captured registrations.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		$GLOBALS['itsdz_test_post_types']    = array();
		$GLOBALS['itsdz_test_taxonomies']    = array();
		$GLOBALS['itsdz_test_post_meta']     = array();
		$GLOBALS['itsdz_test_rewrite_rules'] = array();
	}

	/**
	 * CPTs expose Gutenberg while protecting mutations.
	 *
	 * @return void
	 */
	public function test_post_types_register_with_expected_contract() {
		( new KB_Post_Type() )->register_post_type();
		( new Article_Post_Type() )->register_post_type();

		$this->assertArrayHasKey( KB_Post_Type::POST_TYPE, $GLOBALS['itsdz_test_post_types'] );
		$this->assertArrayHasKey( Article_Post_Type::POST_TYPE, $GLOBALS['itsdz_test_post_types'] );

		$kb      = $GLOBALS['itsdz_test_post_types'][ KB_Post_Type::POST_TYPE ];
		$article = $GLOBALS['itsdz_test_post_types'][ Article_Post_Type::POST_TYPE ];

		$this->assertTrue( $article['show_in_rest'] );
		$this->assertFalse( $kb['show_in_menu'] );
		$this->assertFalse( $article['show_in_menu'] );
		$this->assertContains( 'revisions', $article['supports'] );
		$this->assertContains( 'page-attributes', $article['supports'] );
		$this->assertSame( Capabilities::MANAGE_DOCS, $article['capabilities']['create_posts'] );
		$this->assertSame( '^docs/article/([^/]+)/?$', $GLOBALS['itsdz_test_rewrite_rules'][0]['regex'] );
		$this->assertSame( 'top', $GLOBALS['itsdz_test_rewrite_rules'][0]['position'] );
	}

	/**
	 * Taxonomies attach to articles with the expected visibility.
	 *
	 * @return void
	 */
	public function test_taxonomies_register_with_expected_contract() {
		( new Section_Taxonomy() )->register_taxonomy();
		( new Tag_Taxonomy() )->register_taxonomy();
		( new Version_Taxonomy() )->register_taxonomy();

		$section = $GLOBALS['itsdz_test_taxonomies'][ Section_Taxonomy::TAXONOMY ];
		$tag     = $GLOBALS['itsdz_test_taxonomies'][ Tag_Taxonomy::TAXONOMY ];
		$version = $GLOBALS['itsdz_test_taxonomies'][ Version_Taxonomy::TAXONOMY ];

		$this->assertSame( array( Article_Post_Type::POST_TYPE ), $section['post_types'] );
		$this->assertTrue( $section['args']['hierarchical'] );
		$this->assertFalse( $tag['args']['hierarchical'] );
		$this->assertFalse( $version['args']['show_ui'] );
		$this->assertFalse( $version['args']['show_in_rest'] );
	}

	/**
	 * Registered meta matches the roadmap content model.
	 *
	 * @return void
	 */
	public function test_content_meta_registers_with_rest_protection() {
		( new Meta_Fields() )->register_meta();

		$kb_meta      = $GLOBALS['itsdz_test_post_meta'][ KB_Post_Type::POST_TYPE ];
		$article_meta = $GLOBALS['itsdz_test_post_meta'][ Article_Post_Type::POST_TYPE ];

		$this->assertCount( 8, $kb_meta );
		$this->assertCount( 4, $article_meta );
		$this->assertSame( 'integer', $article_meta['_itsdz_kb_id']['type'] );
		$this->assertTrue( $article_meta['_itsdz_kb_id']['show_in_rest'] );
		$this->assertIsCallable( $article_meta['_itsdz_kb_id']['auth_callback'] );
		$this->assertSame( '', ( new Meta_Fields() )->sanitize_slug( '' ) );
		$this->assertSame( 'product-docs', ( new Meta_Fields() )->sanitize_slug( 'Product Docs' ) );
	}
}
