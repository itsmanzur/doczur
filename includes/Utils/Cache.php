<?php
/**
 * Nirdeshio cache wrapper.
 *
 * @package ItsDZ\Doczur\Utils
 */

namespace ItsDZ\Doczur\Utils;

defined( 'ABSPATH' ) || exit;

/**
 * Provides object-cache reads with a persistent transient fallback.
 */
final class Cache {
	/**
	 * Cache group.
	 */
	const GROUP = 'doczur';

	/**
	 * Fetch a cached value.
	 *
	 * @param string $key Cache key.
	 * @return mixed|false
	 */
	public static function get( $key ) {
		$value = wp_cache_get( $key, self::GROUP, false, $found );

		if ( $found ) {
			return $value;
		}

		$value = get_transient( self::transient_key( $key ) );

		if ( false !== $value ) {
			wp_cache_set( $key, $value, self::GROUP );
		}

		return $value;
	}

	/**
	 * Store a cached value.
	 *
	 * @param string $key        Cache key.
	 * @param mixed  $value      Cache value.
	 * @param int    $expiration Lifetime in seconds.
	 * @return void
	 */
	public static function set( $key, $value, $expiration ) {
		wp_cache_set( $key, $value, self::GROUP, $expiration );
		set_transient( self::transient_key( $key ), $value, $expiration );
	}

	/**
	 * Return a namespace generation used for cheap bulk invalidation.
	 *
	 * @param string $cache_namespace Cache namespace.
	 * @param int    $object_id Namespace owner ID.
	 * @return int
	 */
	public static function generation( $cache_namespace, $object_id ) {
		return max( 1, (int) get_option( self::generation_key( $cache_namespace, $object_id ), 1 ) );
	}

	/**
	 * Invalidate all cache keys in an object namespace.
	 *
	 * @param string $cache_namespace Cache namespace.
	 * @param int    $object_id Namespace owner ID.
	 * @return void
	 */
	public static function bump_generation( $cache_namespace, $object_id ) {
		$key        = self::generation_key( $cache_namespace, $object_id );
		$generation = max( 1, (int) get_option( $key, 1 ) ) + 1;

		update_option( $key, $generation, false );
	}

	/**
	 * Build a bounded transient key.
	 *
	 * @param string $key Cache key.
	 * @return string
	 */
	private static function transient_key( $key ) {
		return 'itsdz_cache_' . md5( $key );
	}

	/**
	 * Build a namespace generation option key.
	 *
	 * @param string $cache_namespace Cache namespace.
	 * @param int    $object_id Namespace owner ID.
	 * @return string
	 */
	private static function generation_key( $cache_namespace, $object_id ) {
		return 'itsdz_cache_gen_' . sanitize_key( $cache_namespace ) . '_' . absint( $object_id );
	}
}
