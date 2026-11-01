<?php
/**
 * Tests for the schema.org graph built from a post.
 *
 * @package Foldmark
 */

/**
 * Foldmark_Graph turns a post into FAQPage nodes.
 */
class Foldmark_Test_Graph extends WP_UnitTestCase {

	/**
	 * Creates a published post.
	 *
	 * @param string $content Post content.
	 * @param array  $extra   Extra post fields.
	 * @return WP_Post
	 */
	private function post( $content, $extra = array() ) {
		return self::factory()->post->create_and_get( array_merge( array( 'post_content' => $content ), $extra ) );
	}

	/**
	 * A raw HTML block, to feed exact markup to an answer.
	 *
	 * @param string $html HTML.
	 * @return string
	 */
	private function html_block( $html ) {
		return "<!-- wp:html -->\n{$html}\n<!-- /wp:html -->";
	}

	public function test_builds_faqpage_node() {
		$p = $this->post( Foldmark_Fixtures::delivery_faq() );

		$expected = array(
			'@type'      => 'FAQPage',
			'@id'        => get_permalink( $p ) . '#faq',
			'mainEntity' => array(
				array(
					'@type'          => 'Question',
					'name'           => 'Quel délai de livraison ?',
					'acceptedAnswer' => array(
						'@type' => 'Answer',
						'text'  => '<p>48 heures en France.</p>',
					),
				),
			),
		);

		$this->assertSame( array( $expected ), Foldmark_Graph::for_post( $p ) );
	}

	public function test_answer_keeps_only_allowed_markup() {
		$raw   = '<p class="x">A <span>b</span> <a href="/c" target="_blank">c</a></p><img src="d.png"><h4>e</h4>';
		$p     = $this->post( Foldmark_Fixtures::accordion( Foldmark_Fixtures::item( 'Q', $this->html_block( $raw ) ) ) );
		$nodes = Foldmark_Graph::for_post( $p );

		$this->assertSame( '<p>A b <a href="/c">c</a></p><h4>e</h4>', $nodes[0]['mainEntity'][0]['acceptedAnswer']['text'] );
	}

	public function test_duplicate_questions_across_accordions_are_merged_once() {
		$p = $this->post(
			Foldmark_Fixtures::accordion(
				Foldmark_Fixtures::item( 'Livraison ?', Foldmark_Fixtures::paragraph( 'Premier.' ) )
				. Foldmark_Fixtures::item( 'Paiement ?', Foldmark_Fixtures::paragraph( 'Carte.' ) )
			)
			. Foldmark_Fixtures::accordion(
				Foldmark_Fixtures::item( '  livraison  ? ', Foldmark_Fixtures::paragraph( 'Second.' ) )
				. Foldmark_Fixtures::item( 'Retours ?', Foldmark_Fixtures::paragraph( '30 jours.' ) )
			)
		);

		$nodes = Foldmark_Graph::for_post( $p );

		$this->assertCount( 1, $nodes );
		$this->assertSame( array( 'Livraison ?', 'Paiement ?', 'Retours ?' ), wp_list_pluck( $nodes[0]['mainEntity'], 'name' ) );
		$this->assertSame( '<p>Premier.</p>', $nodes[0]['mainEntity'][0]['acceptedAnswer']['text'] );
	}

	public function test_faq_without_valid_item_produces_no_node() {
		$image = "<!-- wp:image -->\n<figure class=\"wp-block-image\"><img src=\"x.png\" alt=\"\"/></figure>\n<!-- /wp:image -->";
		$p     = $this->post(
			Foldmark_Fixtures::accordion(
				Foldmark_Fixtures::item( '', Foldmark_Fixtures::paragraph( 'Réponse sans question.' ) )
				. Foldmark_Fixtures::item( 'Question sans réponse ?', '' )
				. Foldmark_Fixtures::item( 'Question avec image seule ?', $image )
			)
		);

		$this->assertSame( array(), Foldmark_Graph::for_post( $p ) );
	}

	public function test_conflicting_block_disables_output() {
		$yoast = '<!-- wp:yoast/faq-block {"questions":[]} -->' . "\n" . '<div class="schema-faq wp-block-yoast-faq-block"></div>' . "\n" . '<!-- /wp:yoast/faq-block -->';
		$this->assertSame( array(), Foldmark_Graph::for_post( $this->post( Foldmark_Fixtures::delivery_faq() . $yoast ) ) );

		$other = '<!-- wp:acme/faq /-->';
		add_filter(
			'foldmark_conflicting_blocks',
			static function ( $names ) {
				$names[] = 'acme/faq';
				return $names;
			}
		);
		$this->assertSame( array(), Foldmark_Graph::for_post( $this->post( Foldmark_Fixtures::delivery_faq() . $other ) ) );
	}

	public function test_password_protected_post_produces_nothing() {
		$p = $this->post( Foldmark_Fixtures::delivery_faq(), array( 'post_password' => 'secret' ) );
		$this->assertSame( array(), Foldmark_Graph::for_post( $p ) );
	}

	public function test_graph_filter_can_extend_or_empty() {
		$p     = $this->post( Foldmark_Fixtures::delivery_faq() );
		$extra = array( '@type' => 'Thing' );

		add_filter(
			'foldmark_graph',
			static function ( $nodes ) use ( $extra ) {
				$nodes[] = $extra;
				return $nodes;
			}
		);
		$nodes = Foldmark_Graph::for_post( $p );
		$this->assertCount( 2, $nodes );
		$this->assertSame( $extra, $nodes[1] );

		add_filter( 'foldmark_graph', '__return_empty_array', 20 );
		$this->assertSame( array(), Foldmark_Graph::for_post( $p ) );
	}
}
