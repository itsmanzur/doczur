<?php
/**
 * WordPress integration test configuration.
 *
 * DANGER: the WordPress test suite DROPS EVERY TABLE in the database named
 * below, on every run. It must never point at a database that holds real
 * content. The guard at the bottom of this file enforces that.
 *
 * Every value can be overridden with an environment variable so this file
 * stays free of machine-specific paths.
 *
 * @package ItsDZ\Doczur\Tests
 */

/**
 * Read a configuration value from the environment.
 *
 * @param string $name     Environment variable name.
 * @param string $fallback Value to use when the variable is unset.
 * @return string
 */
function itsdz_tests_env( $name, $fallback ) {
	$value = getenv( $name );

	return ( false === $value || '' === $value ) ? $fallback : $value;
}

// The plugin lives in wp-content/plugins/doczur, so WordPress core sits four
// levels up. Derived rather than hard-coded so any checkout works unchanged.
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', dirname( __DIR__, 4 ) . '/' );
}

define( 'DB_NAME', itsdz_tests_env( 'WP_TESTS_DB_NAME', 'doczur_tests' ) );
define( 'DB_USER', itsdz_tests_env( 'WP_TESTS_DB_USER', 'root' ) );
define( 'DB_PASSWORD', itsdz_tests_env( 'WP_TESTS_DB_PASSWORD', 'root' ) );
define( 'DB_HOST', itsdz_tests_env( 'WP_TESTS_DB_HOST', '127.0.0.1:10114' ) );
define( 'DB_CHARSET', 'utf8mb4' );
define( 'DB_COLLATE', '' );

$table_prefix = itsdz_tests_env( 'WP_TESTS_TABLE_PREFIX', 'wptests_' ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

define( 'WP_TESTS_DOMAIN', 'doczur.test' );
define( 'WP_TESTS_EMAIL', 'admin@doczur.test' );
define( 'WP_TESTS_TITLE', 'Nirdeshio Integration Tests' );
define( 'WP_PHP_BINARY', 'php' );

define( 'WP_DEBUG', true );
define( 'WP_DEBUG_DISPLAY', true );

/*
 * Safety net.
 *
 * `local` is the database Local uses for the real site this plugin is
 * developed against. Pointing the test suite there would delete the site.
 */
$itsdz_forbidden_databases = array( 'local', 'wordpress', 'wp' );

if ( in_array( strtolower( DB_NAME ), $itsdz_forbidden_databases, true ) ) {
	// phpcs:disable WordPress.Security.EscapeOutput.ExceptionNotEscaped -- CLI-only bootstrap message, never rendered as HTML.
	throw new RuntimeException(
		sprintf(
			'Refusing to run integration tests against the "%s" database: the WordPress test suite drops all of its tables. Set WP_TESTS_DB_NAME to a dedicated test database.',
			DB_NAME
		)
	);
	// phpcs:enable WordPress.Security.EscapeOutput.ExceptionNotEscaped
}
