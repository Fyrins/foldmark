<?php
/**
 * Domain constants shared by the editor, the walker and the types.
 *
 * @package Foldmark
 */

defined( 'ABSPATH' ) || exit;

/**
 * Names that tie saved content to schema types.
 */
final class Foldmark_Schema {

	/**
	 * The block this plugin extends.
	 */
	const BLOCK = 'core/accordion';

	/**
	 * Name of the block attribute. Its "type" key selects a schema type.
	 */
	const ATTRIBUTE = 'foldmark';

	/**
	 * Value of the "type" key for an FAQ.
	 */
	const TYPE_FAQ = 'faq';
}
