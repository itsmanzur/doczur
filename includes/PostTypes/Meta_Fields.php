<?php
/**
 * Doczur content meta registration.
 *
 * @package ItsDZ\Doczur\PostTypes
 */

namespace ItsDZ\Doczur\PostTypes;

use ItsDZ\Doczur\Core\Service;
use ItsDZ\Doczur\Security\Capabilities;

defined( 'ABSPATH' ) || exit;

/**
 * Registers REST-aware post meta with strict sanitization.
 */
final class Meta_Fields implements Service {
	/**
	 * Register WordPress hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'init', array( $this, 'register_meta' ), 6 );
	}

	/**
	 * Register all Phase 1 post meta.
	 *
	 * @return void
	 */
	public function register_meta() {
		$this->register_kb_meta();
		$this->register_article_meta();
	}

	/**
	 * Register documentation project meta.
	 *
	 * @return void
	 */
	private function register_kb_meta() {
		$this->register_field( KB_Post_Type::POST_TYPE, '_itsdz_kb_logo', 'integer', 'absint', 0 );
		$this->register_field( KB_Post_Type::POST_TYPE, '_itsdz_kb_brand_color', 'string', 'sanitize_hex_color', '' );
		$this->register_field( KB_Post_Type::POST_TYPE, '_itsdz_kb_theme_mode', 'string', array( $this, 'sanitize_theme_mode' ), 'system' );
		$this->register_field( KB_Post_Type::POST_TYPE, '_itsdz_kb_template', 'string', array( $this, 'sanitize_template' ), 'clean' );
		$this->register_field( KB_Post_Type::POST_TYPE, '_itsdz_kb_doc_type', 'string', array( $this, 'sanitize_doc_type' ), 'blank' );
		$this->register_field( KB_Post_Type::POST_TYPE, '_itsdz_kb_slug_base', 'string', array( $this, 'sanitize_slug' ), '' );
		$this->register_field( KB_Post_Type::POST_TYPE, '_itsdz_kb_layout_mode', 'string', array( $this, 'sanitize_layout_mode' ), 'canvas' );
		$this->register_field( KB_Post_Type::POST_TYPE, '_itsdz_kb_active_version', 'integer', 'absint', 0 );
	}

	/**
	 * Register documentation article meta.
	 *
	 * @return void
	 */
	private function register_article_meta() {
		$this->register_field( Article_Post_Type::POST_TYPE, '_itsdz_kb_id', 'integer', 'absint', 0 );
		$this->register_field( Article_Post_Type::POST_TYPE, '_itsdz_reading_time', 'integer', 'absint', 0 );
		$this->register_field( Article_Post_Type::POST_TYPE, '_itsdz_last_reviewed', 'string', array( $this, 'sanitize_date' ), '' );
		$this->register_field( Article_Post_Type::POST_TYPE, '_itsdz_owner', 'integer', 'absint', 0 );
	}

	/**
	 * Register a single protected field.
	 *
	 * @param string   $post_type        Post type key.
	 * @param string   $meta_key         Meta key.
	 * @param string   $type             REST scalar type.
	 * @param callable $sanitize_callback Sanitization callback.
	 * @param mixed    $default_value    Default value.
	 * @return void
	 */
	private function register_field( $post_type, $meta_key, $type, $sanitize_callback, $default_value ) {
		register_post_meta(
			$post_type,
			$meta_key,
			array(
				'auth_callback'     => array( $this, 'can_edit_meta' ),
				'default'           => $default_value,
				'sanitize_callback' => $sanitize_callback,
				'show_in_rest'      => true,
				'single'            => true,
				'type'              => $type,
			)
		);
	}

	/**
	 * Authorize protected meta writes.
	 *
	 * @return bool
	 */
	public function can_edit_meta() {
		return current_user_can( Capabilities::MANAGE_DOCS );
	}

	/**
	 * Sanitize theme mode.
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	public function sanitize_theme_mode( $value ) {
		return $this->sanitize_choice( $value, array( 'light', 'dark', 'system' ), 'system' );
	}

	/**
	 * Sanitize template choice.
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	public function sanitize_template( $value ) {
		return $this->sanitize_choice( $value, array( 'clean', 'modern', 'compact' ), 'clean' );
	}

	/**
	 * Sanitize documentation type.
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	public function sanitize_doc_type( $value ) {
		$choices = array( 'software', 'wp-plugin', 'wp-theme', 'physical-product', 'internal', 'blank' );

		return $this->sanitize_choice( $value, $choices, 'blank' );
	}

	/**
	 * Sanitize a documentation URL slug without using the meta key as fallback.
	 *
	 * WordPress passes the meta key as the second sanitizer argument. Calling
	 * sanitize_title directly would treat that key as the empty-title fallback.
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	public function sanitize_slug( $value ) {
		if ( ! is_scalar( $value ) ) {
			return '';
		}

		return sanitize_title( (string) $value );
	}

	/**
	 * Sanitize the public template shell mode.
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	public function sanitize_layout_mode( $value ) {
		return $this->sanitize_choice( $value, array( 'canvas', 'theme' ), 'canvas' );
	}

	/**
	 * Keep valid ISO dates only.
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	public function sanitize_date( $value ) {
		if ( ! is_scalar( $value ) ) {
			return '';
		}

		$value = sanitize_text_field( $value );

		if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $value ) ) {
			return '';
		}

		$date = \DateTimeImmutable::createFromFormat( '!Y-m-d', $value );

		return $date && $date->format( 'Y-m-d' ) === $value ? $value : '';
	}

	/**
	 * Sanitize an allowlisted string.
	 *
	 * @param mixed    $value    Raw value.
	 * @param string[] $choices  Allowed values.
	 * @param string   $fallback Fallback value.
	 * @return string
	 */
	private function sanitize_choice( $value, $choices, $fallback ) {
		if ( ! is_scalar( $value ) ) {
			return $fallback;
		}

		$value = sanitize_key( $value );

		return in_array( $value, $choices, true ) ? $value : $fallback;
	}
}
