<?php
/**
 * Shared markup builders for blocks and shortcodes.
 *
 * Both the Gutenberg block render callbacks and the classic shortcodes call
 * into this class so the two entry points can never drift apart.
 *
 * @package ItsDZ\Doczur\Frontend
 */

namespace ItsDZ\Doczur\Frontend;

use ItsDZ\Doczur\Analytics\View_Tracker;
use ItsDZ\Doczur\PostTypes\KB_Post_Type;
use ItsDZ\Doczur\Taxonomies\Glossary_Taxonomy;

defined( 'ABSPATH' ) || exit;

/**
 * Builds the HTML shared by blocks and shortcodes.
 */
final class Renderers {

	/**
	 * Maximum FAQ entries rendered by a single block or shortcode.
	 */
	const MAX_FAQ_ITEMS = 50;

	/**
	 * Render a notice shown when a block or shortcode is misconfigured.
	 *
	 * @param string $message Human readable message.
	 * @return string
	 */
	public static function notice( $message ) {
		return '<p class="itsdz-block-notice">' . esc_html( $message ) . '</p>';
	}

	// -------------------------------------------------------------------------
	// FAQ
	// -------------------------------------------------------------------------

	/**
	 * Render a FAQ list with collapsible answers.
	 *
	 * Reuses the `.itsdz-accordion` markup so FAQ entries look identical to
	 * accordions written inside article content.
	 *
	 * @param array<int, array<string, mixed>> $items      Question/answer pairs.
	 * @param string                           $heading    Optional heading above the list.
	 * @param bool                             $add_schema Whether to emit FAQPage JSON-LD.
	 * @return string
	 */
	public static function faq( $items, $heading = '', $add_schema = true ) {
		$items = array_slice( self::normalize_faq_items( $items ), 0, self::MAX_FAQ_ITEMS );

		if ( empty( $items ) ) {
			return self::notice( __( 'Nirdeshio: add at least one question to the FAQ.', 'itsmanzur-docs' ) );
		}

		ob_start();
		?>
		<div class="itsdz-faq-block">
			<?php if ( '' !== $heading ) : ?>
				<h2 class="itsdz-faq-block-heading"><?php echo esc_html( $heading ); ?></h2>
			<?php endif; ?>
			<?php foreach ( $items as $item ) : ?>
				<details class="itsdz-accordion itsdz-faq-item">
					<summary><?php echo esc_html( $item['question'] ); ?></summary>
					<div class="itsdz-faq-answer">
						<?php echo wp_kses_post( wpautop( $item['answer'] ) ); ?>
					</div>
				</details>
			<?php endforeach; ?>
		</div>
		<?php

		$html = (string) ob_get_clean();

		if ( $add_schema ) {
			$html .= self::faq_schema( $items );
		}

		return $html;
	}

	/**
	 * Coerce raw FAQ attributes into clean question/answer pairs.
	 *
	 * @param mixed $items Raw attribute value.
	 * @return array<int, array{question: string, answer: string}>
	 */
	private static function normalize_faq_items( $items ) {
		if ( ! is_array( $items ) ) {
			return array();
		}

		$normalized = array();

		foreach ( $items as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}

			$question = isset( $item['question'] ) && is_scalar( $item['question'] )
				? sanitize_text_field( (string) $item['question'] )
				: '';
			$answer   = isset( $item['answer'] ) && is_scalar( $item['answer'] )
				? wp_kses_post( (string) $item['answer'] )
				: '';

			if ( '' === $question ) {
				continue;
			}

			$normalized[] = array(
				'question' => $question,
				'answer'   => $answer,
			);
		}

		return $normalized;
	}

	/**
	 * Build the FAQPage JSON-LD payload for a set of questions.
	 *
	 * @param array<int, array{question: string, answer: string}> $items Clean pairs.
	 * @return string
	 */
	private static function faq_schema( $items ) {
		$entities = array();

		foreach ( $items as $item ) {
			$answer = trim( wp_strip_all_tags( $item['answer'] ) );

			// Search engines reject FAQ entries without an answer body.
			if ( '' === $answer ) {
				continue;
			}

			$entities[] = array(
				'@type'          => 'Question',
				'name'           => $item['question'],
				'acceptedAnswer' => array(
					'@type' => 'Answer',
					'text'  => $answer,
				),
			);
		}

		if ( empty( $entities ) ) {
			return '';
		}

		$schema = array(
			'@context'   => 'https://schema.org',
			'@type'      => 'FAQPage',
			'mainEntity' => $entities,
		);

		return '<script type="application/ld+json">' . wp_json_encode( $schema ) . '</script>';
	}

	// -------------------------------------------------------------------------
	// Glossary
	// -------------------------------------------------------------------------

	/**
	 * Render the glossary as a definition list.
	 *
	 * @param string $heading      Optional heading above the list.
	 * @param bool   $show_aliases Whether to list alternative spellings.
	 * @return string
	 */
	public static function glossary( $heading = '', $show_aliases = true ) {
		$entries = array_filter(
			Glossary_Taxonomy::get_entries(),
			static function ( $entry ) {
				return '' !== trim( (string) $entry['definition'] );
			}
		);

		if ( empty( $entries ) ) {
			return self::notice( __( 'No glossary terms have been added yet.', 'itsmanzur-docs' ) );
		}

		ob_start();
		?>
		<div class="itsdz-glossary-block">
			<?php if ( '' !== $heading ) : ?>
				<h2 class="itsdz-glossary-heading"><?php echo esc_html( $heading ); ?></h2>
			<?php endif; ?>
			<dl class="itsdz-glossary-list">
				<?php foreach ( $entries as $entry ) : ?>
					<div class="itsdz-glossary-entry">
						<dt><?php echo esc_html( $entry['term'] ); ?></dt>
						<dd>
							<?php echo esc_html( $entry['definition'] ); ?>
							<?php if ( $show_aliases && ! empty( $entry['aliases'] ) ) : ?>
								<span class="itsdz-glossary-aliases">
									<?php
									printf(
										/* translators: %s: comma-separated list of alternative spellings. */
										esc_html__( 'Also: %s', 'itsmanzur-docs' ),
										esc_html( implode( ', ', $entry['aliases'] ) )
									);
									?>
								</span>
							<?php endif; ?>
						</dd>
					</div>
				<?php endforeach; ?>
			</dl>
		</div>
		<?php

		return (string) ob_get_clean();
	}

	// -------------------------------------------------------------------------
	// Popular / recent articles
	// -------------------------------------------------------------------------

	/**
	 * Render a ranked list of articles from a knowledge base.
	 *
	 * @param int    $kb_id      Knowledge base post ID.
	 * @param int    $limit      Maximum articles to show.
	 * @param string $order      Either `popular` (most viewed) or `recent`.
	 * @param bool   $show_views Whether to print the view count next to each item.
	 * @return string
	 */
	public static function article_ranking( $kb_id, $limit = 5, $order = 'popular', $show_views = true ) {
		$kb_id = absint( $kb_id );
		$limit = max( 1, min( 50, absint( $limit ) ) );
		$order = 'recent' === $order ? 'recent' : 'popular';

		if ( ! $kb_id ) {
			return self::notice( __( 'Nirdeshio: select a knowledge base first.', 'itsmanzur-docs' ) );
		}

		$kb = get_post( $kb_id );

		if (
			! $kb instanceof \WP_Post ||
			KB_Post_Type::POST_TYPE !== $kb->post_type ||
			'publish' !== $kb->post_status
		) {
			return self::notice( __( 'Nirdeshio: knowledge base not found or not published.', 'itsmanzur-docs' ) );
		}

		$articles = Documentation::get_articles( $kb_id );

		if ( empty( $articles ) ) {
			return self::notice( __( 'No published articles found.', 'itsmanzur-docs' ) );
		}

		$totals = View_Tracker::get_kb_totals( $kb_id );

		if ( 'recent' === $order ) {
			usort(
				$articles,
				static function ( $a, $b ) {
					return strcmp( $b->post_date_gmt, $a->post_date_gmt );
				}
			);
		} else {
			usort(
				$articles,
				static function ( $a, $b ) use ( $totals ) {
					$a_views = isset( $totals[ $a->ID ] ) ? (int) $totals[ $a->ID ] : 0;
					$b_views = isset( $totals[ $b->ID ] ) ? (int) $totals[ $b->ID ] : 0;

					// Fall back to newest-first so ties stay deterministic.
					if ( $a_views === $b_views ) {
						return strcmp( $b->post_date_gmt, $a->post_date_gmt );
					}

					return $b_views <=> $a_views;
				}
			);
		}

		$articles = array_slice( $articles, 0, $limit );

		ob_start();
		?>
		<div class="itsdz-ranking-block" data-order="<?php echo esc_attr( $order ); ?>">
			<ul class="itsdz-ranking-list">
				<?php foreach ( $articles as $article ) : ?>
					<li class="itsdz-ranking-item">
						<a href="<?php echo esc_url( (string) get_permalink( $article ) ); ?>">
							<?php echo esc_html( get_the_title( $article ) ); ?>
						</a>
						<?php if ( $show_views && 'popular' === $order ) : ?>
							<?php $views = isset( $totals[ $article->ID ] ) ? (int) $totals[ $article->ID ] : 0; ?>
							<span class="itsdz-ranking-views">
								<?php
								printf(
									/* translators: %s: formatted number of article views. */
									esc_html( _n( '%s view', '%s views', $views, 'itsmanzur-docs' ) ),
									esc_html( number_format_i18n( $views ) )
								);
								?>
							</span>
						<?php endif; ?>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
		<?php

		return (string) ob_get_clean();
	}
}
