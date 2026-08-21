<?php
/**
 * Section archive template.
 *
 * @package ItsDZ\Doczur
 */

use ItsDZ\Doczur\Frontend\Documentation;

defined( 'ABSPATH' ) || exit;

$itsdz_term = get_queried_object();
$itsdz_kb   = Documentation::get_published_kb();

if ( ! $itsdz_term instanceof WP_Term || ! $itsdz_kb instanceof WP_Post ) {
	return;
}

$itsdz_groups     = Documentation::get_groups( $itsdz_kb->ID );
$itsdz_current_id = 0;
$itsdz_articles   = Documentation::get_section_articles( $itsdz_kb->ID, $itsdz_term->term_id );

require ITSDZ_PLUGIN_DIR . 'templates/partials/shell-start.php';
?>
<div class="itsdz-article-layout">
	<?php require ITSDZ_PLUGIN_DIR . 'templates/partials/navigation.php'; ?>

	<main class="itsdz-article-main" id="itsdz-main">
		<nav class="itsdz-breadcrumbs" aria-label="<?php esc_attr_e( 'Breadcrumb', 'itsmanzur-docs' ); ?>">
			<div class="itsdz-breadcrumb-trail">
				<a href="<?php echo esc_url( get_permalink( $itsdz_kb ) ); ?>"><?php echo esc_html( get_the_title( $itsdz_kb ) ); ?></a>
				<span aria-hidden="true">/</span>
				<span aria-current="page"><?php echo esc_html( $itsdz_term->name ); ?></span>
			</div>
		</nav>

		<article class="itsdz-article">
			<header class="itsdz-article-header">
				<h1><?php echo esc_html( $itsdz_term->name ); ?></h1>
				<?php if ( $itsdz_term->description ) : ?>
					<p class="itsdz-hero-copy"><?php echo esc_html( $itsdz_term->description ); ?></p>
				<?php endif; ?>
			</header>

			<?php if ( $itsdz_articles ) : ?>
				<ul class="itsdz-section-article-list">
					<?php foreach ( $itsdz_articles as $itsdz_section_article ) : ?>
						<li>
							<a href="<?php echo esc_url( get_permalink( $itsdz_section_article ) ); ?>">
								<strong><?php echo esc_html( get_the_title( $itsdz_section_article ) ); ?></strong>
								<span><?php esc_html_e( 'Read article', 'itsmanzur-docs' ); ?> →</span>
							</a>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php else : ?>
				<div class="itsdz-empty-state">
					<h2><?php esc_html_e( 'No published articles in this section yet.', 'itsmanzur-docs' ); ?></h2>
				</div>
			<?php endif; ?>
		</article>
	</main>
</div>
<?php require ITSDZ_PLUGIN_DIR . 'templates/partials/shell-end.php'; ?>
