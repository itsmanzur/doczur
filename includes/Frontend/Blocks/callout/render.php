<?php
/**
 * Doczur Callout block — server-side render callback.
 *
 * Available variables injected by register_block_type():
 *
 * @var array<string, mixed> $attributes Block attributes.
 * @var string               $content    Inner block HTML (unused).
 * @var WP_Block             $block      Block instance.
 *
 * @package ItsDZ\Doczur
 */

defined( 'ABSPATH' ) || exit;

$itsdz_variants = array( 'info', 'warning', 'danger', 'tip' );
$itsdz_variant  = isset( $attributes['variant'] ) && in_array( $attributes['variant'], $itsdz_variants, true )
	? $attributes['variant']
	: 'info';
$itsdz_content  = isset( $attributes['content'] ) && is_scalar( $attributes['content'] )
	? wp_kses_post( (string) $attributes['content'] )
	: '';

if ( '' === trim( wp_strip_all_tags( $itsdz_content ) ) ) {
	return;
}

$itsdz_wrapper_attrs = get_block_wrapper_attributes(
	array( 'class' => 'itsdz-callout itsdz-callout-' . $itsdz_variant )
);
?>
<div <?php echo $itsdz_wrapper_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_block_wrapper_attributes() escapes. ?>>
	<p><?php echo $itsdz_content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_kses_post() above. ?></p>
</div>
