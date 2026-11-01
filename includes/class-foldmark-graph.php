<?php
/**
 * Builds the schema.org nodes for a post.
 *
 * @package Foldmark
 */

defined( 'ABSPATH' ) || exit;

/**
 * Turns the FAQ accordions of a post into a single FAQPage node.
 */
class Foldmark_Graph {

	/**
	 * Returns the schema.org nodes for a post, or an empty list when there is nothing to publish.
	 *
	 * @param WP_Post $post Post.
	 * @return array[]
	 */
	public static function for_post( WP_Post $post ) {
		if ( post_password_required( $post ) ) {
			return array();
		}

		$blocks = parse_blocks( $post->post_content );

		/**
		 * Filters the blocks whose presence stops Foldmark from printing anything.
		 *
		 * Each of them outputs its own FAQPage, and two FAQPage nodes on one page
		 * are reported as an error by Google Search Console.
		 *
		 * @param string[] $names Block names.
		 * @param WP_Post  $post  Post being read.
		 */
		$conflicting = apply_filters( 'foldmark_conflicting_blocks', array( 'yoast/faq-block', 'rank-math/faq-block' ), $post );

		if ( is_array( $conflicting ) && $conflicting && Foldmark_Faq_Extractor::contains_block( $blocks, $conflicting ) ) {
			return array();
		}

		$questions = self::questions( Foldmark_Faq_Extractor::extract( $blocks ), $post );
		$nodes     = array();

		if ( $questions ) {
			$nodes[] = array(
				'@type'      => 'FAQPage',
				'@id'        => get_permalink( $post ) . '#faq',
				'mainEntity' => $questions,
			);
		}

		/**
		 * Filters the schema.org nodes printed for a post.
		 *
		 * Return an empty array to print nothing. The nodes are wrapped in an
		 * "@graph" with the schema.org context when printed.
		 *
		 * @param array[] $nodes Nodes.
		 * @param WP_Post $post  Post being read.
		 */
		$nodes = apply_filters( 'foldmark_graph', $nodes, $post );

		return is_array( $nodes ) ? array_values( $nodes ) : array();
	}

	/**
	 * Builds Question nodes: sanitized, non empty, deduplicated.
	 *
	 * @param array[] $pairs Pairs returned by the extractor.
	 * @param WP_Post $post  Post being read.
	 * @return array[]
	 */
	private static function questions( $pairs, $post ) {
		$allowed   = self::allowed_html( $post );
		$questions = array();
		$seen      = array();

		foreach ( $pairs as $pair ) {
			$name   = trim( (string) $pair['question'] );
			$answer = trim( wp_kses( (string) $pair['answer'], $allowed ) );

			if ( '' === $name || '' === trim( wp_strip_all_tags( $answer ) ) ) {
				continue;
			}

			$key = mb_strtolower( trim( preg_replace( '/\s+/u', ' ', $name ) ) );
			if ( isset( $seen[ $key ] ) ) {
				continue;
			}
			$seen[ $key ] = true;

			$questions[] = array(
				'@type'          => 'Question',
				'name'           => $name,
				'acceptedAnswer' => array(
					'@type' => 'Answer',
					'text'  => $answer,
				),
			);
		}

		return $questions;
	}

	/**
	 * Returns the markup kept in answers: the tags Google accepts in Answer.text.
	 *
	 * @param WP_Post $post Post being read.
	 * @return array
	 */
	private static function allowed_html( $post ) {
		$allowed = array( 'a' => array( 'href' => true ) );
		foreach ( array( 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'br', 'ol', 'ul', 'li', 'p', 'div', 'b', 'strong', 'i', 'em' ) as $tag ) {
			$allowed[ $tag ] = array();
		}

		/**
		 * Filters the markup kept in answers, in the wp_kses() format.
		 *
		 * @param array   $allowed Allowed tags and attributes.
		 * @param WP_Post $post    Post being read.
		 */
		$filtered = apply_filters( 'foldmark_answer_allowed_html', $allowed, $post );

		return is_array( $filtered ) ? $filtered : $allowed;
	}
}
