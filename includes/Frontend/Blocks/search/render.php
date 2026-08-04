<?php
/**
 * Doczur Search block — server-side render callback.
 *
 * Renders a search form that submits to the target KB page and auto-opens
 * the Doczur search modal pre-filled with the entered query (via the
 * `itsdz_q` URL parameter picked up by the frontend JS).
 *
 * Available variables injected by register_block_type():
 *
 * @var array<string, mixed> $attributes Block attributes.
 * @var string               $content    Inner block HTML (unused).
 * @var WP_Block             $block      Block instance.
 *
 * @package ItsDZ\Doczur
 */

use ItsDZ\Doczur\PostTypes\KB_Post_Type;

defined( 'ABSPATH' ) || exit;

$itsdz_kb_id       = absint( $attributes['kb_id'] ?? 0 );
$itsdz_placeholder = sanitize_text_field( $attributes['placeholder'] ?? '' );
$itsdz_button_text = sanitize_text_field( $attributes['button_text'] ?? '' );

if ( ! $itsdz_placeholder ) {
	$itsdz_placeholder = __( 'Search documentation…', 'doczur' );
}

if ( ! $itsdz_button_text ) {
	$itsdz_button_text = __( 'Search', 'doczur' );
}

// Resolve KB permalink for the form action.
$itsdz_kb_url = '';

if ( $itsdz_kb_id ) {
	$itsdz_kb = get_post( $itsdz_kb_id );

	if (
		$itsdz_kb instanceof WP_Post &&
		KB_Post_Type::POST_TYPE === $itsdz_kb->post_type &&
		'publish' === $itsdz_kb->post_status
	) {
		$itsdz_kb_url = (string) get_permalink( $itsdz_kb );
	}
}

// Enqueue frontend assets when the block is embedded outside a doc page
// (on doc pages, Frontend\Assets::enqueue() has already done this).
if ( ! wp_script_is( 'itsdz-frontend', 'enqueued' ) ) {
	$itsdz_asset_file = ITSDZ_PLUGIN_DIR . 'build/frontend.asset.php';
	$itsdz_script     = ITSDZ_PLUGIN_DIR . 'build/frontend.js';
	$itsdz_style      = ITSDZ_PLUGIN_DIR . 'build/style-frontend.css';

	if ( is_readable( $itsdz_asset_file ) && is_readable( $itsdz_script ) && is_readable( $itsdz_style ) ) {
		$itsdz_asset_data = require $itsdz_asset_file;

		wp_enqueue_script(
			'itsdz-frontend',
			ITSDZ_PLUGIN_URL . 'build/frontend.js',
			$itsdz_asset_data['dependencies'],
			$itsdz_asset_data['version'],
			true
		);

		wp_enqueue_style(
			'itsdz-frontend',
			ITSDZ_PLUGIN_URL . 'build/style-frontend.css',
			array(),
			(string) filemtime( $itsdz_style )
		);
	}
}

$itsdz_wrapper_attrs = get_block_wrapper_attributes( array( 'class' => 'itsdz-search-block' ) );
?>
<div <?php echo $itsdz_wrapper_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_block_wrapper_attributes() escapes. ?>>
	<?php if ( $itsdz_kb_url ) : ?>
		<form
			class="itsdz-search-block-form"
			action="<?php echo esc_url( $itsdz_kb_url ); ?>"
			method="get"
			role="search"
		>
			<label class="screen-reader-text" for="itsdz-search-block-input-<?php echo esc_attr( (string) $itsdz_kb_id ); ?>">
				<?php echo esc_html( $itsdz_placeholder ); ?>
			</label>
			<input
				id="itsdz-search-block-input-<?php echo esc_attr( (string) $itsdz_kb_id ); ?>"
				class="itsdz-search-block-input"
				type="search"
				name="itsdz_q"
				placeholder="<?php echo esc_attr( $itsdz_placeholder ); ?>"
				autocomplete="off"
			>
			<button class="itsdz-search-block-button" type="submit">
				<?php echo esc_html( $itsdz_button_text ); ?>
			</button>
		</form>
	<?php else : ?>
		<p class="itsdz-block-notice">
			<?php esc_html_e( 'Select a knowledge base in the Doczur Search block settings.', 'doczur' ); ?>
		</p>
	<?php endif; ?>
</div>
