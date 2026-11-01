<?php
/**
 * Builds the schema.org nodes for a post.
 *
 * @package Foldmark
 */

defined( 'ABSPATH' ) || exit;

/**
 * Finds the marked accordions of a post and hands each group to its schema type.
 */
final class Foldmark_Graph {

	/**
	 * Returns the schema.org nodes for a post, or an empty list when there is nothing to publish.
	 *
	 * @param WP_Post $post Post.
	 * @return array[]
	 */
	public static function for_post( WP_Post $post ) {
		$nodes = array();

		if ( ! post_password_required( $post ) && self::may_contain_accordion( $post->post_content ) ) {
			$blocks = parse_blocks( $post->post_content );
			$walker = new Foldmark_Block_Walker();
			$types  = self::types();

			foreach ( self::accordions_by_type( $blocks, $walker, $types ) as $key => $accordions ) {
				$type      = $types[ $key ];
				$conflicts = $type->conflicting_blocks( $post );

				if ( $conflicts && $walker->contains( $blocks, $conflicts ) ) {
					continue;
				}

				$built = $type->build( $accordions, $walker, $post );
				foreach ( $built as $node ) {
					if ( is_array( $node ) ) {
						$nodes[] = $node;
					}
				}
			}
		}

		/**
		 * Filters the schema.org nodes printed for a post.
		 *
		 * Return an empty array to print nothing. The nodes are wrapped in an
		 * "@graph" with the schema.org context when printed. Text values such as
		 * a question name are plain text: escape them before printing as HTML.
		 *
		 * @param array[] $nodes Nodes.
		 * @param WP_Post $post  Post being read.
		 */
		$nodes = apply_filters( 'foldmark_graph', $nodes, $post );

		return is_array( $nodes ) ? array_values( $nodes ) : array();
	}

	/**
	 * Returns the registered schema types, keyed by their "foldmark.type" value.
	 *
	 * @return Foldmark_Type[]
	 */
	public static function types() {
		$defaults = array( Foldmark_Schema::TYPE_FAQ => new Foldmark_Type_Faq() );

		/**
		 * Filters the schema types, keyed by the "type" value of the foldmark attribute.
		 *
		 * Each value must implement Foldmark_Type; anything else is ignored.
		 *
		 * @param Foldmark_Type[] $types Types.
		 */
		$types = apply_filters( 'foldmark_types', $defaults );
		if ( ! is_array( $types ) ) {
			return $defaults;
		}

		return array_filter(
			$types,
			static function ( $type, $key ) {
				return is_string( $key ) && '' !== $key && $type instanceof Foldmark_Type;
			},
			ARRAY_FILTER_USE_BOTH
		);
	}

	/**
	 * Groups the displayed accordions by their registered type, in document order.
	 *
	 * An accordion nested inside a marked accordion stays part of its content.
	 * The attribute comes from unvalidated JSON, so its shape is checked.
	 *
	 * @param array                 $blocks Blocks returned by parse_blocks().
	 * @param Foldmark_Block_Walker $walker Walker of the current post.
	 * @param Foldmark_Type[]       $types  Registered types.
	 * @return array[] Accordion blocks, keyed by type.
	 */
	public static function accordions_by_type( array $blocks, Foldmark_Block_Walker $walker, array $types ) {
		$groups = array();

		$walker->walk(
			$blocks,
			static function ( $block ) use ( $types, &$groups ) {
				if ( Foldmark_Schema::BLOCK !== $block['blockName'] ) {
					return true;
				}

				$value = isset( $block['attrs'][ Foldmark_Schema::ATTRIBUTE ] ) ? $block['attrs'][ Foldmark_Schema::ATTRIBUTE ] : null;
				if ( ! is_array( $value ) || ! isset( $value['type'] ) || ! is_string( $value['type'] ) || ! isset( $types[ $value['type'] ] ) ) {
					return true;
				}

				$groups[ $value['type'] ][] = $block;
				return false;
			}
		);

		return $groups;
	}

	/**
	 * Cheap check on the raw content before parsing it.
	 *
	 * A marked accordion is either in the content or behind a synced pattern.
	 *
	 * @param string $content Post content.
	 * @return bool
	 */
	private static function may_contain_accordion( $content ) {
		return false !== strpos( $content, '<!-- wp:accordion' ) || false !== strpos( $content, '<!-- wp:block ' );
	}
}
