<?php

/**
 * SyntaxHighlighter Evolved
 *
 * @see https://wordpress.org/plugins/syntaxhighlighter/
 * @since 2.3
 */
class Types_Wordpress_3rd_Syntaxhighlighter implements Types_Wordpress_3rd_Interface {

	/**
	 * Syntaxhighlighter does not set any constant,
	 * but registers global $SyntaxHighlighter which holds the Syntaxhighlighter class object
	 *
	 * @return bool
	 */
	public function is_active() {
		if ( ! function_exists( 'SyntaxHighlighter' ) ) {
			return false;
		}

		global $SyntaxHighlighter;
		if( ! is_object( $SyntaxHighlighter ) ) {
			return false;
		}

		return true;
	}
}