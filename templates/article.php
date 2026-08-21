<?php
/**
 * Single documentation article template.
 *
 * @package ItsDZ\Doczur
 */

use ItsDZ\Doczur\Frontend\Documentation;

defined( 'ABSPATH' ) || exit;

$itsdz_article    = get_queried_object();
$itsdz_kb         = Documentation::get_article_kb( $itsdz_article->ID );
$itsdz_groups     = Documentation::get_groups( $itsdz_kb->ID );
$itsdz_current_id = $itsdz_article->ID;
$itsdz_adjacent   = Documentation::get_adjacent( $itsdz_kb->ID, $itsdz_article->ID );
$itsdz_related    = Documentation::get_related( $itsdz_kb->ID, $itsdz_article->ID );
$itsdz_sections   = get_the_terms( $itsdz_article, 'itsdz_section' );
$itsdz_section    = is_array( $itsdz_sections ) ? reset( $itsdz_sections ) : null;
$itsdz_reading    = max( 1, absint( get_post_meta( $itsdz_article->ID, '_itsdz_reading_time', true ) ) );
$itsdz_author     = get_the_author_meta( 'display_name', (int) $itsdz_article->post_author );
$itsdz_reviewed   = (string) get_post_meta( $itsdz_article->ID, '_itsdz_last_reviewed', true );

/**
 * Filter whether the article byline (author name and avatar) is displayed.
 *
 * @param bool     $show    Whether to show the byline.
 * @param \WP_Post $article Current article.
 */
$itsdz_show_author = (bool) apply_filters( 'itsdz_show_article_author', true, $itsdz_article );

$itsdz_show_feedback = Documentation::ui_flag( $itsdz_kb->ID, '_itsdz_kb_show_feedback' );
$itsdz_show_related  = Documentation::ui_flag( $itsdz_kb->ID, '_itsdz_kb_show_related' );
$itsdz_show_print    = Documentation::ui_flag( $itsdz_kb->ID, '_itsdz_kb_show_print' );
$itsdz_show_toc      = Documentation::ui_flag( $itsdz_kb->ID, '_itsdz_kb_show_toc' );
$itsdz_header_links  = Documentation::header_links( $itsdz_kb->ID );

require ITSDZ_PLUGIN_DIR . 'templates/partials/shell-start.php';
?>
<div class="itsdz-article-layout">
	<?php require ITSDZ_PLUGIN_DIR . 'templates/partials/navigation.php'; ?>

	<main class="itsdz-article-main" id="itsdz-main">
		<nav class="itsdz-breadcrumbs" aria-label="<?php esc_attr_e( 'Breadcrumb', 'itsmanzur-docs' ); ?>">
			<div class="itsdz-breadcrumb-trail">
				<a href="<?php echo esc_url( get_permalink( $itsdz_kb ) ); ?>"><?php echo esc_html( get_the_title( $itsdz_kb ) ); ?></a>
				<span aria-hidden="true">/</span>
				<?php if ( $itsdz_section instanceof WP_Term ) : ?>
					<?php
					$itsdz_section_url = get_term_link( $itsdz_section );
					if ( ! is_wp_error( $itsdz_section_url ) ) :
						?>
						<a href="<?php echo esc_url( $itsdz_section_url ); ?>"><?php echo esc_html( $itsdz_section->name ); ?></a>
					<?php else : ?>
						<span><?php echo esc_html( $itsdz_section->name ); ?></span>
					<?php endif; ?>
					<span aria-hidden="true">/</span>
				<?php endif; ?>
				<span aria-current="page"><?php echo esc_html( get_the_title( $itsdz_article ) ); ?></span>
			</div>
			<?php if ( 'theme' === $itsdz_layout_mode ) : ?>
				<div class="itsdz-breadcrumb-actions">
					<?php require ITSDZ_PLUGIN_DIR . 'templates/partials/header-links.php'; ?>
					<button class="itsdz-icon-button itsdz-mobile-nav-button" type="button" data-itsdz-nav-toggle aria-controls="itsdz-sidebar" aria-expanded="false">
						<span class="itsdz-icon-menu" aria-hidden="true"></span>
						<span class="screen-reader-text"><?php esc_html_e( 'Open documentation navigation', 'itsmanzur-docs' ); ?></span>
					</button>
					<button class="itsdz-icon-button" type="button" data-itsdz-search-open aria-label="<?php esc_attr_e( 'Search documentation', 'itsmanzur-docs' ); ?>">
						<span class="itsdz-icon-search" aria-hidden="true"></span>
					</button>
					<button class="itsdz-icon-button" type="button" data-itsdz-theme-toggle aria-label="<?php esc_attr_e( 'Toggle color mode', 'itsmanzur-docs' ); ?>">
						<span class="itsdz-icon-contrast" aria-hidden="true"></span>
					</button>
				</div>
			<?php endif; ?>
		</nav>

		<article class="itsdz-article">
			<header class="itsdz-article-header">
				<h1><?php echo esc_html( get_the_title( $itsdz_article ) ); ?></h1>
				<div class="itsdz-article-meta">
					<?php if ( $itsdz_show_author && $itsdz_author ) : ?>
						<span class="itsdz-article-byline">
							<?php echo get_avatar( (int) $itsdz_article->post_author, 20, '', $itsdz_author, array( 'class' => 'itsdz-article-avatar' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_avatar() returns escaped markup. ?>
							<span><?php echo esc_html( $itsdz_author ); ?></span>
						</span>
						<span aria-hidden="true">•</span>
					<?php endif; ?>
					<span>
						<?php
						printf(
							/* translators: %s: article modified date. */
							esc_html__( 'Updated %s', 'itsmanzur-docs' ),
							esc_html( get_the_modified_date( get_option( 'date_format' ), $itsdz_article ) )
						);
						?>
					</span>
					<?php if ( $itsdz_reviewed ) : ?>
						<span aria-hidden="true">•</span>
						<span class="itsdz-article-reviewed">
							<?php
							printf(
								/* translators: %s: date the article was last reviewed for accuracy. */
								esc_html__( 'Reviewed %s', 'itsmanzur-docs' ),
								esc_html( wp_date( (string) get_option( 'date_format' ), (int) strtotime( $itsdz_reviewed . ' UTC' ) ) )
							);
							?>
						</span>
					<?php endif; ?>
					<span aria-hidden="true">•</span>
					<span>
						<?php
						printf(
							/* translators: %d: estimated reading time in minutes. */
							esc_html( _n( '%d minute read', '%d minutes read', $itsdz_reading, 'itsmanzur-docs' ) ),
							esc_html( (string) $itsdz_reading )
						);
						?>
					</span>
					<button type="button" class="itsdz-copy-link" data-itsdz-copy-link><?php esc_html_e( 'Copy link', 'itsmanzur-docs' ); ?></button>
					<?php if ( $itsdz_show_print ) : ?>
						<button type="button" class="itsdz-copy-link" data-itsdz-print><?php esc_html_e( 'Print', 'itsmanzur-docs' ); ?></button>
					<?php endif; ?>
					<button
						type="button"
						class="itsdz-copy-link"
						data-itsdz-copy-markdown
						data-itsdz-title="<?php echo esc_attr( get_the_title( $itsdz_article ) ); ?>"
						title="<?php esc_attr_e( 'Copy this article as Markdown, ready to paste into an AI assistant', 'itsmanzur-docs' ); ?>"
					><?php esc_html_e( 'Copy as Markdown', 'itsmanzur-docs' ); ?></button>
				</div>
			</header>

			<div class="itsdz-article-content" data-itsdz-content>
				<?php
				setup_postdata( $itsdz_article );
				the_content();
				wp_reset_postdata();
				?>
			</div>

			<nav class="itsdz-adjacent" aria-label="<?php esc_attr_e( 'Previous and next articles', 'itsmanzur-docs' ); ?>">
				<?php if ( $itsdz_adjacent['previous'] ) : ?>
					<a rel="prev" href="<?php echo esc_url( get_permalink( $itsdz_adjacent['previous'] ) ); ?>">
						<span><?php esc_html_e( 'Previous', 'itsmanzur-docs' ); ?></span>
						<strong>← <?php echo esc_html( get_the_title( $itsdz_adjacent['previous'] ) ); ?></strong>
					</a>
				<?php endif; ?>
				<?php if ( $itsdz_adjacent['next'] ) : ?>
					<a rel="next" href="<?php echo esc_url( get_permalink( $itsdz_adjacent['next'] ) ); ?>">
						<span><?php esc_html_e( 'Next', 'itsmanzur-docs' ); ?></span>
						<strong><?php echo esc_html( get_the_title( $itsdz_adjacent['next'] ) ); ?> →</strong>
					</a>
				<?php endif; ?>
			</nav>

			<?php if ( $itsdz_show_feedback ) : ?>
			<section class="itsdz-feedback" data-itsdz-feedback data-article-id="<?php echo esc_attr( (string) $itsdz_article->ID ); ?>">
				<div>
					<strong><?php esc_html_e( 'Was this article helpful?', 'itsmanzur-docs' ); ?></strong>
					<p><?php esc_html_e( 'Your feedback helps improve this documentation.', 'itsmanzur-docs' ); ?></p>
				</div>
				<div class="itsdz-feedback-actions">
					<button type="button" data-helpful="true"><?php esc_html_e( 'Yes', 'itsmanzur-docs' ); ?></button>
					<button type="button" data-helpful="false"><?php esc_html_e( 'Not yet', 'itsmanzur-docs' ); ?></button>
				</div>
				<p class="itsdz-feedback-status" role="status" aria-live="polite" data-itsdz-feedback-status></p>
			</section>
			<?php endif; ?>

			<div class="itsdz-article-share" aria-label="<?php esc_attr_e( 'Share article', 'itsmanzur-docs' ); ?>">
				<span><?php esc_html_e( 'Share article:', 'itsmanzur-docs' ); ?></span>
				<a href="https://twitter.com/intent/tweet?text=<?php echo rawurlencode( get_the_title( $itsdz_article ) ); ?>&amp;url=<?php echo rawurlencode( get_permalink( $itsdz_article ) ); ?>" target="_blank" rel="noopener noreferrer" aria-label="<?php esc_attr_e( 'Share on Twitter or X', 'itsmanzur-docs' ); ?>">X / Twitter</a>
				<a href="https://www.linkedin.com/sharing/share-offsite/?url=<?php echo rawurlencode( get_permalink( $itsdz_article ) ); ?>" target="_blank" rel="noopener noreferrer" aria-label="<?php esc_attr_e( 'Share on LinkedIn', 'itsmanzur-docs' ); ?>">LinkedIn</a>
				<a href="mailto:?subject=<?php echo rawurlencode( get_the_title( $itsdz_article ) ); ?>&amp;body=<?php echo rawurlencode( get_permalink( $itsdz_article ) ); ?>" aria-label="<?php esc_attr_e( 'Share via Email', 'itsmanzur-docs' ); ?>">Email</a>
			</div>

			<?php if ( $itsdz_show_related && $itsdz_related ) : ?>
				<section class="itsdz-related" aria-labelledby="itsdz-related-title">
					<h2 id="itsdz-related-title"><?php esc_html_e( 'Related articles', 'itsmanzur-docs' ); ?></h2>
					<div>
						<?php foreach ( $itsdz_related as $itsdz_related_article ) : ?>
							<a href="<?php echo esc_url( get_permalink( $itsdz_related_article ) ); ?>">
								<strong><?php echo esc_html( get_the_title( $itsdz_related_article ) ); ?></strong>
								<span><?php esc_html_e( 'Read article', 'itsmanzur-docs' ); ?> →</span>
							</a>
						<?php endforeach; ?>
					</div>
				</section>
			<?php endif; ?>
		</article>
	</main>

	<?php if ( $itsdz_show_toc ) : ?>
	<aside class="itsdz-toc" aria-label="<?php esc_attr_e( 'On this page', 'itsmanzur-docs' ); ?>">
		<strong><?php esc_html_e( 'On this page', 'itsmanzur-docs' ); ?></strong>
		<nav data-itsdz-toc></nav>
	</aside>
	<?php endif; ?>
</div>
<?php require ITSDZ_PLUGIN_DIR . 'templates/partials/shell-end.php'; ?>
