<?php

/**
* Toolset_Shortcode_Transformer
*
* Generic class to manage the Toolset shortcodes transformation to allow usage of different shortocode formats.
*
* @since 2.5.7
*/

class Toolset_Shortcode_Transformer {

	const NATIVE_SYNTAX_OPEN  = '[';
	const NATIVE_SYNTAX_CLOSE = ']';

	const ALTERNATIVE_SYNTAX_OPEN  = '{!{';
	const ALTERNATIVE_SYNTAX_CLOSE = '}!}';

	const TERNARY_SYNTAX_OPEN  = '#!{';
	const TERNARY_SYNTAX_CLOSE = '}!#';

	public function init_hooks() {
		add_filter( 'the_content', array( $this, 'replace_shortcode_placeholders_with_brackets' ), 4 );

		add_filter( 'toolset_transform_shortcode_format', array( $this, 'replace_shortcode_placeholders_with_brackets' ) );
    }

	/**
	 * In Views 2.5.0, we introduced support for shortcodes using placeholders instead of bracket. The selected placeholder
	 * for the left bracket "[" was chosen to be the "{!{" and the selected placeholder for the right bracket "]" was chosen
	 * to be the "}!}". This was done to allow the use of Toolset shortcodes inside the various page builder modules fields.
	 * Here, we are replacing early the instances of the placeholders with the normal brackets, in order for them to be
	 * treated as normal shortcodes.
	 *
	 * In Views 3.6.8, we introduced support for shortcodes using the ternaruy syntax.
	 *
	 * @param  string $content
	 *
	 * @return string
	 *
	 * @since  2.5.0
	 * @since  2.5.7 It was moved from Views to Toolset Common to allow shortcode transformation even if Views is disabled.
	 * @since  3.6.8 Support for ternary syntax.
	 */
	public function replace_shortcode_placeholders_with_brackets( $content ) {
		$content = str_replace(
			[
				self::ALTERNATIVE_SYNTAX_OPEN,
				self::TERNARY_SYNTAX_OPEN,
			],
			self::NATIVE_SYNTAX_OPEN,
			$content
		);
		$content = str_replace(
			[
				self::ALTERNATIVE_SYNTAX_CLOSE,
				self::TERNARY_SYNTAX_CLOSE,
			],
			self::NATIVE_SYNTAX_CLOSE,
			$content
		);
		return $content;
	}

	/**
	 * @param  string $content
	 * @param  string $prefix
	 *
	 * @return bool
	 */
	public static function has_non_standard_syntax( $content, $prefix = '' ) {
		return (
			false !== strpos( $content, Toolset_Shortcode_Transformer::ALTERNATIVE_SYNTAX_OPEN . $prefix )
			|| false !== strpos( $content, Toolset_Shortcode_Transformer::TERNARY_SYNTAX_OPEN . $prefix )
		);
	}

}
