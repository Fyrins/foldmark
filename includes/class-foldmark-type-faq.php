<?php
/**
 * The FAQ type: accordion items as questions and answers.
 *
 * @package Foldmark
 */

defined( 'ABSPATH' ) || exit;

/**
 * Builds a single FAQPage from every FAQ accordion of a post.
 */
final class Foldmark_Type_Faq implements Foldmark_Type {

	/**
	 * Builds the FAQPage node: sanitized answers, no empty item, no duplicate question.
	 *
	 * All FAQ accordions of a post are merged, because a page describes one FAQ.
	 *
	 * @param array[]               $accordions FAQ accordions.
	 * @param Foldmark_Block_Walker $walker     Walker of the current post.
	 * @param WP_Post               $post       Post being read.
	 * @return array[]
	 */
	public function build( array $accordions, Foldmark_Block_Walker $walker, WP_Post $post ): array {
		$allowed   = $this->allowed_html( $post );
		$questions = array();
		$seen      = array();

		foreach ( $this->pairs( $accordions, $walker ) as $pair ) {
			$name   = trim( $pair['question'] );
			$answer = trim( wp_kses( $pair['answer'], $allowed ) );

			if ( '' === $name || '' === trim( wp_strip_all_tags( $answer ) ) ) {
				continue;
			}

			$key = self::question_key( $name );
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

		if ( ! $questions ) {
			return array();
		}

		return array(
			array(
				'@type'      => 'FAQPage',
				'@id'        => get_permalink( $post ) . '#faq',
				'mainEntity' => $questions,
			),
		);
	}

	/**
	 * Returns the blocks of other plugins that print their own FAQPage.
	 *
	 * Two FAQPage nodes on one page are reported as an error by Google Search Console.
	 *
	 * @param WP_Post $post Post being read.
	 * @return string[]
	 */
	public function conflicting_blocks( WP_Post $post ): array {
		/**
		 * Filters the blocks whose presence stops Foldmark from printing its FAQPage.
		 *
		 * @param string[] $names Block names.
		 * @param WP_Post  $post  Post being read.
		 * @param string   $type  Schema type the list applies to: "faq".
		 */
		$names = apply_filters(
			'foldmark_conflicting_blocks',
			array( 'yoast/faq-block', 'rank-math/faq-block', 'wpseopress/faq-block', 'wpseopress/faq-block-v2', 'aioseo/faq' ),
			$post,
			Foldmark_Schema::TYPE_FAQ
		);

		return is_array( $names ) ? array_values( array_filter( $names, 'is_string' ) ) : array();
	}

	/**
	 * Returns the question and answer pairs of the accordions, in document order.
	 *
	 * The question is plain text, entities decoded: the text the reader sees,
	 * never markup. The answer is the saved HTML of the panel blocks, not yet
	 * sanitized. Pairs may be empty.
	 *
	 * @param array[]               $accordions FAQ accordions.
	 * @param Foldmark_Block_Walker $walker     Walker of the current post.
	 * @return array[] List of array( 'question' => string, 'answer' => string ).
	 */
	public function pairs( array $accordions, Foldmark_Block_Walker $walker ) {
		$pairs = array();

		foreach ( $accordions as $accordion ) {
			foreach ( $walker->children( $accordion ) as $item ) {
				if ( 'core/accordion-item' !== $item['blockName'] ) {
					continue;
				}

				$question = '';
				$answer   = '';

				foreach ( $walker->children( $item ) as $child ) {
					if ( 'core/accordion-heading' === $child['blockName'] && '' === $question ) {
						$question = self::heading_text( isset( $child['innerHTML'] ) ? (string) $child['innerHTML'] : '' );
					} elseif ( 'core/accordion-panel' === $child['blockName'] && '' === $answer ) {
						// children() keeps pattern references; static_html() resolves them.
						foreach ( $walker->children( $child ) as $answer_block ) {
							$answer .= $walker->static_html( $answer_block );
						}
					}
				}

				$pairs[] = array(
					'question' => $question,
					'answer'   => $answer,
				);
			}
		}

		return $pairs;
	}

	/**
	 * Returns the key used to detect duplicate questions: case and spacing ignored.
	 *
	 * @param string $question Question text.
	 * @return string
	 */
	public static function question_key( $question ) {
		$key = self::collapse_spaces( (string) $question );

		// WordPress does not require mbstring, and core has no polyfill for mb_strtolower().
		return function_exists( 'mb_strtolower' ) ? mb_strtolower( $key, 'UTF-8' ) : strtolower( $key );
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

		return self::collapse_spaces( $text );
	}

	/**
	 * Collapses runs of white space and trims, keeping the string when it is not valid UTF-8.
	 *
	 * @param string $text Text.
	 * @return string
	 */
	private static function collapse_spaces( $text ) {
		$collapsed = preg_replace( '/\s+/u', ' ', $text );

		// preg_replace() returns null on invalid UTF-8.
		return trim( null === $collapsed ? preg_replace( '/\s+/', ' ', $text ) : $collapsed );
	}

	/**
	 * Returns the markup kept in answers: the tags Google accepts in Answer.text.
	 *
	 * @param WP_Post $post Post being read.
	 * @return array
	 */
	private function allowed_html( WP_Post $post ) {
		$allowed = array( 'a' => array( 'href' => true ) );
		foreach ( array( 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'br', 'ol', 'ul', 'li', 'p', 'div', 'b', 'strong', 'i', 'em' ) as $tag ) {
			$allowed[ $tag ] = array();
		}

		/**
		 * Filters the markup kept in FAQ answers, in the wp_kses() format.
		 *
		 * @param array   $allowed Allowed tags and attributes.
		 * @param WP_Post $post    Post being read.
		 */
		$filtered = apply_filters( 'foldmark_answer_allowed_html', $allowed, $post );

		return is_array( $filtered ) ? $filtered : $allowed;
	}
}
