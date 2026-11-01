<?php
/**
 * Walks a parsed block tree the way core renders it.
 *
 * @package Foldmark
 */

defined( 'ABSPATH' ) || exit;

/**
 * Generic traversal of the blocks returned by parse_blocks().
 *
 * It follows the rendering rules of core so that structured data never
 * describes content the page does not show: hidden blocks are skipped, and
 * synced patterns are followed only when published and not password protected.
 * Works on saved markup only, nothing is rendered.
 *
 * One instance serves one post: it caches the patterns it parses and caps the
 * number of pattern expansions, so content built from nested patterns cannot
 * make a page slow.
 */
final class Foldmark_Block_Walker {

	/**
	 * Maximum number of synced pattern expansions for one walker.
	 */
	const MAX_EXPANSIONS = 100;

	/**
	 * IDs of the synced patterns on the current path, to stop cycles.
	 *
	 * @var int[]
	 */
	private $stack = array();

	/**
	 * Parsed blocks of the synced patterns already read, keyed by ID.
	 *
	 * @var array[]
	 */
	private $parsed = array();

	/**
	 * Number of pattern expansions so far.
	 *
	 * @var int
	 */
	private $expansions = 0;

	/**
	 * Visits every visible block, depth first, following synced patterns.
	 *
	 * Pattern references themselves are never passed to the visitor, only the
	 * blocks they contain.
	 *
	 * @param array    $blocks  Blocks returned by parse_blocks().
	 * @param callable $visitor Receives each block. Return false to skip its inner blocks.
	 * @return void
	 */
	public function walk( array $blocks, callable $visitor ) {
		foreach ( $blocks as $block ) {
			if ( ! is_array( $block ) || empty( $block['blockName'] ) || self::is_hidden( $block ) ) {
				continue;
			}

			if ( 'core/block' === $block['blockName'] ) {
				$id = $this->enter_pattern( $block );
				if ( $id ) {
					$this->walk( $this->pattern_blocks( $id ), $visitor );
					array_pop( $this->stack );
				}
				continue;
			}

			if ( false !== $visitor( $block ) ) {
				$this->walk( $this->children( $block ), $visitor );
			}
		}
	}

	/**
	 * Tells whether one of the named blocks is displayed, synced patterns included.
	 *
	 * @param array    $blocks Blocks returned by parse_blocks().
	 * @param string[] $names  Block names.
	 * @return bool
	 */
	public function contains( array $blocks, array $names ) {
		$found = false;

		$this->walk(
			$blocks,
			static function ( $block ) use ( $names, &$found ) {
				if ( in_array( $block['blockName'], $names, true ) ) {
					$found = true;
				}
				return ! $found;
			}
		);

		return $found;
	}

	/**
	 * Returns the visible, well-formed inner blocks of a block. Patterns are not resolved.
	 *
	 * @param array $block Block.
	 * @return array
	 */
	public function children( array $block ) {
		if ( empty( $block['innerBlocks'] ) || ! is_array( $block['innerBlocks'] ) ) {
			return array();
		}

		return array_values(
			array_filter(
				$block['innerBlocks'],
				static function ( $child ) {
					return is_array( $child ) && ! empty( $child['blockName'] ) && ! self::is_hidden( $child );
				}
			)
		);
	}

	/**
	 * Returns the saved HTML of a visible block and its inner blocks, patterns included.
	 *
	 * Dynamic blocks without saved markup contribute nothing.
	 *
	 * @param array $block Block.
	 * @return string
	 */
	public function static_html( array $block ) {
		if ( self::is_hidden( $block ) ) {
			return '';
		}

		if ( isset( $block['blockName'] ) && 'core/block' === $block['blockName'] ) {
			$html = '';
			$id   = $this->enter_pattern( $block );
			if ( $id ) {
				foreach ( $this->pattern_blocks( $id ) as $inner ) {
					$html .= is_array( $inner ) ? $this->static_html( $inner ) : '';
				}
				array_pop( $this->stack );
			}
			return $html;
		}

		// Every null in innerContent matches one inner block, freeform ones included.
		$html     = '';
		$children = isset( $block['innerBlocks'] ) && is_array( $block['innerBlocks'] ) ? array_values( $block['innerBlocks'] ) : array();
		$index    = 0;

		foreach ( isset( $block['innerContent'] ) ? (array) $block['innerContent'] : array() as $chunk ) {
			if ( is_string( $chunk ) ) {
				$html .= $chunk;
			} elseif ( isset( $children[ $index ] ) ) {
				$html .= is_array( $children[ $index ] ) ? $this->static_html( $children[ $index ] ) : '';
				++$index;
			}
		}

		return $html;
	}

	/**
	 * Returns the number of synced pattern expansions made so far.
	 *
	 * @return int
	 */
	public function expansions() {
		return $this->expansions;
	}

	/**
	 * Tells whether a block is hidden with the block visibility control.
	 *
	 * Same test as wp_render_block_visibility_support() (WordPress 6.9): core
	 * renders nothing for such a block. Hiding on some viewports only leaves the
	 * content in the page and is ignored.
	 *
	 * @param array $block Block.
	 * @return bool
	 */
	public static function is_hidden( array $block ) {
		return isset( $block['attrs']['metadata']['blockVisibility'] ) && false === $block['attrs']['metadata']['blockVisibility'];
	}

	/**
	 * Pushes a synced pattern on the path when core would render it and the budget allows.
	 *
	 * @param array $block core/block block.
	 * @return int The pattern ID, or 0 when it must not be followed.
	 */
	private function enter_pattern( array $block ) {
		$ref = isset( $block['attrs']['ref'] ) ? $block['attrs']['ref'] : 0;
		if ( ! is_numeric( $ref ) || $this->expansions >= self::MAX_EXPANSIONS ) {
			return 0;
		}

		$id = (int) $ref;
		if ( $id <= 0 || in_array( $id, $this->stack, true ) ) {
			return 0;
		}

		// Same conditions as render_block_core_block(): never read what core would not display.
		$post = get_post( $id );
		if ( ! $post || 'wp_block' !== $post->post_type || 'publish' !== $post->post_status || ! empty( $post->post_password ) ) {
			return 0;
		}

		++$this->expansions;
		$this->stack[] = $id;
		return $id;
	}

	/**
	 * Returns the parsed blocks of a synced pattern, parsing it once per walker.
	 *
	 * @param int $id Pattern ID, already checked by enter_pattern().
	 * @return array
	 */
	private function pattern_blocks( $id ) {
		if ( ! isset( $this->parsed[ $id ] ) ) {
			$this->parsed[ $id ] = parse_blocks( get_post_field( 'post_content', $id, 'raw' ) );
		}
		return $this->parsed[ $id ];
	}
}
