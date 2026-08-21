<?php
/**
 * Documentation section taxonomy.
 *
 * @package ItsDZ\Doczur\Taxonomies
 */

namespace ItsDZ\Doczur\Taxonomies;

use ItsDZ\Doczur\Core\Service;
use ItsDZ\Doczur\PostTypes\Article_Post_Type;
use ItsDZ\Doczur\PostTypes\KB_Post_Type;
use ItsDZ\Doczur\Security\Capabilities;

defined( 'ABSPATH' ) || exit;

/**
 * Registers hierarchical article sections.
 */
final class Section_Taxonomy implements Service {
	/**
	 * Taxonomy key.
	 */
	const TAXONOMY = 'itsdz_section';

	/**
	 * Register WordPress hooks.
	 *
	 * @return void
	 */
	/**
	 * Register WordPress hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'init', array( $this, 'register_taxonomy' ), 5 );
		add_action( 'init', array( $this, 'register_meta' ) );

		// Admin form fields for term icon.
		add_action( self::TAXONOMY . '_add_form_fields', array( $this, 'add_icon_field' ) );
		add_action( self::TAXONOMY . '_edit_form_fields', array( $this, 'edit_icon_field' ) );
		add_action( 'created_' . self::TAXONOMY, array( $this, 'save_icon_field' ) );
		add_action( 'edited_' . self::TAXONOMY, array( $this, 'save_icon_field' ) );
	}

	/**
	 * Register term meta for section icon.
	 *
	 * @return void
	 */
	public function register_meta() {
		register_term_meta(
			self::TAXONOMY,
			'_itsdz_section_icon',
			array(
				'type'              => 'string',
				'single'            => true,
				'sanitize_callback' => 'sanitize_text_field',
				'show_in_rest'      => true,
			)
		);
	}

	/**
	 * Add icon field on new term screen.
	 *
	 * @return void
	 */
	public function add_icon_field() {
		?>
		<div class="form-field term-icon-wrap">
			<label for="itsdz-section-icon"><?php esc_html_e( 'Section Icon (Dashicon class)', 'itsmanzur-docs' ); ?></label>
			<input type="text" name="itsdz_section_icon" id="itsdz-section-icon" value="" placeholder="dashicons-category" />
			<p class="description"><?php esc_html_e( 'Enter a Dashicons class (e.g. dashicons-book, dashicons-category, dashicons-vault).', 'itsmanzur-docs' ); ?></p>
		</div>
		<?php
	}

	/**
	 * Edit icon field on edit term screen.
	 *
	 * @param \WP_Term $term Current term object.
	 * @return void
	 */
	public function edit_icon_field( $term ) {
		$icon = get_term_meta( $term->term_id, '_itsdz_section_icon', true );
		?>
		<tr class="form-field term-icon-wrap">
			<th scope="row"><label for="itsdz-section-icon"><?php esc_html_e( 'Section Icon', 'itsmanzur-docs' ); ?></label></th>
			<td>
				<input type="text" name="itsdz_section_icon" id="itsdz-section-icon" value="<?php echo esc_attr( (string) $icon ); ?>" placeholder="dashicons-category" />
				<p class="description"><?php esc_html_e( 'Enter a Dashicons class name (e.g. dashicons-book, dashicons-category, dashicons-hammer).', 'itsmanzur-docs' ); ?></p>
			</td>
		</tr>
		<?php
	}

	/**
	 * Save term icon meta.
	 *
	 * @param int $term_id Term ID.
	 * @return void
	 */
	public function save_icon_field( $term_id ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( isset( $_POST['itsdz_section_icon'] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Missing
			$icon = sanitize_html_class( wp_unslash( $_POST['itsdz_section_icon'] ) );
			update_term_meta( $term_id, '_itsdz_section_icon', $icon );
		}
	}

	/**
	 * Register the section taxonomy.
	 *
	 * @return void
	 */
	public function register_taxonomy() {
		register_taxonomy(
			self::TAXONOMY,
			array( Article_Post_Type::POST_TYPE ),
			array(
				'labels'            => array(
					'name'              => _x( 'Sections', 'taxonomy general name', 'itsmanzur-docs' ),
					'singular_name'     => _x( 'Section', 'taxonomy singular name', 'itsmanzur-docs' ),
					'search_items'      => __( 'Search Sections', 'itsmanzur-docs' ),
					'all_items'         => __( 'All Sections', 'itsmanzur-docs' ),
					'parent_item'       => __( 'Parent Section', 'itsmanzur-docs' ),
					'parent_item_colon' => __( 'Parent Section:', 'itsmanzur-docs' ),
					'edit_item'         => __( 'Edit Section', 'itsmanzur-docs' ),
					'update_item'       => __( 'Update Section', 'itsmanzur-docs' ),
					'add_new_item'      => __( 'Add New Section', 'itsmanzur-docs' ),
					'new_item_name'     => __( 'New Section Name', 'itsmanzur-docs' ),
					'menu_name'         => __( 'Sections', 'itsmanzur-docs' ),
				),
				'public'            => true,
				'hierarchical'      => true,
				'show_ui'           => true,
				'show_admin_column' => true,
				'show_in_rest'      => true,
				'capabilities'      => Capabilities::taxonomy_map(),
				'rewrite'           => array(
					'slug'       => KB_Post_Type::rewrite_slug() . '/section',
					'with_front' => false,
				),
			)
		);
	}
}
