<?php
/**
 * Documentation article navigation.
 *
 * @package ItsDZ\Doczur
 *
 * @var array<int, array{term: WP_Term|null, articles: WP_Post[]}> $itsdz_groups Article groups.
 * @var int                                                          $itsdz_current_id Current article ID.
 */

defined( 'ABSPATH' ) || exit;
?>
<aside class="itsdz-sidebar" id="itsdz-sidebar" data-itsdz-sidebar>
	<div class="itsdz-sidebar-heading">
		<strong><?php esc_html_e( 'Documentation', 'doczur' ); ?></strong>
		<button type="button" data-itsdz-nav-close aria-label="<?php esc_attr_e( 'Close documentation navigation', 'doczur' ); ?>">×</button>
	</div>
	<nav aria-label="<?php esc_attr_e( 'Article navigation', 'doczur' ); ?>">
		<?php foreach ( $itsdz_groups as $itsdz_group ) : ?>
			<?php
			$itsdz_section_name     = $itsdz_group['term'] ? $itsdz_group['term']->name : __( 'More articles', 'doczur' );
			$itsdz_contains_current = in_array( $itsdz_current_id, wp_list_pluck( $itsdz_group['articles'], 'ID' ), true );
			?>
			<details <?php echo $itsdz_contains_current ? 'open' : ''; ?>>
				<summary><?php echo esc_html( $itsdz_section_name ); ?><span><?php echo esc_html( (string) count( $itsdz_group['articles'] ) ); ?></span></summary>
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
	</nav>
</aside>
