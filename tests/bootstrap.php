<?php
/**
 * PHPUnit bootstrap: loads the WordPress test suite with Foldmark active.
 *
 * @package Foldmark
 */

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- WordPress test suite constants.

require_once dirname( __DIR__ ) . '/vendor/autoload.php';

// Read by vendor/wp-phpunit/wp-phpunit/wp-tests-config.php, here and in the install subprocess.
putenv( 'WP_PHPUNIT__TESTS_CONFIG=' . __DIR__ . '/wp-tests-config.php' ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.runtime_configuration_putenv -- Test bootstrap only, never shipped.
define( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH', dirname( __DIR__ ) . '/vendor/yoast/phpunit-polyfills' );

$foldmark_tests_dir = getenv( 'WP_PHPUNIT__DIR' );

require_once $foldmark_tests_dir . '/includes/functions.php';

tests_add_filter(
	'muplugins_loaded',
	static function () {
		require dirname( __DIR__ ) . '/foldmark.php';
	}
);

require $foldmark_tests_dir . '/includes/bootstrap.php';
