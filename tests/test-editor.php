<?php
/**
 * Tests for the block attribute and the block variations.
 *
 * @package Foldmark
 */

/**
 * The accordion block carries the attribute and the two variations.
 */
class Foldmark_Test_Editor extends WP_UnitTestCase {

	public function test_accordion_declares_object_attribute() {
		$type = WP_Block_Type_Registry::get_instance()->get_registered( 'core/accordion' );
		$this->assertSame( array( 'type' => 'object' ), $type->attributes['foldmark'] );
	}

	public function test_faq_variation_is_preconfigured() {
		$v = $this->variation( 'foldmark/faq' );
		$this->assertSame( array( 'inserter', 'transform' ), $v['scope'] );
		$this->assertSame( array( 'foldmark' => array( 'type' => 'faq' ) ), $v['attributes'] );
		$this->assertSame( array( 'foldmark.type' ), $v['isActive'] );
		$this->assertCount( 3, $v['innerBlocks'] );
		$this->assertSame( 'core/accordion-item', $v['innerBlocks'][0][0] );
		$this->assertSame( array( 'core/accordion-heading', 'core/accordion-panel' ), wp_list_pluck( $v['innerBlocks'][0][2], 0 ) );
	}

	public function test_plain_variation_resets_attribute() {
		$v = $this->variation( 'foldmark/accordion' );
		$this->assertSame( array( 'transform' ), $v['scope'] );
		$this->assertSame( array( 'foldmark' => null ), $v['attributes'] );
	}

	public function test_other_blocks_are_untouched() {
		$type = WP_Block_Type_Registry::get_instance()->get_registered( 'core/details' );
		$this->assertArrayNotHasKey( 'foldmark', $type->attributes );
		$this->assertEmpty( wp_list_filter( $type->get_variations(), array( 'name' => 'foldmark/faq' ) ) );
	}

	/**
	 * Finds a variation of core/accordion by name.
	 *
	 * @param string $name Variation name.
	 * @return array The variation, or an empty array.
	 */
	private function variation( $name ) {
		$type  = WP_Block_Type_Registry::get_instance()->get_registered( 'core/accordion' );
		$found = wp_list_filter( $type->get_variations(), array( 'name' => $name ) );
		$this->assertCount( 1, $found, "Variation $name is not registered." );
		return array_values( $found )[0];
	}
}
