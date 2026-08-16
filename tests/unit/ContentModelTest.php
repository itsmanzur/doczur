<?php
/**
 * Content model registration tests.
 *
 * @package ItsDZ\Doczur\Tests\Unit
 */

namespace ItsDZ\Doczur\Tests\Unit;

use ItsDZ\Doczur\Frontend\Rewrite_Manager;
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
		$GLOBALS['itsdz_test_options']       = array();
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
		$this->assertSame( 'docs', $kb['rewrite']['slug'] );
		$this->assertSame( 'docs/article', $article['rewrite']['slug'] );
	}

	/**
	 * A saved slug base becomes the public documentation URL prefix.
	 *
	 * @return void
	 */
	public function test_post_types_register_with_configured_slug_base() {
		$GLOBALS['itsdz_test_options'][ KB_Post_Type::SLUG_OPTION ] = 'help';

		( new KB_Post_Type() )->register_post_type();
		( new Article_Post_Type() )->register_post_type();

		$kb      = $GLOBALS['itsdz_test_post_types'][ KB_Post_Type::POST_TYPE ];
		$article = $GLOBALS['itsdz_test_post_types'][ Article_Post_Type::POST_TYPE ];

		$this->assertSame( 'help', $kb['rewrite']['slug'] );
		$this->assertSame( 'help/article', $article['rewrite']['slug'] );
		$this->assertSame( '^help/article/([^/]+)/?$', $GLOBALS['itsdz_test_rewrite_rules'][0]['regex'] );
	}

	/**
	 * Changing the slug base stores it and requests a rewrite flush.
	 *
	 * @return void
	 */
	public function test_persist_rewrite_slug_flags_a_flush_when_the_prefix_changes() {
		KB_Post_Type::persist_rewrite_slug( 'Product Docs' );

		$this->assertSame( 'product-docs', KB_Post_Type::rewrite_slug() );
		$this->assertSame( '1', $GLOBALS['itsdz_test_options'][ Rewrite_Manager::FLUSH_OPTION ] );
	}

	/**
	 * An empty slug base falls back to docs without a needless flush.
	 *
	 * @return void
	 */
	public function test_persist_rewrite_slug_ignores_empty_values() {
		KB_Post_Type::persist_rewrite_slug( '' );

		$this->assertSame( 'docs', KB_Post_Type::rewrite_slug() );
		$this->assertArrayNotHasKey( Rewrite_Manager::FLUSH_OPTION, $GLOBALS['itsdz_test_options'] );
	}

	/**
	 * A flagged slug change flushes rules after post types have re-registered.
	 *
	 * @return void
	 */
	public function test_rewrite_manager_flushes_when_flagged() {
		$GLOBALS['itsdz_test_options'][ Rewrite_Manager::FLUSH_OPTION ] = '1';
		$GLOBALS['itsdz_test_rewrite_flushed']                          = false;

		( new Rewrite_Manager() )->maybe_flush();

		$this->assertTrue( $GLOBALS['itsdz_test_rewrite_flushed'] );
		$this->assertSame( Rewrite_Manager::VERSION, $GLOBALS['itsdz_test_options'][ Rewrite_Manager::VERSION_OPTION ] );
		$this->assertArrayNotHasKey( Rewrite_Manager::FLUSH_OPTION, $GLOBALS['itsdz_test_options'] );
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
		$this->assertTrue( $version['args']['hierarchical'] );
		$this->assertTrue( $version['args']['show_in_rest'] );
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
