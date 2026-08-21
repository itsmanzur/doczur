<?php
/**
 * Nirdeshio FAQ block — server-side render callback.
 *
 * Available variables injected by register_block_type():
 *
 * @var array<string, mixed> $attributes Block attributes.
 * @var string               $content    Inner block HTML (unused).
 * @var WP_Block             $block      Block instance.
 *
 * @package ItsDZ\Doczur
 */

use ItsDZ\Doczur\Frontend\Renderers;

defined( 'ABSPATH' ) || exit;

$itsdz_items   = isset( $attributes['items'] ) && is_array( $attributes['items'] ) ? $attributes['items'] : array();
$itsdz_heading = isset( $attributes['heading'] ) && is_scalar( $attributes['heading'] ) ? (string) $attributes['heading'] : '';
$itsdz_schema  = ! isset( $attributes['schema'] ) || (bool) $attributes['schema'];

$itsdz_wrapper_attrs = get_block_wrapper_attributes();
?>
<div <?php echo $itsdz_wrapper_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_block_wrapper_attributes() escapes. ?>>
	<?php echo Renderers::faq( $itsdz_items, $itsdz_heading, $itsdz_schema ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Renderers::faq() escapes each field. ?>
</div>
