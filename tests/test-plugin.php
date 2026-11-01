<?php
/**
 * Tests for the plugin bootstrap.
 *
 * @package Foldmark
 */

/**
 * Smoke test: the plugin is loaded by the test suite.
 */
class Foldmark_Test_Plugin extends WP_UnitTestCase {

	public function test_plugin_is_loaded() {
		$this->assertSame( '1.0.0', FOLDMARK_VERSION );
	}
}
