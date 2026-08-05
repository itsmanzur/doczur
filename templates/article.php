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

require ITSDZ_PLUGIN_DIR . 'templates/partials/shell-start.php';
?>
<div class="itsdz-article-layout">
	<?php require ITSDZ_PLUGIN_DIR . 'templates/partials/navigation.php'; ?>

	<main class="itsdz-article-main" id="itsdz-main">
		<nav class="itsdz-breadcrumbs" aria-label="<?php esc_attr_e( 'Breadcrumb', 'doczur' ); ?>">
			<a href="<?php echo esc_url( get_permalink( $itsdz_kb ) ); ?>"><?php echo esc_html( get_the_title( $itsdz_kb ) ); ?></a>
			<span aria-hidden="true">/</span>
			<?php if ( $itsdz_section instanceof WP_Term ) : ?>
				<span><?php echo esc_html( $itsdz_section->name ); ?></span>
				<span aria-hidden="true">/</span>
			<?php endif; ?>
			<span aria-current="page"><?php echo esc_html( get_the_title( $itsdz_article ) ); ?></span>
		</nav>

		<article class="itsdz-article">
			<header class="itsdz-article-header">
				<h1><?php echo esc_html( get_the_title( $itsdz_article ) ); ?></h1>
				<div class="itsdz-article-meta">
					<span>
						<?php
						printf(
							/* translators: %s: article modified date. */
							esc_html__( 'Updated %s', 'doczur' ),
							esc_html( get_the_modified_date( get_option( 'date_format' ), $itsdz_article ) )
						);
						?>
					</span>
					<span aria-hidden="true">•</span>
					<span>
						<?php
						printf(
							/* translators: %d: estimated reading time in minutes. */
							esc_html( _n( '%d minute read', '%d minutes read', $itsdz_reading, 'doczur' ) ),
							esc_html( (string) $itsdz_reading )
						);
						?>
					</span>
					<button type="button" class="itsdz-copy-link" data-itsdz-copy-link><?php esc_html_e( 'Copy link', 'doczur' ); ?></button>
				</div>
			</header>

			<div class="itsdz-article-content" data-itsdz-content>
				<?php
				setup_postdata( $itsdz_article );
				the_content();
				wp_reset_postdata();
				?>
			</div>

			<nav class="itsdz-adjacent" aria-label="<?php esc_attr_e( 'Previous and next articles', 'doczur' ); ?>">
				<?php if ( $itsdz_adjacent['previous'] ) : ?>
					<a rel="prev" href="<?php echo esc_url( get_permalink( $itsdz_adjacent['previous'] ) ); ?>">
						<span><?php esc_html_e( 'Previous', 'doczur' ); ?></span>
						<strong>← <?php echo esc_html( get_the_title( $itsdz_adjacent['previous'] ) ); ?></strong>
					</a>
				<?php endif; ?>
				<?php if ( $itsdz_adjacent['next'] ) : ?>
					<a rel="next" href="<?php echo esc_url( get_permalink( $itsdz_adjacent['next'] ) ); ?>">
						<span><?php esc_html_e( 'Next', 'doczur' ); ?></span>
						<strong><?php echo esc_html( get_the_title( $itsdz_adjacent['next'] ) ); ?> →</strong>
					</a>
				<?php endif; ?>
			</nav>

			<section class="itsdz-feedback" data-itsdz-feedback data-article-id="<?php echo esc_attr( (string) $itsdz_article->ID ); ?>">
				<div>
					<strong><?php esc_html_e( 'Was this article helpful?', 'doczur' ); ?></strong>
					<p><?php esc_html_e( 'Your feedback helps improve this documentation.', 'doczur' ); ?></p>
				</div>
				<div class="itsdz-feedback-actions">
					<button type="button" data-helpful="true"><?php esc_html_e( 'Yes', 'doczur' ); ?></button>
					<button type="button" data-helpful="false"><?php esc_html_e( 'Not yet', 'doczur' ); ?></button>
				</div>
				<p class="itsdz-feedback-status" role="status" aria-live="polite" data-itsdz-feedback-status></p>
			</section>

			<div class="itsdz-article-share" aria-label="<?php esc_attr_e( 'Share article', 'doczur' ); ?>">
				<span><?php esc_html_e( 'Share article:', 'doczur' ); ?></span>
				<a href="https://twitter.com/intent/tweet?text=<?php echo rawurlencode( get_the_title( $itsdz_article ) ); ?>&amp;url=<?php echo rawurlencode( get_permalink( $itsdz_article ) ); ?>" target="_blank" rel="noopener noreferrer" aria-label="<?php esc_attr_e( 'Share on Twitter or X', 'doczur' ); ?>">X / Twitter</a>
				<a href="https://www.linkedin.com/sharing/share-offsite/?url=<?php echo rawurlencode( get_permalink( $itsdz_article ) ); ?>" target="_blank" rel="noopener noreferrer" aria-label="<?php esc_attr_e( 'Share on LinkedIn', 'doczur' ); ?>">LinkedIn</a>
				<a href="mailto:?subject=<?php echo rawurlencode( get_the_title( $itsdz_article ) ); ?>&amp;body=<?php echo rawurlencode( get_permalink( $itsdz_article ) ); ?>" aria-label="<?php esc_attr_e( 'Share via Email', 'doczur' ); ?>">Email</a>
			</div>

			<?php if ( $itsdz_related ) : ?>
				<section class="itsdz-related" aria-labelledby="itsdz-related-title">
					<h2 id="itsdz-related-title"><?php esc_html_e( 'Related articles', 'doczur' ); ?></h2>
					<div>
						<?php foreach ( $itsdz_related as $itsdz_related_article ) : ?>
							<a href="<?php echo esc_url( get_permalink( $itsdz_related_article ) ); ?>">
								<strong><?php echo esc_html( get_the_title( $itsdz_related_article ) ); ?></strong>
								<span><?php esc_html_e( 'Read article', 'doczur' ); ?> →</span>
							</a>
						<?php endforeach; ?>
					</div>
				</section>
			<?php endif; ?>
		</article>
	</main>

	<aside class="itsdz-toc" aria-label="<?php esc_attr_e( 'On this page', 'doczur' ); ?>">
		<strong><?php esc_html_e( 'On this page', 'doczur' ); ?></strong>
		<nav data-itsdz-toc></nav>
	</aside>
</div>
<?php require ITSDZ_PLUGIN_DIR . 'templates/partials/shell-end.php'; ?>
