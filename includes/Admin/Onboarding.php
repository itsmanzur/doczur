<?php
/**
 * Onboarding wizard state, local analytics, and dismissible admin notice.
 *
 * @package ItsDZ\Doczur\Admin
 */

namespace ItsDZ\Doczur\Admin;

use ItsDZ\Doczur\Core\Migrations\Migration_1_0_0;
use ItsDZ\Doczur\Core\Service;
use ItsDZ\Doczur\Security\Capabilities;

defined( 'ABSPATH' ) || exit;

/**
 * Stores wizard progress in wp_options and optional step events in search_log.
 */
final class Onboarding implements Service {
	/**
	 * Option holding wizard status (not content).
	 */
	const OPTION = 'itsdz_onboarding';

	/**
	 * User meta tracking how many times the WP-admin notice was dismissed.
	 */
	const NOTICE_META = 'itsdz_onboarding_notice_dismisses';

	/**
	 * Maximum times the dismissible admin notice may appear.
	 */
	const MAX_NOTICE_DISMISSES = 2;

	/**
	 * Search log query sentinel for opt-in onboarding events.
	 *
	 * Future search-analytics queries MUST exclude query = 'onboarding'
	 * (and typically kb_id = 0) so these rows never appear as searches.
	 */
	const EVENT_QUERY = 'onboarding';

	/**
	 * Register admin notice hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'admin_notices', array( $this, 'render_notice' ) );
		add_action( 'admin_init', array( $this, 'maybe_dismiss_notice' ) );
	}

	/**
	 * Default wizard state. Never stores personal data.
	 *
	 * @return array{status: string, step: int, analytics_opt_in: bool, page_id: int}
	 */
	public static function defaults() {
		return array(
			'status'           => 'pending',
			'step'             => 1,
			'analytics_opt_in' => false,
			'page_id'          => 0,
		);
	}

	/**
	 * Read the persisted wizard state.
	 *
	 * @return array{status: string, step: int, analytics_opt_in: bool, page_id: int}
	 */
	public static function get_state() {
		$stored = get_option( self::OPTION, array() );

		if ( ! is_array( $stored ) ) {
			$stored = array();
		}

		$state = array_merge( self::defaults(), $stored );

		$state['status']           = self::sanitize_status( $state['status'] );
		$state['step']             = self::sanitize_step( $state['step'] );
		$state['analytics_opt_in'] = (bool) $state['analytics_opt_in'];
		$state['page_id']          = absint( $state['page_id'] );

		return $state;
	}

	/**
	 * Persist wizard UI state without touching documentation content.
	 *
	 * @param array<string, mixed> $updates Partial state.
	 * @return array{status: string, step: int, analytics_opt_in: bool, page_id: int}
	 */
	public static function save_state( array $updates ) {
		$state = array_merge( self::get_state(), $updates );
		$state = array(
			'status'           => self::sanitize_status( $state['status'] ),
			'step'             => self::sanitize_step( $state['step'] ),
			'analytics_opt_in' => (bool) $state['analytics_opt_in'],
			'page_id'          => absint( $state['page_id'] ),
		);

		update_option( self::OPTION, $state, false );

		return $state;
	}

	/**
	 * Whether the in-plugin wizard UI should open.
	 *
	 * @return bool
	 */
	public static function should_show_wizard() {
		$state = self::get_state();

		return in_array( $state['status'], array( 'pending', 'in_progress' ), true );
	}

	/**
	 * Record a completed step. Optionally log an anonymous event.
	 *
	 * @param int       $step             Step number 1–5.
	 * @param bool|null $analytics_opt_in Explicit opt-in toggle, or null to leave unchanged.
	 * @param int       $page_id          Optional page created in step 4.
	 * @return array{status: string, step: int, analytics_opt_in: bool, page_id: int}
	 */
	public static function complete_step( $step, $analytics_opt_in = null, $page_id = 0 ) {
		$step  = self::sanitize_step( $step );
		$state = self::get_state();

		if ( null !== $analytics_opt_in ) {
			$state['analytics_opt_in'] = (bool) $analytics_opt_in;
		}

		$state['step']   = $step;
		$state['status'] = 5 === $step ? 'completed' : 'in_progress';

		if ( $page_id ) {
			$state['page_id'] = absint( $page_id );
		}

		$state = self::save_state( $state );
		self::maybe_log_event( $step, $state['analytics_opt_in'] );

		return $state;
	}

	/**
	 * Dismiss the wizard without deleting content.
	 *
	 * @param bool|null $analytics_opt_in Explicit opt-in toggle, or null to leave unchanged.
	 * @return array{status: string, step: int, analytics_opt_in: bool, page_id: int}
	 */
	public static function skip( $analytics_opt_in = null ) {
		$state = self::get_state();

		if ( null !== $analytics_opt_in ) {
			$state['analytics_opt_in'] = (bool) $analytics_opt_in;
		}

		$state['status'] = 'skipped';
		$state           = self::save_state( $state );
		self::maybe_log_event( 0, $state['analytics_opt_in'] );

		return $state;
	}

	/**
	 * Re-open the wizard UI from step 1. Existing sections and articles stay.
	 *
	 * @return array{status: string, step: int, analytics_opt_in: bool, page_id: int}
	 */
	public static function restart() {
		$state           = self::get_state();
		$state['status'] = 'pending';
		$state['step']   = 1;

		return self::save_state( $state );
	}

	/**
	 * Drop-off counts keyed by step number. Empty unless the site opted in.
	 *
	 * @return array<int, int>
	 */
	public static function dropoff_counts() {
		$counts = array(
			0 => 0,
			1 => 0,
			2 => 0,
			3 => 0,
			4 => 0,
			5 => 0,
		);

		if ( ! self::get_state()['analytics_opt_in'] ) {
			return $counts;
		}

		global $wpdb;

		$table = Migration_1_0_0::table_names()['search_log'];

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Admin-only aggregate of local onboarding events.
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT results_count AS step, COUNT(*) AS total FROM %i WHERE kb_id = %d AND query = %s GROUP BY results_count',
				$table,
				0,
				self::EVENT_QUERY
			),
			ARRAY_A
		);

		if ( ! is_array( $rows ) ) {
			return $counts;
		}

		foreach ( $rows as $row ) {
			$step = absint( $row['step'] );

			if ( isset( $counts[ $step ] ) ) {
				$counts[ $step ] = absint( $row['total'] );
			}
		}

		return $counts;
	}

	/**
	 * Public payload for the status endpoint.
	 *
	 * @return array<string, mixed>
	 */
	public static function status_payload() {
		$state = self::get_state();
		$page  = $state['page_id'] ? get_post( $state['page_id'] ) : null;
		$url   = ( $page instanceof \WP_Post && 'publish' === $page->post_status )
			? (string) get_permalink( $page )
			: '';

		return array(
			'status'           => $state['status'],
			'step'             => $state['step'],
			'analytics_opt_in' => $state['analytics_opt_in'],
			'should_show'      => self::should_show_wizard(),
			'page_id'          => $state['page_id'],
			'page_url'         => $url,
			'dropoff'          => self::dropoff_counts(),
		);
	}

	/**
	 * Show a dismissible prompt at most twice, never after skip/complete.
	 *
	 * @return void
	 */
	public function render_notice() {
		if ( ! current_user_can( Capabilities::MANAGE_DOCS ) ) {
			return;
		}

		if ( ! self::should_show_wizard() ) {
			return;
		}

		$screen = get_current_screen();

		$plugin_screen = 'toplevel_page_' . Admin_Menu::PAGE_SLUG;

		if ( $screen && ( $plugin_screen === $screen->id || Admin_Menu::PAGE_SLUG === $screen->parent_base ) ) {
			return;
		}

		$user_id   = get_current_user_id();
		$dismisses = absint( get_user_meta( $user_id, self::NOTICE_META, true ) );

		if ( $dismisses >= self::MAX_NOTICE_DISMISSES ) {
			return;
		}

		$url     = add_query_arg(
			array(
				'page' => Admin_Menu::PAGE_SLUG,
			),
			admin_url( 'admin.php' )
		);
		$dismiss = wp_nonce_url(
			add_query_arg( 'itsdz_onboarding_dismiss', '1' ),
			'itsdz_onboarding_dismiss'
		);
		?>
		<div class="notice notice-info is-dismissible">
			<p>
				<?php esc_html_e( 'Finish setting up Nirdeshio in about two minutes — or skip and explore on your own.', 'itsmanzur-docs' ); ?>
				<a href="<?php echo esc_url( $url ); ?>"><?php esc_html_e( 'Open setup', 'itsmanzur-docs' ); ?></a>
				|
				<a href="<?php echo esc_url( $dismiss ); ?>"><?php esc_html_e( 'Dismiss', 'itsmanzur-docs' ); ?></a>
			</p>
		</div>
		<?php
	}

	/**
	 * Persist a notice dismissal against the current user.
	 *
	 * @return void
	 */
	public function maybe_dismiss_notice() {
		if ( ! isset( $_GET['itsdz_onboarding_dismiss'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}

		if ( ! current_user_can( Capabilities::MANAGE_DOCS ) ) {
			return;
		}

		check_admin_referer( 'itsdz_onboarding_dismiss' );

		$user_id = get_current_user_id();
		$count   = absint( get_user_meta( $user_id, self::NOTICE_META, true ) ) + 1;
		update_user_meta( $user_id, self::NOTICE_META, min( $count, self::MAX_NOTICE_DISMISSES ) );

		wp_safe_redirect( remove_query_arg( array( 'itsdz_onboarding_dismiss', '_wpnonce' ) ) );
		exit;
	}

	/**
	 * Write step + UTC timestamp only when the site explicitly opted in.
	 *
	 * @param int  $step   Step number (0 = skipped).
	 * @param bool $opt_in Current opt-in flag.
	 * @return void
	 */
	private static function maybe_log_event( $step, $opt_in ) {
		if ( ! $opt_in ) {
			return;
		}

		global $wpdb;

		$table = Migration_1_0_0::table_names()['search_log'];

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Opt-in local analytics only; no PII.
		$wpdb->insert(
			$table,
			array(
				'kb_id'              => 0,
				'query'              => self::EVENT_QUERY,
				'results_count'      => self::sanitize_step( $step, true ),
				'clicked_article_id' => 0,
				'ip_hash'            => '',
				'created_at'         => current_time( 'mysql', true ),
			),
			array( '%d', '%s', '%d', '%d', '%s', '%s' )
		);
	}

	/**
	 * Normalize a stored wizard status.
	 *
	 * @param mixed $status Raw status.
	 * @return string
	 */
	private static function sanitize_status( $status ) {
		$status = is_scalar( $status ) ? sanitize_key( (string) $status ) : '';

		return in_array( $status, array( 'pending', 'in_progress', 'skipped', 'completed' ), true )
			? $status
			: 'pending';
	}

	/**
	 * Clamp a wizard step to the allowed range.
	 *
	 * @param mixed $step       Raw step.
	 * @param bool  $allow_zero Allow 0 for skip events.
	 * @return int
	 */
	private static function sanitize_step( $step, $allow_zero = false ) {
		$step = absint( $step );
		$min  = $allow_zero ? 0 : 1;

		if ( $step < $min ) {
			return $min;
		}

		return min( 5, $step );
	}
}
