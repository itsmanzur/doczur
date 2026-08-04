<?php
/**
 * Classic shortcode registration.
 *
 * Registers two shortcodes that replicate the Gutenberg block output so
 * the same features are available in Classic Editor, page builders, and
 * widget areas.
 *
 * Shortcodes:
 *   [doczur_search kb_id="123" placeholder="..." button_text="..."]
 *   [doczur_docs_list kb_id="123" limit="5" show_section="true"]
 *
 * @package ItsDZ\Doczur\Frontend
 */

namespace ItsDZ\Doczur\Frontend;

use ItsDZ\Doczur\Core\Service;
use ItsDZ\Doczur\PostTypes\KB_Post_Type;

defined( 'ABSPATH' ) || exit;

/**
 * Registers and renders Doczur shortcodes.
 */
final class Shortcode implements Service {

	/**
	 * Register shortcode hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'init', array( $this, 'add_shortcodes' ) );
	}

	/**
	 * Register all Doczur shortcodes.
	 *
	 * @return void
	 */
	public function add_shortcodes() {
		add_shortcode( 'doczur_search', array( $this, 'render_search' ) );
		add_shortcode( 'doczur_docs_list', array( $this, 'render_docs_list' ) );
	}

	// -------------------------------------------------------------------------
	// [doczur_search]
	// -------------------------------------------------------------------------

	/**
	 * Render the search form shortcode.
	 *
	 * @param array<string, string>|string $atts Raw shortcode attributes.
	 * @return string HTML output.
	 */
	public function render_search( $atts ) {
		$atts = shortcode_atts(
			array(
				'kb_id'       => '0',
				'placeholder' => '',
				'button_text' => '',
			),
			is_array( $atts ) ? $atts : array(),
			'doczur_search'
		);

		$kb_id       = absint( $atts['kb_id'] );
		$placeholder = sanitize_text_field( $atts['placeholder'] );
		$button_text = sanitize_text_field( $atts['button_text'] );

		if ( ! $placeholder ) {
			$placeholder = __( 'Search documentation…', 'doczur' );
		}

		if ( ! $button_text ) {
			$button_text = __( 'Search', 'doczur' );
		}

		$kb_url = $this->get_kb_url( $kb_id );

		if ( ! $kb_url ) {
			return '<p class="itsdz-block-notice">' . esc_html__( 'Doczur: please set a valid kb_id for the doczur_search shortcode.', 'doczur' ) . '</p>';
		}

		$this->maybe_enqueue_frontend_assets();

		$input_id = 'itsdz-sc-search-' . $kb_id;

		ob_start();
		?>
		<div class="itsdz-search-block">
			<form class="itsdz-search-block-form" action="<?php echo esc_url( $kb_url ); ?>" method="get" role="search">
				<label class="screen-reader-text" for="<?php echo esc_attr( $input_id ); ?>">
					<?php echo esc_html( $placeholder ); ?>
				</label>
				<input
					id="<?php echo esc_attr( $input_id ); ?>"
					class="itsdz-search-block-input"
					type="search"
					name="itsdz_q"
					placeholder="<?php echo esc_attr( $placeholder ); ?>"
					autocomplete="off"
				>
				<button class="itsdz-search-block-button" type="submit">
					<?php echo esc_html( $button_text ); ?>
				</button>
			</form>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	// -------------------------------------------------------------------------
	// [doczur_docs_list]
	// -------------------------------------------------------------------------

	/**
	 * Render the article list shortcode.
	 *
	 * @param array<string, string>|string $atts Raw shortcode attributes.
	 * @return string HTML output.
	 */
	public function render_docs_list( $atts ) {
		$atts = shortcode_atts(
			array(
				'kb_id'        => '0',
				'limit'        => '5',
				'show_section' => 'true',
			),
			is_array( $atts ) ? $atts : array(),
			'doczur_docs_list'
		);

		$kb_id        = absint( $atts['kb_id'] );
		$limit        = max( 1, min( 50, absint( $atts['limit'] ) ) );
		$show_section = in_array( strtolower( (string) $atts['show_section'] ), array( 'true', '1', 'yes' ), true );

		if ( ! $kb_id ) {
			return '<p class="itsdz-block-notice">' . esc_html__( 'Doczur: please set a valid kb_id for the doczur_docs_list shortcode.', 'doczur' ) . '</p>';
		}

		$kb    = get_post( $kb_id );
		$kb_ok = $kb instanceof \WP_Post && KB_Post_Type::POST_TYPE === $kb->post_type && 'publish' === $kb->post_status;

		if ( ! $kb_ok ) {
			return '<p class="itsdz-block-notice">' . esc_html__( 'Doczur: knowledge base not found or not published.', 'doczur' ) . '</p>';
		}

		$all_articles = Documentation::get_articles( $kb_id );
		$articles     = array_slice( $all_articles, 0, $limit );
		$has_more     = count( $all_articles ) > $limit;

		if ( empty( $articles ) ) {
			return '<p class="itsdz-block-notice">' . esc_html__( 'No published articles found.', 'doczur' ) . '</p>';
		}

		ob_start();
		?>
		<div class="itsdz-docs-list-block">
			<ul class="itsdz-docs-list-block-list">
				<?php foreach ( $articles as $article ) : ?>
					<?php
					$section_label = '';

					if ( $show_section ) {
						$terms = get_the_terms( $article, 'itsdz_section' );

						if ( is_array( $terms ) && ! empty( $terms ) ) {
							$section_label = reset( $terms )->name;
						}
					}
					?>
					<li class="itsdz-docs-list-block-item">
						<a href="<?php echo esc_url( (string) get_permalink( $article ) ); ?>">
							<?php echo esc_html( get_the_title( $article ) ); ?>
						</a>
						<?php if ( $section_label ) : ?>
							<span class="itsdz-docs-list-block-section">
								<?php echo esc_html( $section_label ); ?>
							</span>
						<?php endif; ?>
					</li>
				<?php endforeach; ?>
			</ul>
			<?php if ( $has_more ) : ?>
				<a class="itsdz-docs-list-block-more" href="<?php echo esc_url( (string) get_permalink( $kb ) ); ?>">
					<?php esc_html_e( 'View all articles →', 'doczur' ); ?>
				</a>
			<?php endif; ?>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	// -------------------------------------------------------------------------
	// Shared helpers
	// -------------------------------------------------------------------------

	/**
	 * Resolve the permalink for a published KB.
	 *
	 * @param int $kb_id KB post ID.
	 * @return string Empty string if the KB is invalid or not published.
	 */
	private function get_kb_url( $kb_id ) {
		if ( ! $kb_id ) {
			return '';
		}

		$kb = get_post( $kb_id );

		if (
			! $kb instanceof \WP_Post ||
			KB_Post_Type::POST_TYPE !== $kb->post_type ||
			'publish' !== $kb->post_status
		) {
			return '';
		}

		return (string) get_permalink( $kb );
	}

	/**
	 * Enqueue the frontend bundle when not already loaded.
	 *
	 * Allows shortcodes embedded outside doc pages to still function.
	 *
	 * @return void
	 */
	private function maybe_enqueue_frontend_assets() {
		if ( wp_script_is( 'itsdz-frontend', 'enqueued' ) ) {
			return;
		}

		$asset_file  = ITSDZ_PLUGIN_DIR . 'build/frontend.asset.php';
		$script_file = ITSDZ_PLUGIN_DIR . 'build/frontend.js';
		$style_file  = ITSDZ_PLUGIN_DIR . 'build/style-frontend.css';

		if ( ! is_readable( $asset_file ) || ! is_readable( $script_file ) || ! is_readable( $style_file ) ) {
			return;
		}

		$asset = require $asset_file;

		wp_enqueue_script(
			'itsdz-frontend',
			ITSDZ_PLUGIN_URL . 'build/frontend.js',
			$asset['dependencies'],
			$asset['version'],
			true
		);

		wp_enqueue_style(
			'itsdz-frontend',
			ITSDZ_PLUGIN_URL . 'build/style-frontend.css',
			array(),
			(string) filemtime( $style_file )
		);
	}
}
