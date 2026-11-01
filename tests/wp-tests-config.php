<?php
/**
 * WordPress test suite configuration, driven by environment variables.
 *
 * WP_CORE_DIR       Path to a WordPress install whose files the suite loads.
 * WP_TESTS_DB_HOST  Database host, with its port (default 127.0.0.1).
 * WP_TESTS_DB_NAME  Database name (default foldmark_tests). It is wiped on every run.
 * WP_TESTS_DB_USER  Database user (default db).
 * WP_TESTS_DB_PASS  Database password (default db).
 *
 * @package Foldmark
 */

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- WordPress test suite constants.

$foldmark_core_dir = getenv( 'WP_CORE_DIR' );
if ( ! $foldmark_core_dir ) {
	fwrite( STDERR, "WP_CORE_DIR is not set. See CONTRIBUTING.md.\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite
	exit( 1 );
}

define( 'ABSPATH', rtrim( $foldmark_core_dir, '/' ) . '/' );

define( 'DB_NAME', getenv( 'WP_TESTS_DB_NAME' ) ? getenv( 'WP_TESTS_DB_NAME' ) : 'foldmark_tests' );
define( 'DB_USER', getenv( 'WP_TESTS_DB_USER' ) ? getenv( 'WP_TESTS_DB_USER' ) : 'db' );
define( 'DB_PASSWORD', getenv( 'WP_TESTS_DB_PASS' ) ? getenv( 'WP_TESTS_DB_PASS' ) : 'db' );
define( 'DB_HOST', getenv( 'WP_TESTS_DB_HOST' ) ? getenv( 'WP_TESTS_DB_HOST' ) : '127.0.0.1' );
define( 'DB_CHARSET', 'utf8mb4' );
define( 'DB_COLLATE', '' );

define( 'WP_TESTS_DOMAIN', 'example.org' );
define( 'WP_TESTS_EMAIL', 'admin@example.org' );
define( 'WP_TESTS_TITLE', 'Foldmark Tests' );
define( 'WP_PHP_BINARY', 'php' );
define( 'WPLANG', '' );
define( 'WP_DEBUG', true );
