<?php
/**
 * PHPUnit bootstrap for isolated tests.
 *
 * WordPress integration-suite bootstrapping is introduced with Phase 1.
 *
 * @package ItsDZ\Doczur\Tests
 */

$itsdz_autoloader = dirname( __DIR__ ) . '/vendor/autoload.php';

if ( ! is_readable( $itsdz_autoloader ) ) {
	throw new RuntimeException( 'Composer dependencies are missing. Run composer install.' );
}

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', dirname( __DIR__ ) . '/' );
}

require_once $itsdz_autoloader;

require_once __DIR__ . '/stubs/WP_REST_Controller.php';
require_once __DIR__ . '/stubs/WP_Term.php';

if ( ! function_exists( '__' ) ) {
	/**
	 * Test translation stub.
	 *
	 * @param string $text Source text.
	 * @return string
	 */
	function __( $text ) {
		return $text;
	}
}

if ( ! function_exists( '_x' ) ) {
	/**
	 * Test contextual translation stub.
	 *
	 * @param string $text Source text.
	 * @return string
	 */
	function _x( $text ) {
		return $text;
	}
}

if ( ! function_exists( 'register_post_type' ) ) {
	/**
	 * Capture test post type registrations.
	 *
	 * @param string               $post_type Post type key.
	 * @param array<string, mixed> $args      Registration arguments.
	 * @return void
	 */
	function register_post_type( $post_type, $args ) {
		$GLOBALS['itsdz_test_post_types'][ $post_type ] = $args;
	}
}

if ( ! function_exists( 'add_rewrite_rule' ) ) {
	/**
	 * Capture test rewrite rules.
	 *
	 * @param string $regex    Route expression.
	 * @param string $query    Route query.
	 * @param string $position Rule position.
	 * @return void
	 */
	function add_rewrite_rule( $regex, $query, $position ) {
		$GLOBALS['itsdz_test_rewrite_rules'][] = array(
			'regex'    => $regex,
			'query'    => $query,
			'position' => $position,
		);
	}
}

if ( ! function_exists( 'register_taxonomy' ) ) {
	/**
	 * Capture test taxonomy registrations.
	 *
	 * @param string               $taxonomy   Taxonomy key.
	 * @param string[]             $post_types Object types.
	 * @param array<string, mixed> $args       Registration arguments.
	 * @return void
	 */
	function register_taxonomy( $taxonomy, $post_types, $args ) {
		$GLOBALS['itsdz_test_taxonomies'][ $taxonomy ] = array(
			'post_types' => $post_types,
			'args'       => $args,
		);
	}
}

if ( ! function_exists( 'register_post_meta' ) ) {
	/**
	 * Capture test post meta registrations.
	 *
	 * @param string               $post_type Post type key.
	 * @param string               $meta_key  Meta key.
	 * @param array<string, mixed> $args      Registration arguments.
	 * @return void
	 */
	function register_post_meta( $post_type, $meta_key, $args ) {
		$GLOBALS['itsdz_test_post_meta'][ $post_type ][ $meta_key ] = $args;
	}
}

if ( ! function_exists( 'dbDelta' ) ) {
	/**
	 * Capture migration statements in isolated tests.
	 *
	 * @param string $query Schema statement.
	 * @return array<int, string>
	 */
	function dbDelta( $query ) { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.FunctionNameInvalid
		$GLOBALS['itsdz_test_dbdelta'][] = $query;

		return array();
	}
}

if ( ! function_exists( 'get_post' ) ) {
	/**
	 * Resolve an isolated test post.
	 *
	 * @param int $post_id Post ID.
	 * @return object|null
	 */
	function get_post( $post_id ) {
		return $GLOBALS['itsdz_test_posts'][ $post_id ] ?? null;
	}
}

if ( ! function_exists( 'get_term' ) ) {
	/**
	 * Resolve an isolated test term.
	 *
	 * @param int    $term_id  Term ID.
	 * @param string $taxonomy Taxonomy key.
	 * @return object|null
	 */
	function get_term( $term_id, $taxonomy ) {
		unset( $taxonomy );

		return $GLOBALS['itsdz_test_terms'][ $term_id ] ?? null;
	}
}

if ( ! function_exists( 'get_option' ) ) {
	/**
	 * Resolve an isolated test option.
	 *
	 * @param string $option        Option name.
	 * @param mixed  $default_value Default value.
	 * @return mixed
	 */
	function get_option( $option, $default_value = false ) {
		if ( ! isset( $GLOBALS['itsdz_test_options'] ) || ! is_array( $GLOBALS['itsdz_test_options'] ) ) {
			return $default_value;
		}

		return $GLOBALS['itsdz_test_options'][ $option ] ?? $default_value;
	}
}

if ( ! function_exists( 'update_option' ) ) {
	/**
	 * Store an isolated test option.
	 *
	 * @param string $option   Option name.
	 * @param mixed  $value    Option value.
	 * @param mixed  $autoload Autoload flag (unused).
	 * @return bool
	 */
	function update_option( $option, $value, $autoload = true ) {
		unset( $autoload );

		if ( ! isset( $GLOBALS['itsdz_test_options'] ) || ! is_array( $GLOBALS['itsdz_test_options'] ) ) {
			$GLOBALS['itsdz_test_options'] = array();
		}

		$GLOBALS['itsdz_test_options'][ $option ] = $value;

		return true;
	}
}

if ( ! function_exists( 'delete_option' ) ) {
	/**
	 * Delete an isolated test option.
	 *
	 * @param string $option Option name.
	 * @return bool
	 */
	function delete_option( $option ) {
		unset( $GLOBALS['itsdz_test_options'][ $option ] );

		return true;
	}
}

if ( ! function_exists( 'apply_filters' ) ) {
	/**
	 * Pass-through filter stub.
	 *
	 * @param string $hook  Filter hook.
	 * @param mixed  $value Filtered value.
	 * @return mixed
	 */
	function apply_filters( $hook, $value ) {
		unset( $hook );

		return $value;
	}
}

if ( ! function_exists( 'flush_rewrite_rules' ) ) {
	/**
	 * Record rewrite flushes in isolated tests.
	 *
	 * @param bool $hard Whether to update .htaccess (unused).
	 * @return void
	 */
	function flush_rewrite_rules( $hard = true ) {
		unset( $hard );
		$GLOBALS['itsdz_test_rewrite_flushed'] = true;
	}
}

if ( ! function_exists( 'get_post_meta' ) ) {
	/**
	 * Resolve isolated test post meta.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $key     Meta key.
	 * @param bool   $single  Whether to return a single value (unused).
	 * @return mixed
	 */
	function get_post_meta( $post_id, $key, $single = true ) {
		unset( $single );

		return $GLOBALS['itsdz_test_post_meta_values'][ $post_id ][ $key ] ?? '';
	}
}

if ( ! function_exists( 'get_the_title' ) ) {
	/**
	 * Return an isolated test title.
	 *
	 * @param object $post Post object.
	 * @return string
	 */
	function get_the_title( $post ) {
		return $post->post_title;
	}
}

if ( ! function_exists( 'get_permalink' ) ) {
	/**
	 * Return an isolated test permalink.
	 *
	 * @param object $post Post object.
	 * @return string
	 */
	function get_permalink( $post ) {
		return 'https://example.test/docs/' . $post->ID;
	}
}

if ( ! function_exists( 'wp_strip_all_tags' ) ) {
	/**
	 * Strip markup in isolated tests.
	 *
	 * @param string $text Source text.
	 * @return string
	 */
	function wp_strip_all_tags( $text ) {
		return strip_tags( $text ); // phpcs:ignore WordPress.WP.AlternativeFunctions.strip_tags_strip_tags
	}
}

if ( ! function_exists( 'absint' ) ) {
	/**
	 * Cast a positive integer in isolated tests.
	 *
	 * @param mixed $value Source value.
	 * @return int
	 */
	function absint( $value ) {
		return abs( (int) $value );
	}
}

if ( ! function_exists( 'sanitize_title' ) ) {
	/**
	 * Create a basic slug in isolated tests.
	 *
	 * @param string $title Source title.
	 * @return string
	 */
	function sanitize_title( $title ) {
		return trim( strtolower( preg_replace( '/[^a-z0-9]+/i', '-', $title ) ), '-' );
	}
}

if ( ! function_exists( 'sanitize_text_field' ) ) {
	/**
	 * Sanitize isolated test text.
	 *
	 * @param mixed $text Source text.
	 * @return string
	 */
	function sanitize_text_field( $text ) {
		return trim( strip_tags( (string) $text ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.strip_tags_strip_tags
	}
}

if ( ! function_exists( 'wp_json_encode' ) ) {
	/**
	 * Encode isolated test data.
	 *
	 * @param mixed $value Source value.
	 * @return string|false
	 */
	function wp_json_encode( $value ) {
		return json_encode( $value ); // phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode
	}
}

if ( ! function_exists( 'wp_trim_words' ) ) {
	/**
	 * Trim isolated test content.
	 *
	 * @param string $text      Source text.
	 * @param int    $num_words Word limit.
	 * @return string
	 */
	function wp_trim_words( $text, $num_words ) {
		return implode( ' ', array_slice( preg_split( '/\s+/', trim( $text ) ), 0, $num_words ) );
	}
}
