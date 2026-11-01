<?php
/**
 * Tests for the block tree walker.
 *
 * @package Foldmark
 */

/**
 * Foldmark_Block_Walker follows synced patterns within a bounded budget.
 */
class Foldmark_Test_Block_Walker extends WP_UnitTestCase {

	/**
	 * Creates a published synced pattern.
	 *
	 * @param string $content Pattern content.
	 * @return int
	 */
	private function pattern( $content ) {
		return self::factory()->post->create(
			array(
				'post_type'    => 'wp_block',
				'post_status'  => 'publish',
				'post_content' => $content,
			)
		);
	}

	public function test_diamond_of_synced_patterns_is_bounded() {
		// Twelve levels, each including the next one twice: 4096 expansions without a budget.
		$id = $this->pattern( Foldmark_Fixtures::delivery_faq() );
		for ( $level = 0; $level < 12; $level++ ) {
			$id = $this->pattern( Foldmark_Fixtures::synced( $id ) . Foldmark_Fixtures::synced( $id ) );
		}

		$walker = new Foldmark_Block_Walker();
		$walker->walk(
			parse_blocks( Foldmark_Fixtures::synced( $id ) ),
			static function () {
				return true;
			}
		);

		$this->assertLessThanOrEqual( Foldmark_Block_Walker::MAX_EXPANSIONS, $walker->expansions() );
	}

	public function test_pattern_is_parsed_once_per_walker() {
		$id     = $this->pattern( Foldmark_Fixtures::delivery_faq() );
		$blocks = parse_blocks( str_repeat( Foldmark_Fixtures::synced( $id ), 3 ) );

		$parses = 0;
		add_filter(
			'block_parser_class',
			static function ( $class_name ) use ( &$parses ) {
				++$parses;
				return $class_name;
			}
		);

		$visited = 0;
		( new Foldmark_Block_Walker() )->walk(
			$blocks,
			static function ( $block ) use ( &$visited ) {
				if ( 'core/accordion' === $block['blockName'] ) {
					++$visited;
				}
				return true;
			}
		);

		$this->assertSame( 1, $parses );
		$this->assertSame( 3, $visited );
	}

	public function test_visitor_can_stop_descent() {
		$seen = array();
		( new Foldmark_Block_Walker() )->walk(
			parse_blocks( Foldmark_Fixtures::group( Foldmark_Fixtures::delivery_faq() ) ),
			static function ( $block ) use ( &$seen ) {
				$seen[] = $block['blockName'];
				return 'core/accordion' !== $block['blockName'];
			}
		);

		$this->assertSame( array( 'core/group', 'core/accordion' ), $seen );
	}
}
