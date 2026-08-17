<?php
/**
 * Documentation article navigation.
 *
 * @package ItsDZ\Doczur
 *
 * @var array<int, array{term: WP_Term|null, articles: WP_Post[]}> $itsdz_groups Article groups.
 * @var int $itsdz_current_id Current article ID.
 * @var string $itsdz_nav_style Public nav skin.
 * @var int $itsdz_kb_id Documentation project ID.
 */

use ItsDZ\Doczur\Frontend\Documentation;

defined( 'ABSPATH' ) || exit;

$itsdz_nav_style = isset( $itsdz_nav_style ) ? $itsdz_nav_style : 'accordion';
?>
<aside class="itsdz-sidebar" id="itsdz-sidebar" data-itsdz-sidebar>
	<div class="itsdz-sidebar-heading">
		<strong><?php esc_html_e( 'Documentation', 'doczur' ); ?></strong>
		<button type="button" data-itsdz-nav-close aria-label="<?php esc_attr_e( 'Close documentation navigation', 'doczur' ); ?>">×</button>
	</div>
	<nav aria-label="<?php esc_attr_e( 'Article navigation', 'doczur' ); ?>">
		<?php if ( 'tree' === $itsdz_nav_style ) : ?>
			<?php
			$itsdz_nav_nodes = Documentation::get_nav_tree( $itsdz_kb_id );
			require __DIR__ . '/nav-nodes.php';
			?>
		<?php else : ?>
			<?php foreach ( $itsdz_groups as $itsdz_group ) : ?>
				<?php
				$itsdz_section_name     = $itsdz_group['term'] ? $itsdz_group['term']->name : __( 'More articles', 'doczur' );
				$itsdz_contains_current = in_array( $itsdz_current_id, wp_list_pluck( $itsdz_group['articles'], 'ID' ), true );
				$itsdz_section_link     = $itsdz_group['term'] ? get_term_link( $itsdz_group['term'] ) : '';
				?>
				<details <?php echo $itsdz_contains_current ? 'open' : ''; ?>>
					<summary><?php echo esc_html( $itsdz_section_name ); ?><span><?php echo esc_html( (string) count( $itsdz_group['articles'] ) ); ?></span></summary>
					<?php if ( $itsdz_group['term'] && ! is_wp_error( $itsdz_section_link ) ) : ?>
						<a class="itsdz-nav-section-link" href="<?php echo esc_url( $itsdz_section_link ); ?>">
							<?php esc_html_e( 'View section', 'doczur' ); ?>
						</a>
					<?php endif; ?>
					<ul>
						<?php foreach ( $itsdz_group['articles'] as $itsdz_nav_article ) : ?>
							<li>
								<a href="<?php echo esc_url( get_permalink( $itsdz_nav_article ) ); ?>" <?php echo $itsdz_nav_article->ID === $itsdz_current_id ? 'aria-current="page"' : ''; ?>>
									<?php echo esc_html( get_the_title( $itsdz_nav_article ) ); ?>
								</a>
							</li>
						<?php endforeach; ?>
					</ul>
				</details>
			<?php endforeach; ?>
		<?php endif; ?>
	</nav>
</aside>
