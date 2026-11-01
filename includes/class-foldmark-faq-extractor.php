<?php
/**
 * Reads FAQ accordions out of a parsed block tree.
 *
 * @package Foldmark
 */

defined( 'ABSPATH' ) || exit;

/**
 * Turns the blocks returned by parse_blocks() into question and answer pairs.
 *
 * Works on saved markup only: nothing is rendered, so reading a post twice has
 * no side effect. Synced patterns are followed, with a guard against patterns
 * that include themselves.
 */
class Foldmark_Faq_Extractor {

	/**
	 * Returns the question and answer pairs of every FAQ accordion, in document order.
	 *
	 * The question is plain text. The answer is the saved HTML of the panel
	 * blocks, not yet sanitized. Pairs may be empty: filtering is left to the caller.
	 *
	 * @param array $blocks Blocks returned by parse_blocks().
	 * @return array[] List of array( 'question' => string, 'answer' => string ).
	 */
	public static function extract( $blocks ) {
		$pairs = array();
		$stack = array();
		self::collect( $blocks, $pairs, $stack );
		return $pairs;
	}

	/**
	 * Tells whether one of the named blocks appears anywhere, synced patterns included.
	 *
	 * @param array    $blocks Blocks returned by parse_blocks().
	 * @param string[] $names  Block names to look for.
	 * @return bool
	 */
	public static function contains_block( $blocks, $names ) {
		$stack = array();
		return self::find( $blocks, (array) $names, $stack );
	}

	/**
	 * Walks the tree and collects pairs.
	 *
	 * @param array $blocks Blocks.
	 * @param array $pairs  Collected pairs.
	 * @param int[] $stack  IDs of the synced patterns being walked.
	 * @return void
	 */
	private static function collect( $blocks, &$pairs, &$stack ) {
		foreach ( (array) $blocks as $block ) {
			if ( ! is_array( $block ) || empty( $block['blockName'] ) ) {
				continue;
			}

			if ( 'core/block' === $block['blockName'] ) {
				$id = self::enter_pattern( $block, $stack );
				if ( $id ) {
					self::collect( self::pattern_blocks( $id ), $pairs, $stack );
					array_pop( $stack );
				}
				continue;
			}

			if ( Foldmark_Editor::BLOCK === $block['blockName'] && self::is_faq( $block ) ) {
				foreach ( self::children( $block ) as $item ) {
					if ( 'core/accordion-item' === $item['blockName'] ) {
						$pairs[] = self::pair( $item, $stack );
					}
				}
				// A FAQ nested in an answer stays part of that answer.
				continue;
			}

			self::collect( self::children( $block ), $pairs, $stack );
		}
	}

	/**
	 * Walks the tree looking for a block name.
	 *
	 * @param array    $blocks Blocks.
	 * @param string[] $names  Block names.
	 * @param int[]    $stack  IDs of the synced patterns being walked.
	 * @return bool
	 */
	private static function find( $blocks, $names, &$stack ) {
		foreach ( (array) $blocks as $block ) {
			if ( ! is_array( $block ) || empty( $block['blockName'] ) ) {
				continue;
			}

			if ( in_array( $block['blockName'], $names, true ) ) {
				return true;
			}

			if ( 'core/block' === $block['blockName'] ) {
				$id = self::enter_pattern( $block, $stack );
				if ( $id ) {
					$found = self::find( self::pattern_blocks( $id ), $names, $stack );
					array_pop( $stack );
					if ( $found ) {
						return true;
					}
				}
				continue;
			}

			if ( self::find( self::children( $block ), $names, $stack ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Tells whether an accordion is marked as an FAQ.
	 *
	 * The attribute comes from unvalidated JSON, so its shape is checked.
	 *
	 * @param array $block Accordion block.
	 * @return bool
	 */
	private static function is_faq( $block ) {
		$value = isset( $block['attrs'][ Foldmark_Editor::ATTRIBUTE ] ) ? $block['attrs'][ Foldmark_Editor::ATTRIBUTE ] : null;

		return is_array( $value )
			&& isset( $value['type'] )
			&& is_string( $value['type'] )
			&& Foldmark_Editor::TYPE_FAQ === $value['type'];
	}

	/**
	 * Builds the pair of one accordion item.
	 *
	 * @param array $item  Accordion item block.
	 * @param int[] $stack IDs of the synced patterns being walked.
	 * @return array
	 */
	private static function pair( $item, &$stack ) {
		$question = '';
		$answer   = '';

		foreach ( self::children( $item ) as $child ) {
			if ( 'core/accordion-heading' === $child['blockName'] && '' === $question ) {
				$question = self::heading_text( isset( $child['innerHTML'] ) ? (string) $child['innerHTML'] : '' );
			} elseif ( 'core/accordion-panel' === $child['blockName'] && '' === $answer ) {
				foreach ( self::children( $child ) as $answer_block ) {
					$answer .= self::static_html( $answer_block, $stack );
				}
			}
		}

		return array(
			'question' => $question,
			'answer'   => $answer,
		);
	}

	/**
	 * Returns the plain text of the heading title, without the toggle icon.
	 *
	 * @param string $html Saved HTML of the accordion heading.
	 * @return string
	 */
	private static function heading_text( $html ) {
		$processor = WP_HTML_Processor::create_fragment( $html );
		if ( ! $processor ) {
			return '';
		}

		$depth = null;
		$text  = '';

		while ( $processor->next_token() ) {
			if ( null === $depth ) {
				if ( 'SPAN' === $processor->get_tag() && ! $processor->is_tag_closer() && $processor->has_class( 'wp-block-accordion-heading__toggle-title' ) ) {
					$depth = $processor->get_current_depth();
				}
				continue;
			}

			if ( $processor->get_current_depth() < $depth ) {
				break;
			}

			if ( '#text' === $processor->get_token_type() ) {
				$text .= $processor->get_modifiable_text();
			}
		}

		return trim( preg_replace( '/\s+/u', ' ', $text ) );
	}

	/**
	 * Returns the saved HTML of a block and its inner blocks.
	 *
	 * Dynamic blocks without saved markup contribute nothing.
	 *
	 * @param array $block Block.
	 * @param int[] $stack IDs of the synced patterns being walked.
	 * @return string
	 */
	private static function static_html( $block, &$stack ) {
		if ( ! is_array( $block ) ) {
			return '';
		}

		if ( isset( $block['blockName'] ) && 'core/block' === $block['blockName'] ) {
			$html = '';
			$id   = self::enter_pattern( $block, $stack );
			if ( $id ) {
				foreach ( self::pattern_blocks( $id ) as $inner ) {
					$html .= self::static_html( $inner, $stack );
				}
				array_pop( $stack );
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
				$html .= self::static_html( $children[ $index ], $stack );
				++$index;
			}
		}

		return $html;
	}

	/**
	 * Pushes a synced pattern on the stack when it can be followed.
	 *
	 * @param array $block core/block block.
	 * @param int[] $stack IDs of the synced patterns being walked.
	 * @return int The pattern ID, or 0 when it must not be followed.
	 */
	private static function enter_pattern( $block, &$stack ) {
		$ref = isset( $block['attrs']['ref'] ) ? $block['attrs']['ref'] : 0;
		if ( ! is_numeric( $ref ) ) {
			return 0;
		}

		$id = (int) $ref;
		if ( $id <= 0 || in_array( $id, $stack, true ) ) {
			return 0;
		}

		// Same conditions as render_block_core_block(): never read what core would not display.
		$post = get_post( $id );
		if ( ! $post || 'wp_block' !== $post->post_type || 'publish' !== $post->post_status || ! empty( $post->post_password ) ) {
			return 0;
		}

		$stack[] = $id;
		return $id;
	}

	/**
	 * Returns the parsed blocks of a synced pattern.
	 *
	 * @param int $id Pattern ID, already checked by enter_pattern().
	 * @return array
	 */
	private static function pattern_blocks( $id ) {
		return parse_blocks( get_post_field( 'post_content', $id, 'raw' ) );
	}

	/**
	 * Returns the inner blocks of a block, keeping only well-formed ones.
	 *
	 * @param array $block Block.
	 * @return array
	 */
	private static function children( $block ) {
		if ( empty( $block['innerBlocks'] ) || ! is_array( $block['innerBlocks'] ) ) {
			return array();
		}

		return array_values(
			array_filter(
				$block['innerBlocks'],
				static function ( $child ) {
					return is_array( $child ) && ! empty( $child['blockName'] );
				}
			)
		);
	}
}
