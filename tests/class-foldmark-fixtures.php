<?php
/**
 * Block markup fixtures, copied from what the editor saves (WordPress 7.1).
 *
 * @package Foldmark
 */

/**
 * Builds serialized block markup for the tests.
 */
class Foldmark_Fixtures {

	/**
	 * Attributes of an accordion published as an FAQ.
	 */
	const FAQ = '{"foldmark":{"type":"faq"}}';

	/**
	 * An accordion block.
	 *
	 * @param string $items      Serialized accordion items.
	 * @param string $attributes Block comment attributes, as JSON, or '' for none.
	 * @return string
	 */
	public static function accordion( $items, $attributes = self::FAQ ) {
		$attributes = '' === $attributes ? '' : ' ' . $attributes;
		return "<!-- wp:accordion{$attributes} -->\n<div role=\"group\" class=\"wp-block-accordion\">{$items}</div>\n<!-- /wp:accordion -->";
	}

	/**
	 * An accordion item: heading plus panel.
	 *
	 * @param string $title  Title markup, as saved by the rich text field.
	 * @param string $answer Serialized blocks inside the panel.
	 * @param string $icon   Icon position: right, left, or none.
	 * @return string
	 */
	public static function item( $title, $answer, $icon = 'right' ) {
		$classes = 'wp-block-accordion-heading';
		$span    = '<span class="wp-block-accordion-heading__toggle-icon" aria-hidden="true">+</span>';
		$title   = '<span class="wp-block-accordion-heading__toggle-title">' . $title . '</span>';

		if ( 'right' === $icon ) {
			$classes .= ' has-icon has-icon-right';
			$button   = $title . $span;
		} elseif ( 'left' === $icon ) {
			$classes .= ' has-icon has-icon-left';
			$button   = $span . $title;
		} else {
			$button = $title;
		}

		$heading = "<!-- wp:accordion-heading -->\n<h3 class=\"{$classes}\"><button type=\"button\" class=\"wp-block-accordion-heading__toggle\">{$button}</button></h3>\n<!-- /wp:accordion-heading -->";
		$panel   = "<!-- wp:accordion-panel -->\n<div role=\"region\" class=\"wp-block-accordion-panel\">{$answer}</div>\n<!-- /wp:accordion-panel -->";

		return "<!-- wp:accordion-item -->\n<div class=\"wp-block-accordion-item\">{$heading}\n\n{$panel}</div>\n<!-- /wp:accordion-item -->";
	}

	/**
	 * A paragraph block.
	 *
	 * @param string $html Inner HTML of the paragraph.
	 * @return string
	 */
	public static function paragraph( $html ) {
		return "<!-- wp:paragraph -->\n<p>{$html}</p>\n<!-- /wp:paragraph -->";
	}

	/**
	 * A group block wrapping other blocks.
	 *
	 * @param string $inner Serialized inner blocks.
	 * @return string
	 */
	public static function group( $inner ) {
		return "<!-- wp:group {\"layout\":{\"type\":\"constrained\"}} -->\n<div class=\"wp-block-group\">{$inner}</div>\n<!-- /wp:group -->";
	}

	/**
	 * A reference to a synced pattern.
	 *
	 * @param int $id Pattern post ID.
	 * @return string
	 */
	public static function synced( $id ) {
		return '<!-- wp:block {"ref":' . (int) $id . '} /-->';
	}

	/**
	 * The one-question FAQ used across the tests.
	 *
	 * @return string
	 */
	public static function delivery_faq() {
		return self::accordion( self::item( 'Quel délai de livraison ?', self::paragraph( '48 heures en France.' ) ) );
	}
}
