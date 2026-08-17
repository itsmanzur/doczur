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
		$this->register_field( KB_Post_Type::POST_TYPE, '_itsdz_kb_nav_style', 'string', array( $this, 'sanitize_nav_style' ), 'accordion' );
		$this->register_field( KB_Post_Type::POST_TYPE, '_itsdz_kb_show_toc', 'string', array( $this, 'sanitize_on_off' ), '1' );
		$this->register_field( KB_Post_Type::POST_TYPE, '_itsdz_kb_show_feedback', 'string', array( $this, 'sanitize_on_off' ), '1' );
		$this->register_field( KB_Post_Type::POST_TYPE, '_itsdz_kb_show_related', 'string', array( $this, 'sanitize_on_off' ), '1' );
		$this->register_field( KB_Post_Type::POST_TYPE, '_itsdz_kb_show_print', 'string', array( $this, 'sanitize_on_off' ), '1' );
		$this->register_field( KB_Post_Type::POST_TYPE, '_itsdz_kb_custom_css', 'string', array( $this, 'sanitize_custom_css' ), '' );
		$this->register_field( KB_Post_Type::POST_TYPE, '_itsdz_kb_header_links', 'string', array( $this, 'sanitize_header_links' ), '[]' );
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
	 * Sanitize the public left-nav skin.
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	public function sanitize_nav_style( $value ) {
		return $this->sanitize_choice( $value, array( 'accordion', 'rail', 'line', 'tree' ), 'accordion' );
	}

	/**
	 * Sanitize an on/off toggle stored as "1" or "0".
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	public function sanitize_on_off( $value ) {
		if ( is_bool( $value ) ) {
			return $value ? '1' : '0';
		}

		if ( ! is_scalar( $value ) ) {
			return '1';
		}

		$normalized = strtolower( (string) $value );

		if ( in_array( $normalized, array( '0', 'false', 'off', 'no' ), true ) ) {
			return '0';
		}

		return '1';
	}

	/**
	 * Strip executable bits from a custom CSS snippet.
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	public function sanitize_custom_css( $value ) {
		if ( ! is_scalar( $value ) ) {
			return '';
		}

		$css = wp_strip_all_tags( (string) $value );
		$css = (string) preg_replace( '/expression\s*\(/i', '', $css );
		$css = (string) preg_replace( '/javascript\s*:/i', '', $css );
		$css = (string) preg_replace( '/@import/i', '', $css );
		$css = (string) preg_replace( '/behavior\s*:/i', '', $css );
		$css = (string) preg_replace( '/-moz-binding/i', '', $css );

		return substr( $css, 0, 8000 );
	}

	/**
	 * Keep a small list of labelled header links.
	 *
	 * @param mixed $value Raw JSON string or array.
	 * @return string JSON object list.
	 */
	public function sanitize_header_links( $value ) {
		if ( is_string( $value ) ) {
			$decoded = json_decode( $value, true );
		} elseif ( is_array( $value ) ) {
			$decoded = $value;
		} else {
			return '[]';
		}

		if ( ! is_array( $decoded ) ) {
			return '[]';
		}

		$links = array();

		foreach ( array_slice( $decoded, 0, 4 ) as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}

			$label = isset( $item['label'] ) ? sanitize_text_field( (string) $item['label'] ) : '';
			$url   = isset( $item['url'] ) ? esc_url_raw( (string) $item['url'] ) : '';

			if ( '' === $label || '' === $url ) {
				continue;
			}

			$links[] = array(
				'label' => $label,
				'url'   => $url,
			);
		}

		$encoded = wp_json_encode( $links );

		return is_string( $encoded ) ? $encoded : '[]';
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
