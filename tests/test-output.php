<?php
/**
 * Tests for the printed JSON-LD and the public accessor.
 *
 * @package Foldmark
 */

/**
 * Foldmark_Plugin prints one JSON-LD script on singular views.
 */
class Foldmark_Test_Output extends WP_UnitTestCase {

	const OPENING_TAG = '<script type="application/ld+json">';

	/**
	 * Visits a new published post and returns it.
	 *
	 * @param string $content Post content.
	 * @return WP_Post
	 */
	private function visit( $content ) {
		$post = self::factory()->post->create_and_get( array( 'post_content' => $content ) );
		$this->go_to( get_permalink( $post ) );
		return $post;
	}

	/**
	 * Captures what wp_head prints.
	 *
	 * @return string
	 */
	private function head() {
		return get_echo( 'do_action', array( 'wp_head' ) );
	}

	/**
	 * Decodes the JSON-LD printed in the head.
	 *
	 * @param string $head Head markup.
	 * @return array
	 */
	private function decode( $head ) {
		$start = strpos( $head, self::OPENING_TAG );
		$this->assertNotFalse( $start, 'No JSON-LD script printed.' );
		$start += strlen( self::OPENING_TAG );
		$end    = strpos( $head, '</script>', $start );
		return json_decode( substr( $head, $start, $end - $start ), true );
	}

	public function test_singular_page_prints_one_script() {
		$post = $this->visit( Foldmark_Fixtures::delivery_faq() );
		$head = $this->head();

		$this->assertSame( 1, substr_count( $head, self::OPENING_TAG ) );
		$this->assertSame(
			array(
				'@context' => 'https://schema.org',
				'@graph'   => Foldmark_Graph::for_post( $post ),
			),
			$this->decode( $head )
		);
	}

	public function test_archive_prints_nothing() {
		self::factory()->post->create( array( 'post_content' => Foldmark_Fixtures::delivery_faq() ) );
		$this->go_to( home_url( '/' ) );

		$this->assertSame( 0, substr_count( $this->head(), self::OPENING_TAG ) );
		$this->assertSame( array(), foldmark_get_graph() );
	}

	public function test_get_graph_is_available_before_wp_head() {
		$runs = 0;
		add_filter(
			'foldmark_graph',
			static function ( $nodes ) use ( &$runs ) {
				++$runs;
				return $nodes;
			}
		);

		$this->visit( Foldmark_Fixtures::delivery_faq() );

		$this->assertSame( 'FAQPage', foldmark_get_graph()[0]['@type'] );
		$this->head();
		foldmark_get_graph();
		$this->assertSame( 1, $runs );
	}

	public function test_reset_clears_the_request_cache() {
		$post = $this->visit( Foldmark_Fixtures::delivery_faq() );
		$this->assertCount( 1, foldmark_get_graph() );

		wp_update_post(
			array(
				'ID'           => $post->ID,
				'post_content' => Foldmark_Fixtures::paragraph( 'Plus de FAQ.' ),
			)
		);
		$this->go_to( get_permalink( $post ) );
		$this->assertCount( 1, foldmark_get_graph(), 'The graph is cached for the request.' );

		Foldmark_Plugin::reset();
		$this->assertSame( array(), foldmark_get_graph() );
	}

	public function test_output_can_be_disabled() {
		add_filter( 'foldmark_output_enabled', '__return_false' );
		$this->visit( Foldmark_Fixtures::delivery_faq() );

		$this->assertSame( 0, substr_count( $this->head(), self::OPENING_TAG ) );
		$this->assertSame( 'FAQPage', foldmark_get_graph()[0]['@type'] );
	}

	public function test_output_cannot_break_out_of_script_tag() {
		$question = 'Q&lt;/script&gt;&lt;script&gt;alert(1)&lt;/script&gt;';
		$answer   = "<!-- wp:html -->\n<p>Réponse</p><!-- note -->\n<!-- /wp:html -->";
		$this->visit( Foldmark_Fixtures::accordion( Foldmark_Fixtures::item( $question, $answer ) ) );

		$head  = $this->head();
		$start = strpos( $head, self::OPENING_TAG ) + strlen( self::OPENING_TAG );
		$body  = substr( $head, $start, strpos( $head, '</script>', $start ) - $start );

		$this->assertSame( 1, substr_count( $head, '</script>' ) );
		$this->assertStringNotContainsString( '<', $body );
		$this->assertSame( 'Q</script><script>alert(1)</script>', $this->decode( $head )['@graph'][0]['mainEntity'][0]['name'] );
	}
}
