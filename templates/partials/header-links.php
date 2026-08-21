<?php
/**
 * Optional public header shortcut links.
 *
 * @package ItsDZ\Doczur
 *
 * @var array<int, array{label: string, url: string}> $itsdz_header_links Header links.
 */

defined( 'ABSPATH' ) || exit;

if ( empty( $itsdz_header_links ) ) {
	return;
}
?>
<nav class="itsdz-header-links" aria-label="<?php esc_attr_e( 'Documentation links', 'itsmanzur-docs' ); ?>">
	<?php foreach ( $itsdz_header_links as $itsdz_header_link ) : ?>
		<a href="<?php echo esc_url( $itsdz_header_link['url'] ); ?>"><?php echo esc_html( $itsdz_header_link['label'] ); ?></a>
	<?php endforeach; ?>
</nav>
