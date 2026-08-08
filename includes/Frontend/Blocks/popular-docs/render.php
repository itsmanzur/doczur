<?php
/**
 * Doczur Popular Articles block — server-side render callback.
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

$itsdz_kb_id      = absint( $attributes['kb_id'] ?? 0 );
$itsdz_limit      = absint( $attributes['limit'] ?? 5 );
$itsdz_order      = isset( $attributes['order'] ) && is_scalar( $attributes['order'] ) ? (string) $attributes['order'] : 'popular';
$itsdz_show_views = ! isset( $attributes['show_views'] ) || (bool) $attributes['show_views'];

$itsdz_wrapper_attrs = get_block_wrapper_attributes();
?>
<div <?php echo $itsdz_wrapper_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_block_wrapper_attributes() escapes. ?>>
	<?php echo Renderers::article_ranking( $itsdz_kb_id, $itsdz_limit, $itsdz_order, $itsdz_show_views ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Renderers::article_ranking() escapes each field. ?>
</div>
