<?php
/**
 * Tests for the FAQ type: reading questions and answers out of a block tree.
 *
 * @package Foldmark
 */

/**
 * Foldmark_Type_Faq, fed by Foldmark_Graph and Foldmark_Block_Walker, reads FAQ accordions.
 */
class Foldmark_Test_Faq_Type extends WP_UnitTestCase {

	/**
	 * Extracts pairs from serialized markup, with trimmed answers.
	 *
	 * @param string $markup Serialized blocks.
	 * @return array
	 */
	private function pairs( $markup ) {
		$walker     = new Foldmark_Block_Walker();
		$accordions = Foldmark_Graph::accordions_by_type( parse_blocks( $markup ), $walker, Foldmark_Graph::types() );
		$pairs      = ( new Foldmark_Type_Faq() )->pairs( isset( $accordions['faq'] ) ? $accordions['faq'] : array(), $walker );
		foreach ( $pairs as $i => $pair ) {
			$pairs[ $i ]['answer'] = trim( $pair['answer'] );
		}
		return $pairs;
	}

	/**
	 * Creates a synced pattern.
	 *
	 * @param string $content Pattern content.
	 * @param string $status  Post status.
	 * @return int
	 */
	private function pattern( $content, $status = 'publish' ) {
		return self::factory()->post->create(
			array(
				'post_type'    => 'wp_block',
				'post_status'  => $status,
				'post_content' => $content,
			)
		);
	}

	public function test_extracts_question_and_answer() {
		$this->assertSame(
			array(
				array(
					'question' => 'Quel délai de livraison ?',
					'answer'   => '<p>48 heures en France.</p>',
				),
			),
			$this->pairs( Foldmark_Fixtures::delivery_faq() )
		);
	}

	public function test_question_ignores_icon_and_inline_markup() {
		$markup = Foldmark_Fixtures::accordion( Foldmark_Fixtures::item( '<strong>Prix</strong> &amp; délais', Foldmark_Fixtures::paragraph( 'Voir la grille.' ), 'left' ) );
		$this->assertSame( 'Prix & délais', $this->pairs( $markup )[0]['question'] );
	}

	public function test_plain_accordion_is_ignored() {
		$markup = Foldmark_Fixtures::accordion( Foldmark_Fixtures::item( 'Q', Foldmark_Fixtures::paragraph( 'A' ) ), '' );
		$this->assertSame( array(), $this->pairs( $markup ) );
	}

	public function test_malformed_attribute_is_ignored() {
		$item = Foldmark_Fixtures::item( 'Q', Foldmark_Fixtures::paragraph( 'A' ) );
		foreach ( array( '{"foldmark":"faq"}', '{"foldmark":{"type":["faq"]}}', '{"foldmark":{"type":null}}', '{"foldmark":{"type":"howto"}}', '{"foldmark":null}' ) as $attributes ) {
			$this->assertSame( array(), $this->pairs( Foldmark_Fixtures::accordion( $item, $attributes ) ), $attributes );
		}
	}

	public function test_items_from_several_accordions_follow_document_order() {
		$markup = Foldmark_Fixtures::accordion( Foldmark_Fixtures::item( 'Un', Foldmark_Fixtures::paragraph( '1' ) ) . Foldmark_Fixtures::item( 'Deux', Foldmark_Fixtures::paragraph( '2' ) ) )
			. Foldmark_Fixtures::paragraph( 'Entre les deux.' )
			. Foldmark_Fixtures::accordion( Foldmark_Fixtures::item( 'Trois', Foldmark_Fixtures::paragraph( '3' ) ) );
		$this->assertSame( array( 'Un', 'Deux', 'Trois' ), wp_list_pluck( $this->pairs( $markup ), 'question' ) );
	}

	public function test_faq_inside_group_and_columns_is_found() {
		$markup = Foldmark_Fixtures::group(
			"<!-- wp:columns -->\n<div class=\"wp-block-columns\"><!-- wp:column -->\n<div class=\"wp-block-column\">"
			. Foldmark_Fixtures::delivery_faq()
			. "</div>\n<!-- /wp:column --></div>\n<!-- /wp:columns -->"
		);
		$this->assertSame( array( 'Quel délai de livraison ?' ), wp_list_pluck( $this->pairs( $markup ), 'question' ) );
	}

	public function test_nested_accordion_stays_in_parent_answer() {
		$inner  = Foldmark_Fixtures::accordion( Foldmark_Fixtures::item( 'Sous-question', Foldmark_Fixtures::paragraph( 'Sous-réponse' ) ) );
		$markup = Foldmark_Fixtures::accordion( Foldmark_Fixtures::item( 'Question', Foldmark_Fixtures::paragraph( 'Réponse' ) . $inner ) );
		$pairs  = $this->pairs( $markup );
		$this->assertSame( array( 'Question' ), wp_list_pluck( $pairs, 'question' ) );
		$this->assertStringContainsString( 'Sous-réponse', $pairs[0]['answer'] );
	}

	public function test_synced_pattern_is_resolved() {
		$published = $this->pattern( Foldmark_Fixtures::delivery_faq() );
		$this->assertSame( array( 'Quel délai de livraison ?' ), wp_list_pluck( $this->pairs( Foldmark_Fixtures::synced( $published ) ), 'question' ) );

		$draft = $this->pattern( Foldmark_Fixtures::delivery_faq(), 'draft' );
		$this->assertSame( array(), $this->pairs( Foldmark_Fixtures::synced( $draft ) ) );
	}

	public function test_password_protected_synced_pattern_is_not_read() {
		$id = $this->pattern( Foldmark_Fixtures::delivery_faq() );
		wp_update_post(
			array(
				'ID'            => $id,
				'post_password' => 'secret',
			)
		);
		$this->assertSame( array(), $this->pairs( Foldmark_Fixtures::synced( $id ) ) );
	}

	public function test_synced_pattern_inside_an_answer_is_resolved() {
		$id     = $this->pattern( Foldmark_Fixtures::paragraph( 'Texte partagé.' ) );
		$markup = Foldmark_Fixtures::accordion( Foldmark_Fixtures::item( 'Q', Foldmark_Fixtures::synced( $id ) ) );
		$this->assertStringContainsString( 'Texte partagé.', $this->pairs( $markup )[0]['answer'] );
	}

	public function test_self_referencing_synced_pattern_terminates() {
		$a = $this->pattern( '' );
		$b = $this->pattern( Foldmark_Fixtures::synced( $a ) );
		wp_update_post(
			array(
				'ID'           => $a,
				'post_content' => Foldmark_Fixtures::delivery_faq() . Foldmark_Fixtures::synced( $b ),
			)
		);
		$this->assertCount( 1, $this->pairs( Foldmark_Fixtures::synced( $a ) ) );
	}

	public function test_hidden_accordion_is_ignored() {
		$item = Foldmark_Fixtures::item( 'Q', Foldmark_Fixtures::paragraph( 'A' ) );
		$this->assertSame( array(), $this->pairs( Foldmark_Fixtures::accordion( $item, '{"foldmark":{"type":"faq"},"metadata":{"blockVisibility":false}}' ) ) );
	}

	public function test_faq_inside_hidden_group_is_ignored() {
		$markup = str_replace( '{"layout":{"type":"constrained"}}', '{"layout":{"type":"constrained"},"metadata":{"blockVisibility":false}}', Foldmark_Fixtures::group( Foldmark_Fixtures::delivery_faq() ) );
		$this->assertSame( array(), $this->pairs( $markup ) );
	}

	public function test_hidden_item_is_ignored() {
		$hidden = str_replace( '<!-- wp:accordion-item -->', '<!-- wp:accordion-item {"metadata":{"blockVisibility":false}} -->', Foldmark_Fixtures::item( 'Masquée', Foldmark_Fixtures::paragraph( 'A' ) ) );
		$markup = Foldmark_Fixtures::accordion( $hidden . Foldmark_Fixtures::item( 'Visible', Foldmark_Fixtures::paragraph( 'B' ) ) );
		$this->assertSame( array( 'Visible' ), wp_list_pluck( $this->pairs( $markup ), 'question' ) );
	}

	public function test_hidden_answer_block_is_left_out() {
		$hidden = str_replace( '<!-- wp:paragraph -->', '<!-- wp:paragraph {"metadata":{"blockVisibility":false}} -->', Foldmark_Fixtures::paragraph( 'Texte masqué' ) );
		$pairs  = $this->pairs( Foldmark_Fixtures::accordion( Foldmark_Fixtures::item( 'Q', Foldmark_Fixtures::paragraph( 'Texte visible' ) . $hidden ) ) );
		$this->assertSame( '<p>Texte visible</p>', $pairs[0]['answer'] );
	}

	public function test_hidden_synced_pattern_reference_is_ignored() {
		$id = $this->pattern( Foldmark_Fixtures::delivery_faq() );
		$this->assertSame( array(), $this->pairs( '<!-- wp:block {"ref":' . $id . ',"metadata":{"blockVisibility":false}} /-->' ) );
	}

	public function test_contains_block_ignores_hidden_blocks() {
		$yoast = '<!-- wp:yoast/faq-block {"questions":[],"metadata":{"blockVisibility":false}} -->' . "\n" . '<div class="schema-faq wp-block-yoast-faq-block"></div>' . "\n" . '<!-- /wp:yoast/faq-block -->';
		$this->assertFalse( ( new Foldmark_Block_Walker() )->contains( parse_blocks( $yoast ), array( 'yoast/faq-block' ) ) );
	}

	public function test_contains_block_finds_nested_and_synced_blocks() {
		$yoast = '<!-- wp:yoast/faq-block {"questions":[]} -->' . "\n" . '<div class="schema-faq wp-block-yoast-faq-block"></div>' . "\n" . '<!-- /wp:yoast/faq-block -->';
		$names = array( 'yoast/faq-block' );

		$this->assertTrue( ( new Foldmark_Block_Walker() )->contains( parse_blocks( Foldmark_Fixtures::group( $yoast ) ), $names ) );
		$this->assertTrue( ( new Foldmark_Block_Walker() )->contains( parse_blocks( Foldmark_Fixtures::synced( $this->pattern( $yoast ) ) ), $names ) );
		$this->assertFalse( ( new Foldmark_Block_Walker() )->contains( parse_blocks( Foldmark_Fixtures::delivery_faq() ), $names ) );
	}
}
