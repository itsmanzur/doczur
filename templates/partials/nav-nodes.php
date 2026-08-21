<?php
/**
 * Nested public navigation nodes.
 *
 * @package ItsDZ\Doczur
 *
 * @var array<int, array{term: WP_Term|null, articles: WP_Post[], children: array}> $itsdz_nav_nodes Nav nodes.
 * @var int $itsdz_current_id Current article ID.
 */

use ItsDZ\Doczur\Frontend\Documentation;

defined( 'ABSPATH' ) || exit;

$itsdz_nav_level = $itsdz_nav_nodes;

foreach ( $itsdz_nav_level as $itsdz_node ) {
	$itsdz_section_name     = $itsdz_node['term'] ? $itsdz_node['term']->name : __( 'More articles', 'itsmanzur-docs' );
	$itsdz_article_count    = count( $itsdz_node['articles'] );
	$itsdz_contains_current = Documentation::nav_contains_article( $itsdz_node, $itsdz_current_id );
	$itsdz_section_link     = $itsdz_node['term'] ? get_term_link( $itsdz_node['term'] ) : '';
	?>
	<details <?php echo $itsdz_contains_current ? 'open' : ''; ?>>
		<summary>
			<?php echo esc_html( $itsdz_section_name ); ?>
			<span><?php echo esc_html( (string) $itsdz_article_count ); ?></span>
		</summary>
		<?php if ( $itsdz_node['term'] && ! is_wp_error( $itsdz_section_link ) ) : ?>
			<a class="itsdz-nav-section-link" href="<?php echo esc_url( $itsdz_section_link ); ?>">
				<?php esc_html_e( 'View section', 'itsmanzur-docs' ); ?>
			</a>
		<?php endif; ?>
		<?php if ( $itsdz_node['articles'] ) : ?>
			<ul>
				<?php foreach ( $itsdz_node['articles'] as $itsdz_nav_article ) : ?>
					<li>
						<a href="<?php echo esc_url( get_permalink( $itsdz_nav_article ) ); ?>" <?php echo (int) $itsdz_nav_article->ID === (int) $itsdz_current_id ? 'aria-current="page"' : ''; ?>>
							<?php echo esc_html( get_the_title( $itsdz_nav_article ) ); ?>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
		<?php
		if ( ! empty( $itsdz_node['children'] ) ) {
			$itsdz_nav_nodes = $itsdz_node['children'];
			require __DIR__ . '/nav-nodes.php';
		}
		?>
	</details>
	<?php
}
