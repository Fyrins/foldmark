<?php
/**
 * Contract of a schema type.
 *
 * @package Foldmark
 */

defined( 'ABSPATH' ) || exit;

/**
 * Turns the accordions marked with one "foldmark.type" value into schema.org nodes.
 *
 * Register an implementation with the foldmark_types filter, keyed by that value.
 */
interface Foldmark_Type {

	/**
	 * Builds the nodes for the accordions of this type found in a post.
	 *
	 * @param array[]               $accordions Accordion blocks, in document order, as returned by parse_blocks().
	 * @param Foldmark_Block_Walker $walker     Walker of the current post, to read inner blocks and saved HTML.
	 * @param WP_Post               $post       Post being read.
	 * @return array[] Nodes, possibly none.
	 */
	public function build( array $accordions, Foldmark_Block_Walker $walker, WP_Post $post ): array;

	/**
	 * Returns the blocks whose presence in the post stops this type from producing anything.
	 *
	 * @param WP_Post $post Post being read.
	 * @return string[] Block names.
	 */
	public function conflicting_blocks( WP_Post $post ): array;
}
