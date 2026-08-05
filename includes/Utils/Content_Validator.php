<?php
/**
 * Shared content validation.
 *
 * @package ItsDZ\Doczur\Utils
 */

namespace ItsDZ\Doczur\Utils;

use ItsDZ\Doczur\PostTypes\KB_Post_Type;

defined( 'ABSPATH' ) || exit;

/**
 * Validates Doczur object relationships.
 */
final class Content_Validator {
	/**
	 * Resolve and validate a documentation project.
	 *
	 * @param int  $kb_id       Project post ID.
	 * @param bool $public_only Require published status.
	 * @return \WP_Post|\WP_Error
	 */
	public static function get_kb( $kb_id, $public_only = false ) {
		$kb = get_post( absint( $kb_id ) );

		if ( ! $kb || KB_Post_Type::POST_TYPE !== $kb->post_type || 'trash' === $kb->post_status ) {
			return new \WP_Error(
				'itsdz_invalid_kb',
				__( 'The documentation project does not exist.', 'doczur' ),
				array( 'status' => 400 )
			);
		}

		if ( $public_only && 'publish' !== $kb->post_status ) {
			return new \WP_Error(
				'itsdz_kb_not_public',
				__( 'The documentation project is not publicly available.', 'doczur' ),
				array( 'status' => 404 )
			);
		}

		return $kb;
	}

	/**
	 * Sanitize article content allowing responsive iframe video embeds.
	 *
	 * @param string $content Raw HTML content.
	 * @return string
	 */
	public static function sanitize_content( $content ) {
		$allowed_html                 = wp_kses_allowed_html( 'post' );
		$allowed_html['iframe']       = array(
			'src'             => true,
			'width'           => true,
			'height'          => true,
			'frameborder'     => true,
			'allow'           => true,
			'allowfullscreen' => true,
			'class'           => true,
			'style'           => true,
			'title'           => true,
		);
		$allowed_html['figure']       = array(
			'class' => true,
			'style' => true,
		);
		$allowed_html['figcaption']   = array(
			'class' => true,
			'style' => true,
		);
		$allowed_html['img']          = array(
			'src'     => true,
			'alt'     => true,
			'width'   => true,
			'height'  => true,
			'class'   => true,
			'style'   => true,
			'loading' => true,
		);
		$allowed_html['details']      = array(
			'class' => true,
			'open'  => true,
		);
		$allowed_html['summary']      = array(
			'class' => true,
		);
		$allowed_html['table']        = array(
			'class' => true,
			'style' => true,
		);
		$allowed_html['thead']        = array( 'class' => true );
		$allowed_html['tbody']        = array( 'class' => true );
		$allowed_html['tr']           = array( 'class' => true );
		$allowed_html['th']           = array(
			'class' => true,
			'scope' => true,
		);
		$allowed_html['td']           = array(
			'class'   => true,
			'colspan' => true,
			'rowspan' => true,
		);
		$allowed_html['div']['class'] = true;
		$allowed_html['div']['style'] = true;

		return wp_kses( (string) $content, $allowed_html );
	}
}
