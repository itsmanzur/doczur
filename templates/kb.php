<?php
/**
 * Documentation project landing template.
 *
 * @package ItsDZ\\Doczur
 */

use ItsDZ\Doczur\Frontend\Documentation;

defined( 'ABSPATH' ) || exit;

$itsdz_kb         = get_queried_object();
$itsdz_groups     = Documentation::get_groups( $itsdz_kb->ID );
$itsdz_current_id = 0; // No active article on the landing page.

require ITSDZ_PLUGIN_DIR . 'templates/partials/shell-start.php';
?>
<main class="itsdz-landing" id="itsdz-main">
	<section class="itsdz-hero">
		<div class="itsdz-hero-inner">
			<p class="itsdz-eyebrow"><?php esc_html_e( 'Knowledge base', 'doczur' ); ?></p>
			<h1><?php echo esc_html( get_the_title( $itsdz_kb ) ); ?></h1>
			<?php if ( $itsdz_kb->post_content ) : ?>
				<div class="itsdz-hero-copy"><?php echo wp_kses_post( wpautop( $itsdz_kb->post_content ) ); ?></div>
			<?php else : ?>
				<p class="itsdz-hero-copy"><?php esc_html_e( 'Find answers, setup guidance, and detailed product information.', 'doczur' ); ?></p>
			<?php endif; ?>
			<button class="itsdz-hero-search" type="button" data-itsdz-search-open>
				<span class="itsdz-icon-search itsdz-icon-lg" aria-hidden="true"></span>
				<span><?php esc_html_e( 'Search for an answer…', 'doczur' ); ?></span>
				<kbd>Ctrl K</kbd>
			</button>
		</div>
	</section>

	<section class="itsdz-section-grid" aria-labelledby="itsdz-sections-title">
		<div class="itsdz-section-grid-heading">
			<div>
				<p class="itsdz-eyebrow"><?php esc_html_e( 'Browse documentation', 'doczur' ); ?></p>
				<h2 id="itsdz-sections-title"><?php esc_html_e( 'Explore by section', 'doczur' ); ?></h2>
			</div>
			<span>
				<?php
				$itsdz_total = count( Documentation::get_articles( $itsdz_kb->ID ) );
				printf(
					/* translators: %d: published article count. */
					esc_html( _n( '%d published article', '%d published articles', $itsdz_total, 'doczur' ) ),
					absint( $itsdz_total )
				);
				?>
			</span>
		</div>

		<?php if ( $itsdz_groups ) : ?>
			<div class="itsdz-cards">
				<?php foreach ( $itsdz_groups as $itsdz_group ) : ?>
					<?php
					$itsdz_group_name = $itsdz_group['term'] ? $itsdz_group['term']->name : __( 'More articles', 'doczur' );
					$itsdz_icon       = $itsdz_group['term'] ? get_term_meta( $itsdz_group['term']->term_id, '_itsdz_section_icon', true ) : '';
					?>
					<article class="itsdz-section-card">
						<?php
						$itsdz_section_url = $itsdz_group['term'] ? get_term_link( $itsdz_group['term'] ) : '';
						$itsdz_section_url = is_wp_error( $itsdz_section_url ) ? '' : $itsdz_section_url;
						?>
						<div class="itsdz-section-card-icon" aria-hidden="true">
							<?php if ( $itsdz_icon ) : ?>
								<span class="dashicons <?php echo esc_attr( (string) $itsdz_icon ); ?>"></span>
							<?php else : ?>
								<?php echo esc_html( mb_strtoupper( mb_substr( $itsdz_group_name, 0, 1 ) ) ); ?>
							<?php endif; ?>
						</div>
						<?php if ( $itsdz_section_url ) : ?>
							<h3><a href="<?php echo esc_url( $itsdz_section_url ); ?>"><?php echo esc_html( $itsdz_group_name ); ?></a></h3>
						<?php else : ?>
							<h3><?php echo esc_html( $itsdz_group_name ); ?></h3>
						<?php endif; ?>
						<?php if ( $itsdz_group['term'] && $itsdz_group['term']->description ) : ?>
							<p><?php echo esc_html( $itsdz_group['term']->description ); ?></p>
						<?php endif; ?>
						<ul>
							<?php foreach ( array_slice( $itsdz_group['articles'], 0, 5 ) as $itsdz_article ) : ?>
								<li><a href="<?php echo esc_url( get_permalink( $itsdz_article ) ); ?>"><?php echo esc_html( get_the_title( $itsdz_article ) ); ?></a></li>
							<?php endforeach; ?>
						</ul>
						<?php if ( count( $itsdz_group['articles'] ) > 5 ) : ?>
							<p class="itsdz-card-more">
								<?php
								printf(
									/* translators: %d: number of additional articles. */
									esc_html__( '+ %d more articles', 'doczur' ),
									absint( count( $itsdz_group['articles'] ) - 5 )
								);
								?>
							</p>
						<?php endif; ?>
					</article>
				<?php endforeach; ?>
			</div>
		<?php else : ?>
			<div class="itsdz-empty-state">
				<h2><?php esc_html_e( 'Documentation is being prepared', 'doczur' ); ?></h2>
				<p><?php esc_html_e( 'Published articles will appear here.', 'doczur' ); ?></p>
			</div>
		<?php endif; ?>
	</section>
</main>
<?php require ITSDZ_PLUGIN_DIR . 'templates/partials/shell-end.php'; ?>
