<?php
/**
 * Shared public documentation shell end.
 *
 * @package ItsDZ\Doczur
 *
 * @var string $itsdz_layout_mode Template shell mode.
 * @var int    $itsdz_kb_id       Documentation project ID.
 */

use ItsDZ\Doczur\Frontend\Documentation;

defined( 'ABSPATH' ) || exit;
?>
	<footer class="itsdz-site-footer">
		<p>
			<?php
			printf(
				/* translators: %s: Doczur product name. */
				esc_html__( 'Documentation powered by %s', 'doczur' ),
				'<strong>Doczur</strong>'
			);
			?>
		</p>
	</footer>
	<div class="itsdz-search-modal" data-itsdz-search-modal hidden>
		<div class="itsdz-search-backdrop" data-itsdz-search-close></div>
		<section class="itsdz-search-dialog" role="dialog" aria-modal="true" aria-labelledby="itsdz-search-title">
			<div class="itsdz-search-box">
				<span class="itsdz-icon-search itsdz-icon-lg" aria-hidden="true"></span>
				<label class="screen-reader-text" id="itsdz-search-title" for="itsdz-search-input"><?php esc_html_e( 'Search documentation', 'doczur' ); ?></label>
				<input id="itsdz-search-input" type="search" autocomplete="off" placeholder="<?php esc_attr_e( 'Search articles…', 'doczur' ); ?>" data-itsdz-search-input>
				<button type="button" data-itsdz-search-close aria-label="<?php esc_attr_e( 'Close search', 'doczur' ); ?>">Esc</button>
			</div>
			<p class="itsdz-search-status" role="status" aria-live="polite" data-itsdz-search-status><?php esc_html_e( 'Type at least two characters to search.', 'doczur' ); ?></p>
			<div
				class="itsdz-search-results"
				data-itsdz-search-results
				data-itsdz-popular="<?php echo esc_attr( (string) wp_json_encode( Documentation::get_popular_links( $itsdz_kb_id ) ) ); ?>"
			></div>
		</section>
	</div>
</div>
<?php
if ( 'theme' === $itsdz_layout_mode ) {
	get_footer();
} else {
	wp_footer();
	?>
	</body>
	</html>
	<?php
}
