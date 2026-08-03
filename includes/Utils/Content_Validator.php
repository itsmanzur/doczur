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
}
