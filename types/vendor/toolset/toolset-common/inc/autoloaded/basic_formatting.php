<?php

namespace OTGS\Toolset\Common;

/**
 * Shared formatting mechanism to be used when the the_content filter is way too loaded,
 *
 * Should include only native callbacks plus other callbacks provided by plugins, like:
 * - support for shortcodes in alternative syntax.
 * - shortcodes pre-rendering.
 *
 * To access this formatting mechanism, just call the right filter over your content:
 * $content = apply_filters( \OTGS\Toolset\Common\BasicFormatting::FILTER_NAME, $content );
 *
 * @since 3.2.3
 */
class BasicFormatting {

	const FILTER_NAME = 'toolset_the_content_basic_formatting';

	/**
	 * @var \Toolset_Shortcode_Transformer
	 */
	public $di_shortcode_transformer = null;

	public function __construct( \Toolset_Shortcode_Transformer $shortcode_transformer = null ) {
		$this->di_shortcode_transformer = ( null === $shortcode_transformer )
			? new \Toolset_Shortcode_Transformer()
			: $shortcode_transformer;
	}

	/**
	 * List of the default supported callbacks.
	 *
	 * @return array
	 */
	private function get_callback_candidates() {
		return array(
			array( 'callback' => array( $this->di_shortcode_transformer, 'replace_shortcode_placeholders_with_brackets' ), 'priority' => 4 ),
			array( 'callback' => array( $GLOBALS['wp_embed'], 'run_shortcode' ), 'priority' => 8 ),
			array( 'callback' => array( $GLOBALS['wp_embed'], 'autoembed' ), 'priority' => 8 ),
			array( 'callback' => 'wptexturize', 'priority' => 10 ),
			array( 'callback' => 'wpautop', 'priority' => 10 ),
			array( 'callback' => 'shortcode_unautop', 'priority' =>10 ),
			array( 'callback' => 'wp_filter_content_tags', 'priority' => 10 ),
			array( 'callback' => 'capital_P_dangit', 'priority' => 11 ),
			array( 'callback' => 'do_shortcode', 'priority' => 11 ),
			array( 'callback' => 'convert_smilies', 'priority' => 20 )
		);
	}

	/**
	 * Initialize the filter callbacks.
	 *
	 * @return void
	 */
	public function initialize() {
		foreach ( $this->get_callback_candidates() as $callback_candidate ) {
			if ( ! is_callable( $callback_candidate['callback'] ) ) {
				continue;
			}
			add_filter( static::FILTER_NAME, $callback_candidate['callback'], $callback_candidate['priority'] );
		}
	}
}
