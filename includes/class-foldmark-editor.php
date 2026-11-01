<?php
/**
 * Declares the block attribute and the block variations, server side only.
 *
 * @package Foldmark
 */

defined( 'ABSPATH' ) || exit;

/**
 * Adds a "foldmark" attribute and two variations to core/accordion.
 *
 * Both are declared in PHP and reach the editor through the block type
 * definitions WordPress already sends it, so the plugin ships no JavaScript.
 * The attribute has no source: it lives in the block comment, which block
 * validation ignores, so the saved markup never changes.
 */
final class Foldmark_Editor {

	/**
	 * Hooks the filters. Must run before core blocks are registered on init.
	 *
	 * @return void
	 */
	public static function init() {
		add_filter( 'register_block_type_args', array( __CLASS__, 'add_attribute' ), 10, 2 );
		add_filter( 'get_block_type_variations', array( __CLASS__, 'add_variations' ), 10, 2 );
	}

	/**
	 * Adds the attribute to core/accordion.
	 *
	 * @param array  $args Block type arguments.
	 * @param string $name Block type name.
	 * @return array
	 */
	public static function add_attribute( $args, $name ) {
		if ( Foldmark_Schema::BLOCK !== $name || ! is_array( $args ) ) {
			return $args;
		}

		if ( ! isset( $args['attributes'] ) || ! is_array( $args['attributes'] ) ) {
			$args['attributes'] = array();
		}

		// Null is the value the Accordion variation writes to turn an FAQ back into a plain accordion.
		$args['attributes'][ Foldmark_Schema::ATTRIBUTE ] = array( 'type' => array( 'object', 'null' ) );

		return $args;
	}

	/**
	 * Adds the FAQ variation, and the variation that turns it back into a plain accordion.
	 *
	 * @param array         $variations Registered variations.
	 * @param WP_Block_Type $block_type Block type.
	 * @return array
	 */
	public static function add_variations( $variations, $block_type ) {
		if ( ! $block_type instanceof WP_Block_Type || Foldmark_Schema::BLOCK !== $block_type->name || ! is_array( $variations ) ) {
			return $variations;
		}

		$item = array(
			'core/accordion-item',
			array(),
			array(
				array( 'core/accordion-heading', array() ),
				array( 'core/accordion-panel', array(), array( array( 'core/paragraph', array() ) ) ),
			),
		);

		$variations[] = array(
			'name'        => 'foldmark/faq',
			'title'       => _x( 'FAQ', 'block variation title', 'foldmark' ),
			'description' => __( 'Questions and answers published as FAQ structured data.', 'foldmark' ),
			'icon'        => 'editor-help',
			'keywords'    => array( 'faq', 'questions', 'schema' ),
			'scope'       => array( 'inserter', 'transform' ),
			'attributes'  => array( Foldmark_Schema::ATTRIBUTE => array( 'type' => Foldmark_Schema::TYPE_FAQ ) ),
			'isActive'    => array( Foldmark_Schema::ATTRIBUTE . '.type' ),
			'innerBlocks' => array( $item, $item, $item ),
		);

		$variations[] = array(
			'name'       => 'foldmark/accordion',
			'title'      => __( 'Accordion', 'foldmark' ),
			'scope'      => array( 'transform' ),
			'attributes' => array( Foldmark_Schema::ATTRIBUTE => null ),
			'isActive'   => array( Foldmark_Schema::ATTRIBUTE ),
		);

		return $variations;
	}
}
