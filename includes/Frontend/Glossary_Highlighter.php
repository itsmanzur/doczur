<?php
/**
 * Automatic glossary term highlighting.
 *
 * Wraps the first mention of each glossary term in an article so readers get
 * the definition without leaving the page.
 *
 * The replacement walks the parsed DOM and only ever rewrites text nodes.
 * A naive string replace would corrupt markup the moment a term appeared
 * inside an attribute (a class name, a URL, an alt text), so that approach is
 * deliberately avoided.
 *
 * @package ItsDZ\Doczur\Frontend
 */

namespace ItsDZ\Doczur\Frontend;

use ItsDZ\Doczur\Core\Service;
use ItsDZ\Doczur\PostTypes\Article_Post_Type;
use ItsDZ\Doczur\Taxonomies\Glossary_Taxonomy;
use ItsDZ\Doczur\Utils\Cache;

defined( 'ABSPATH' ) || exit;

/**
 * Links glossary terms inside article content.
 */
final class Glossary_Highlighter implements Service {

	/**
	 * Elements whose text must never be touched.
	 *
	 * Headings are excluded so the table of contents stays clean, links so we
	 * never nest interactive elements, and code so samples stay verbatim.
	 */
	const SKIPPED_ANCESTORS = array(
		'a',
		'abbr',
		'button',
		'code',
		'h1',
		'h2',
		'h3',
		'h4',
		'h5',
		'h6',
		'pre',
		'script',
		'style',
		'textarea',
	);

	/**
	 * Register WordPress hooks.
	 *
	 * Priority 20 keeps this after wpautop so paragraphs already exist.
	 *
	 * @return void
	 */
	public function register() {
		add_filter( 'the_content', array( $this, 'maybe_highlight' ), 20 );
	}

	/**
	 * Whether automatic highlighting is switched on.
	 *
	 * @return bool
	 */
	public static function is_enabled() {
		/**
		 * Filter whether glossary terms are auto-linked inside articles.
		 *
		 * @param bool $enabled Whether highlighting runs.
		 */
		return (bool) apply_filters( 'itsdz_glossary_autolink', true );
	}

	/**
	 * Highlight glossary terms on single article views.
	 *
	 * @param string $content Post content.
	 * @return string
	 */
	public function maybe_highlight( $content ) {
		if ( ! is_singular( Article_Post_Type::POST_TYPE ) || ! self::is_enabled() ) {
			return $content;
		}

		/*
		 * The article template renders through setup_postdata() rather than a
		 * have_posts() loop, so in_the_loop() is false here. Comparing the post
		 * being filtered against the queried one is both accurate and stricter:
		 * excerpts rendered for other posts on the same request are left alone.
		 */
		if ( (int) get_the_ID() !== (int) get_queried_object_id() ) {
			return $content;
		}

		return self::cached_apply( $content );
	}

	/**
	 * Apply highlighting, caching the result per article.
	 *
	 * Every request for the same article ran the full DOM walk from scratch;
	 * for a long article against a large glossary that cost is paid on every
	 * single pageview. The cache key folds in a hash of the content itself,
	 * so an edit is a natural cache miss with no save-time hook required, and
	 * a generation counter that every glossary edit bumps, so a changed
	 * definition or a new term takes effect immediately everywhere.
	 *
	 * @param string $content Post content.
	 * @return string
	 */
	private static function cached_apply( $content ) {
		$key    = 'glossary:' . Cache::generation( 'glossary', 0 ) . ':' . md5( $content );
		$cached = Cache::get( $key );

		if ( is_string( $cached ) ) {
			return $cached;
		}

		$result = self::apply( $content, Glossary_Taxonomy::get_entries() );

		Cache::set( $key, $result, HOUR_IN_SECONDS );

		return $result;
	}

	/**
	 * Wrap the first occurrence of each glossary term.
	 *
	 * @param string                                                                               $content Article HTML.
	 * @param array<int, array{term_id: int, term: string, definition: string, aliases: string[]}> $entries Glossary entries.
	 * @return string
	 */
	public static function apply( $content, $entries ) {
		if ( '' === trim( (string) $content ) || empty( $entries ) ) {
			return $content;
		}

		$patterns = self::build_patterns( $entries );

		if ( empty( $patterns ) ) {
			return $content;
		}

		$dom = self::load( $content );

		if ( ! $dom instanceof \DOMDocument ) {
			return $content;
		}

		$xpath = new \DOMXPath( $dom );

		// getElementById() needs a DTD to know which attribute is an ID, and
		// there is none here, so the wrapper is located by query instead.
		$wrapper = $xpath->query( '//div[@id="itsdz-glossary-root"]' );
		$root    = ( $wrapper instanceof \DOMNodeList && $wrapper->length > 0 ) ? $wrapper->item( 0 ) : null;

		if ( ! $root instanceof \DOMElement ) {
			return $content;
		}

		$skip = array();

		foreach ( self::SKIPPED_ANCESTORS as $tag ) {
			$skip[] = 'ancestor::' . $tag;
		}

		$text_nodes = $xpath->query( './/text()[not(' . implode( ' or ', $skip ) . ')]', $root );

		if ( ! $text_nodes instanceof \DOMNodeList ) {
			return $content;
		}

		$used = array();

		foreach ( $text_nodes as $node ) {
			if ( count( $used ) === count( $patterns ) ) {
				break;
			}

			self::process_text_node( $dom, $node, $patterns, $used );
		}

		if ( empty( $used ) ) {
			return $content;
		}

		return self::save( $dom, $root );
	}

	/**
	 * Rewrite one text node, wrapping the first unused term it contains.
	 *
	 * @param \DOMDocument                                                      $dom      Owner document.
	 * @param \DOMNode                                                          $node     Text node.
	 * @param array<int, array{regex: string, definition: string, key: string}> $patterns Compiled patterns.
	 * @param array<string, bool>                                               $used     Terms already linked, by reference.
	 * @return void
	 */
	private static function process_text_node( $dom, $node, $patterns, &$used ) {
		// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- DOM API property.
		$text = $node->nodeValue;

		if ( '' === trim( (string) $text ) ) {
			return;
		}

		foreach ( $patterns as $pattern ) {
			if ( isset( $used[ $pattern['key'] ] ) ) {
				continue;
			}

			if ( ! preg_match( $pattern['regex'], (string) $text, $matches, PREG_OFFSET_CAPTURE ) ) {
				continue;
			}

			$matched = $matches[0][0];
			$offset  = $matches[0][1];

			$fragment = $dom->createDocumentFragment();

			$before = substr( (string) $text, 0, $offset );
			$after  = substr( (string) $text, $offset + strlen( $matched ) );

			if ( '' !== $before ) {
				$fragment->appendChild( $dom->createTextNode( $before ) );
			}

			$fragment->appendChild( self::create_marker( $dom, $matched, $pattern['definition'] ) );

			if ( '' !== $after ) {
				$fragment->appendChild( $dom->createTextNode( $after ) );
			}

			// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- DOM API property.
			$node->parentNode->replaceChild( $fragment, $node );

			$used[ $pattern['key'] ] = true;

			// The node is gone; remaining terms are picked up on later nodes.
			return;
		}
	}

	/**
	 * Build the glossary marker element.
	 *
	 * The definition sits in a nested span rather than a title attribute so
	 * the styled tooltip does not fight the browser's native one, and screen
	 * readers still announce the definition after the term.
	 *
	 * @param \DOMDocument $dom        Owner document.
	 * @param string       $label      Matched text, with its original casing.
	 * @param string       $definition Term definition.
	 * @return \DOMElement
	 */
	private static function create_marker( $dom, $label, $definition ) {
		$marker = $dom->createElement( 'abbr' );
		$marker->setAttribute( 'class', 'itsdz-glossary-term' );
		$marker->setAttribute( 'tabindex', '0' );
		$marker->appendChild( $dom->createTextNode( $label ) );

		$tooltip = $dom->createElement( 'span' );
		$tooltip->setAttribute( 'class', 'itsdz-glossary-tip' );
		$tooltip->setAttribute( 'role', 'tooltip' );
		$tooltip->appendChild( $dom->createTextNode( $definition ) );

		$marker->appendChild( $tooltip );

		return $marker;
	}

	/**
	 * Compile one case-insensitive pattern per term and alias.
	 *
	 * Longer terms are matched first so "API key" wins over "API".
	 *
	 * @param array<int, array{term_id: int, term: string, definition: string, aliases: string[]}> $entries Glossary entries.
	 * @return array<int, array{regex: string, definition: string, key: string}>
	 */
	private static function build_patterns( $entries ) {
		$patterns = array();

		foreach ( $entries as $entry ) {
			$definition = trim( wp_strip_all_tags( (string) $entry['definition'] ) );

			if ( '' === $definition ) {
				continue;
			}

			$labels = array_merge( array( $entry['term'] ), $entry['aliases'] );

			foreach ( $labels as $label ) {
				$label = trim( (string) $label );

				if ( '' === $label ) {
					continue;
				}

				$patterns[] = array(
					'definition' => $definition,
					'key'        => (string) $entry['term_id'],
					'length'     => mb_strlen( $label ),
					'regex'      => '/(?<![\w-])' . preg_quote( $label, '/' ) . '(?![\w-])/ui',
				);
			}
		}

		usort(
			$patterns,
			static function ( $a, $b ) {
				return $b['length'] <=> $a['length'];
			}
		);

		return $patterns;
	}

	/**
	 * Parse article HTML into a document.
	 *
	 * @param string $content Article HTML.
	 * @return \DOMDocument|null
	 */
	private static function load( $content ) {
		$dom      = new \DOMDocument();
		$previous = libxml_use_internal_errors( true );

		// The XML declaration forces UTF-8 parsing; the wrapper gives a single
		// predictable root to read back without picking up <html>/<body>.
		$loaded = $dom->loadHTML(
			'<?xml encoding="utf-8" ?><div id="itsdz-glossary-root">' . $content . '</div>',
			LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
		);

		libxml_clear_errors();
		libxml_use_internal_errors( $previous );

		return $loaded ? $dom : null;
	}

	/**
	 * Serialize the wrapper's children back to HTML.
	 *
	 * @param \DOMDocument $dom  Document.
	 * @param \DOMElement  $root Wrapper element.
	 * @return string
	 */
	private static function save( $dom, $root ) {
		$html = '';

		// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- DOM API property.
		foreach ( $root->childNodes as $child ) {
			$html .= $dom->saveHTML( $child );
		}

		return $html;
	}
}
