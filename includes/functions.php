<?php
/**
 * Public functions.
 *
 * @package Foldmark
 */

defined( 'ABSPATH' ) || exit;

/**
 * Returns the schema.org nodes Foldmark prints for the current view.
 *
 * Available once the main query has run, so before wp_head. Combine with the
 * foldmark_output_enabled filter to move the nodes into another plugin's graph.
 *
 * The result is computed once per request and per post: filters on
 * foldmark_graph, foldmark_types, foldmark_conflicting_blocks or
 * foldmark_answer_allowed_html must be added before the first call.
 *
 * @return array[] Nodes, or an empty array.
 */
function foldmark_get_graph() {
	return Foldmark_Plugin::get_graph();
}
