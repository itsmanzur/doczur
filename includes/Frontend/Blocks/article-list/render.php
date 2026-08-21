<?php
/**
 * Nirdeshio Article List block — server-side render callback.
 *
 * Renders a linked list of published articles from the selected KB.
 * Optionally shows the section label next to each article title.
 *
 * Available variables injected by register_block_type():
 *
 * @var array<string, mixed> $attributes Block attributes.
 * @var string               $content    Inner block HTML (unused).
 * @var WP_Block             $block      Block instance.
 *
 * @package ItsDZ\Doczur
 */

use ItsDZ\Doczur\Frontend\Documentation;
use ItsDZ\Doczur\PostTypes\KB_Post_Type;

defined( 'ABSPATH' ) || exit;

$itsdz_kb_id        = absint( $attributes['kb_id'] ?? 0 );
$itsdz_limit        = max( 1, min( 50, absint( $attributes['limit'] ?? 5 ) ) );
$itsdz_show_section = (bool) ( $attributes['show_section'] ?? true );

// Editor / misconfiguration notice.
if ( ! $itsdz_kb_id ) {
	echo '<p class="itsdz-block-notice">' . esc_html__( 'Select a knowledge base in the Nirdeshio Article List block settings.', 'itsmanzur-docs' ) . '</p>';
	return;
}

$itsdz_kb = get_post( $itsdz_kb_id );

if (
	! $itsdz_kb instanceof WP_Post ||
	KB_Post_Type::POST_TYPE !== $itsdz_kb->post_type ||
	'publish' !== $itsdz_kb->post_status
) {
	echo '<p class="itsdz-block-notice">' . esc_html__( 'Knowledge base not found or not published.', 'itsmanzur-docs' ) . '</p>';
	return;
}

$itsdz_all_articles = Documentation::get_articles( $itsdz_kb_id );
$itsdz_articles     = array_slice( $itsdz_all_articles, 0, $itsdz_limit );
$itsdz_has_more     = count( $itsdz_all_articles ) > $itsdz_limit;

if ( empty( $itsdz_articles ) ) {
	echo '<p class="itsdz-block-notice">' . esc_html__( 'No published articles found in this knowledge base.', 'itsmanzur-docs' ) . '</p>';
	return;
}

$itsdz_wrapper_attrs = get_block_wrapper_attributes( array( 'class' => 'itsdz-docs-list-block' ) );
?>
<div <?php echo $itsdz_wrapper_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_block_wrapper_attributes() escapes. ?>>
	<ul class="itsdz-docs-list-block-list">
		<?php foreach ( $itsdz_articles as $itsdz_article ) : ?>
			<?php
			$itsdz_section_label = '';

			if ( $itsdz_show_section ) {
				$itsdz_terms = get_the_terms( $itsdz_article, 'itsdz_section' );

				if ( is_array( $itsdz_terms ) && ! empty( $itsdz_terms ) ) {
					$itsdz_section_label = reset( $itsdz_terms )->name;
				}
			}
			?>
			<li class="itsdz-docs-list-block-item">
				<a href="<?php echo esc_url( (string) get_permalink( $itsdz_article ) ); ?>">
					<?php echo esc_html( get_the_title( $itsdz_article ) ); ?>
				</a>
				<?php if ( $itsdz_section_label ) : ?>
					<span class="itsdz-docs-list-block-section">
						<?php echo esc_html( $itsdz_section_label ); ?>
					</span>
				<?php endif; ?>
			</li>
		<?php endforeach; ?>
	</ul>
	<?php if ( $itsdz_has_more ) : ?>
		<a class="itsdz-docs-list-block-more" href="<?php echo esc_url( (string) get_permalink( $itsdz_kb ) ); ?>">
			<?php esc_html_e( 'View all articles →', 'itsmanzur-docs' ); ?>
		</a>
	<?php endif; ?>
</div>
