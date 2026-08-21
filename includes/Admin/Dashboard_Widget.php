<?php
/**
 * WordPress dashboard health widget.
 *
 * @package ItsDZ\Doczur\Admin
 */

namespace ItsDZ\Doczur\Admin;

use ItsDZ\Doczur\Core\Service;
use ItsDZ\Doczur\PostTypes\Article_Post_Type;
use ItsDZ\Doczur\PostTypes\KB_Post_Type;
use ItsDZ\Doczur\Security\Capabilities;

defined( 'ABSPATH' ) || exit;

/**
 * Surfaces draft, stale, and unsectioned articles on the WP dashboard.
 */
final class Dashboard_Widget implements Service {
	/**
	 * Days after which an unreviewed article is flagged.
	 */
	const STALE_AFTER_DAYS = 90;

	/**
	 * Register WordPress hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'wp_dashboard_setup', array( $this, 'add_widget' ) );
	}

	/**
	 * Add the dashboard widget for documentation managers.
	 *
	 * @return void
	 */
	public function add_widget() {
		if ( ! current_user_can( Capabilities::MANAGE_DOCS ) ) {
			return;
		}

		wp_add_dashboard_widget(
			'itsdz_docs_health',
			__( 'Nirdeshio', 'itsmanzur-docs' ),
			array( $this, 'render' )
		);
	}

	/**
	 * Render draft, stale, and unsectioned counts.
	 *
	 * @return void
	 */
	public function render() {
		$kb = $this->get_kb();

		if ( ! $kb ) {
			printf(
				'<p>%s</p>',
				esc_html__( 'No documentation project yet. Open Nirdeshio to create one.', 'itsmanzur-docs' )
			);
			return;
		}

		$articles = get_posts(
			array(
				'meta_key'       => '_itsdz_kb_id', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value'     => $kb->ID, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
				'posts_per_page' => 200, // phpcs:ignore WordPress.WP.PostsPerPage.posts_per_page_posts_per_page -- dashboard health counts, not a public query.
				'post_status'    => array( 'draft', 'publish' ),
				'post_type'      => Article_Post_Type::POST_TYPE,
			)
		);

		$drafts      = 0;
		$stale       = 0;
		$unsectioned = 0;
		$cutoff      = gmdate( 'Y-m-d', time() - ( self::STALE_AFTER_DAYS * DAY_IN_SECONDS ) );

		foreach ( $articles as $article ) {
			if ( 'draft' === $article->post_status ) {
				++$drafts;
			}

			$reviewed = (string) get_post_meta( $article->ID, '_itsdz_last_reviewed', true );
			$stamp    = $reviewed ? $reviewed : substr( (string) $article->post_modified_gmt, 0, 10 );

			if ( $stamp && $stamp < $cutoff ) {
				++$stale;
			}

			$sections = get_the_terms( $article, 'itsdz_section' );

			if ( ! is_array( $sections ) || ! $sections ) {
				++$unsectioned;
			}
		}

		$screen_url = admin_url( 'admin.php?page=' . Admin_Menu::PAGE_SLUG );
		?>
		<ul class="itsdz-dashboard-health">
			<li>
				<strong><?php echo esc_html( (string) $drafts ); ?></strong>
				<?php esc_html_e( 'drafts', 'itsmanzur-docs' ); ?>
			</li>
			<li>
				<strong><?php echo esc_html( (string) $stale ); ?></strong>
				<?php esc_html_e( 'need review', 'itsmanzur-docs' ); ?>
			</li>
			<li>
				<strong><?php echo esc_html( (string) $unsectioned ); ?></strong>
				<?php esc_html_e( 'unsectioned', 'itsmanzur-docs' ); ?>
			</li>
		</ul>
		<p>
			<a href="<?php echo esc_url( $screen_url ); ?>"><?php esc_html_e( 'Open documentation', 'itsmanzur-docs' ); ?></a>
		</p>
		<style>
			.itsdz-dashboard-health { display: flex; gap: 16px; margin: 0 0 12px; padding: 0; list-style: none; }
			.itsdz-dashboard-health li { margin: 0; color: #646970; }
			.itsdz-dashboard-health strong { display: block; color: #1d2327; font-size: 22px; line-height: 1.2; }
		</style>
		<?php
	}

	/**
	 * Resolve the Free documentation project.
	 *
	 * @return \WP_Post|null
	 */
	private function get_kb() {
		$posts = get_posts(
			array(
				'numberposts' => 1,
				'post_status' => array( 'publish', 'draft' ),
				'post_type'   => KB_Post_Type::POST_TYPE,
			)
		);

		return $posts ? $posts[0] : null;
	}
}
