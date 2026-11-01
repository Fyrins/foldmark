<?php
/**
 * Hooks, per-request cache and output.
 *
 * @package Foldmark
 */

defined( 'ABSPATH' ) || exit;

/**
 * Prints the graph of the current singular view in the document head.
 */
class Foldmark_Plugin {

	/**
	 * Graphs already built during this request, keyed by post ID.
	 *
	 * @var array[]
	 */
	private static $graphs = array();

	/**
	 * Hooks the output.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'wp_head', array( __CLASS__, 'print_graph' ) );
	}

	/**
	 * Returns the nodes of the current view.
	 *
	 * Only singular views of a post get a graph: archives, feeds, REST responses
	 * and admin screens never do. Available as soon as the main query has run,
	 * so before wp_head.
	 *
	 * @return array[]
	 */
	public static function get_graph() {
		if ( is_admin() || is_feed() || wp_is_serving_rest_request() || ! is_singular() ) {
			return array();
		}

		$post = get_queried_object();
		if ( ! $post instanceof WP_Post ) {
			return array();
		}

		if ( ! array_key_exists( $post->ID, self::$graphs ) ) {
			self::$graphs[ $post->ID ] = Foldmark_Graph::for_post( $post );
		}

		return self::$graphs[ $post->ID ];
	}

	/**
	 * Prints the JSON-LD script.
	 *
	 * @return void
	 */
	public static function print_graph() {
		/**
		 * Filters whether Foldmark prints its JSON-LD script.
		 *
		 * Return false to print nothing, for instance to hand the nodes returned
		 * by foldmark_get_graph() to another plugin's graph instead.
		 *
		 * @param bool $enabled Whether to print the script.
		 */
		if ( ! apply_filters( 'foldmark_output_enabled', true ) ) {
			return;
		}

		$nodes = self::get_graph();
		if ( ! $nodes ) {
			return;
		}

		$json = wp_json_encode(
			array(
				'@context' => 'https://schema.org',
				'@graph'   => $nodes,
			),
			JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
		);

		if ( false === $json ) {
			return;
		}

		wp_print_inline_script_tag( $json, array( 'type' => 'application/ld+json' ) );
	}
}
