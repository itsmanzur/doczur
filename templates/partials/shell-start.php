<?php
/**
 * Shared public documentation shell start.
 *
 * @package ItsDZ\Doczur
 *
 * @var WP_Post $itsdz_kb Documentation project.
 */

defined( 'ABSPATH' ) || exit;

$itsdz_kb_id       = $itsdz_kb->ID;
$itsdz_layout_mode = get_post_meta( $itsdz_kb_id, '_itsdz_kb_layout_mode', true );
$itsdz_theme_mode  = get_post_meta( $itsdz_kb_id, '_itsdz_kb_theme_mode', true );
$itsdz_template    = get_post_meta( $itsdz_kb_id, '_itsdz_kb_template', true );
$itsdz_logo_id     = absint( get_post_meta( $itsdz_kb_id, '_itsdz_kb_logo', true ) );

$itsdz_layout_mode = in_array( $itsdz_layout_mode, array( 'canvas', 'theme' ), true ) ? $itsdz_layout_mode : 'canvas';
$itsdz_theme_mode  = in_array( $itsdz_theme_mode, array( 'light', 'dark', 'system' ), true ) ? $itsdz_theme_mode : 'system';
$itsdz_template    = in_array( $itsdz_template, array( 'clean', 'modern', 'compact' ), true ) ? $itsdz_template : 'clean';

if ( 'theme' === $itsdz_layout_mode ) {
	get_header();
} else {
	?>
	<!doctype html>
	<html <?php language_attributes(); ?>>
	<head>
		<meta charset="<?php bloginfo( 'charset' ); ?>">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<?php wp_head(); ?>
	</head>
	<body <?php body_class( 'itsdz-canvas-page' ); ?>>
	<?php wp_body_open(); ?>
	<?php
}
?>
<div
	class="itsdz-docs itsdz-template-<?php echo esc_attr( $itsdz_template ); ?>"
	data-kb-id="<?php echo esc_attr( (string) $itsdz_kb_id ); ?>"
	data-rest-url="<?php echo esc_url( rest_url( 'itsdz/v1/' ) ); ?>"
	data-theme="<?php echo esc_attr( $itsdz_theme_mode ); ?>"
>
	<a class="itsdz-skip-link" href="#itsdz-main"><?php esc_html_e( 'Skip to documentation', 'doczur' ); ?></a>
	<header class="itsdz-site-header">
		<div class="itsdz-header-inner">
			<a class="itsdz-site-brand" href="<?php echo esc_url( get_permalink( $itsdz_kb ) ); ?>">
				<?php if ( $itsdz_logo_id ) : ?>
					<?php echo wp_get_attachment_image( $itsdz_logo_id, 'thumbnail', false, array( 'class' => 'itsdz-site-logo' ) ); ?>
				<?php else : ?>
					<span class="itsdz-site-mark" aria-hidden="true">D</span>
				<?php endif; ?>
				<span><?php echo esc_html( get_the_title( $itsdz_kb ) ); ?></span>
			</a>
			<div class="itsdz-header-actions">
				<button class="itsdz-icon-button itsdz-mobile-nav-button" type="button" data-itsdz-nav-toggle aria-controls="itsdz-sidebar" aria-expanded="false">
					<span aria-hidden="true">☰</span>
					<span class="screen-reader-text"><?php esc_html_e( 'Open documentation navigation', 'doczur' ); ?></span>
				</button>
				<button class="itsdz-search-trigger" type="button" data-itsdz-search-open aria-label="<?php esc_attr_e( 'Search documentation', 'doczur' ); ?>">
					<span aria-hidden="true">⌕</span>
					<span><?php esc_html_e( 'Search documentation', 'doczur' ); ?></span>
					<kbd>Ctrl K</kbd>
				</button>
				<button class="itsdz-icon-button" type="button" data-itsdz-theme-toggle aria-label="<?php esc_attr_e( 'Toggle color mode', 'doczur' ); ?>">
					<span aria-hidden="true">◐</span>
				</button>
			</div>
		</div>
	</header>
