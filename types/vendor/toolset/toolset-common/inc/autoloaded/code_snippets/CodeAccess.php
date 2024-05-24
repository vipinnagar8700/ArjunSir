<?php

namespace OTGS\Toolset\Common\CodeSnippets;

/**
 * Reads and parses the code of the snippet to obtain additional information, and saves updated snippet code.
 *
 * @since 3.0.8
 */
class CodeAccess {


	/** @var Snippet */
	private $snippet;

	/** @var \Toolset_Files */
	private $files;


	/**
	 * CodeAccess constructor.
	 *
	 * @param Snippet $snippet
	 * @param \Toolset_Files $files
	 */
	public function __construct( Snippet $snippet, \Toolset_Files $files ) {
		$this->snippet = $snippet;
		$this->files = $files;
	}


	/**
	 * Decorate the snippet model with its source code.
	 *
	 * Parse the source code and try to extract a first docblock. If one is present, set its sanitized content as
	 * the snippet's description.
	 */
	public function decorate_snippet() {
		if( ! $this->files->is_file( $this->snippet->get_absolute_file_path() ) ) {
			return;
		}

		$snippet_code = $this->files->file_get_contents( $this->snippet->get_absolute_file_path() );
		if ( false === $snippet_code ) {
			return;
		}

		$this->snippet->set_code( $snippet_code );

		$snippet_tokens = token_get_all( $snippet_code );

		$this->snippet->set_description( $this->retrieve_description( $snippet_tokens ) );

		$this->snippet->set_has_security_check( $this->identify_security_check( $snippet_tokens ) );
	}


	/**
	 * @param $snippet_tokens
	 *
	 * @return string
	 */
	private function retrieve_description( $snippet_tokens ) {
		$docblocks = array_filter( $snippet_tokens, function ( $token ) {
			return $token[0] === T_DOC_COMMENT;
		} );

		if ( empty( $docblocks ) ) {
			return '';
		}

		// Get content of the first docblock token.
		$description_token = reset( $docblocks );
		$description = toolset_getarr( $description_token, 1 );

		// Sanitize the description.
		$description = str_replace( '/**', '', $description );
		$description = str_replace( '*/', '', $description );
		$description = str_replace( '* ', '', $description );

		// Get rid of whitespace around each line, and ensure empty lines are empty (where '*' wasn't matched by the replacement above).
		$description = implode(
			"\n",
			array_map(
				function ( $line ) {
					return ( '*' === $line ? '' : $line );
				},
				array_filter(
					array_map( 'trim', explode( "\n", $description ) ),
					function ( $line ) {
						return ! empty( $line );
					}
				)
			)
		);

		$description = sanitize_textarea_field( $description );
		$description = wp_trim_words( $description, 55, '...' );

		return $description;
	}


	/**
	 * Parse the snippet's tokens to determine that a security check is placed at the beginning of
	 * the snippet's code: toolset_snippet_security_check() or die( 'something' );
	 *
	 * Docblocks, comments and whitespace are ignored, but otherwise, nothing else is allowed before this statement for
	 * the check to pass.
	 *
	 * @param array $snippet_tokens
	 *
	 * @return bool
	 *
	 * @since Types 3.1.2
	 */
	private function identify_security_check( $snippet_tokens ) {
		$ignore_at_the_beginning = array( T_OPEN_TAG, T_DOC_COMMENT, T_COMMENT, T_WHITESPACE );

		// Tokens which are not ignored must consequently match these conditions.
		$matches = array(
			function( $value, $content ) { return $value === T_STRING && $content === 'toolset_snippet_security_check'; },
			function( $value ) { return $value === '('; },
			function( $value ) { return $value === ')'; },
			function( $value ) { return $value === T_LOGICAL_OR; },
			function( $value ) { return $value === T_EXIT; },
			function( $value ) { return $value === '('; },
			function( $value ) { return $value === T_ENCAPSED_AND_WHITESPACE || $value === T_CONSTANT_ENCAPSED_STRING; },
			function( $value ) { return $value === ')'; },
			function( $value ) { return $value === ';'; },
		);
		$next_to_match = 0;

		foreach( $snippet_tokens as $snippet_token ) {

			// Tokens can come in two forms.
			if( is_string( $snippet_token ) ) {
				$token_value = $snippet_token;
				$token_content = $token_value;
			} else {
				list( $token_value, $token_content ) = $snippet_token;
			}

			if( in_array( $token_value, $ignore_at_the_beginning, true ) ) {
				continue;
			}

			// The current token is not ignored, make sure it matches the expectation.
			$matcher = $matches[ $next_to_match ];
			if( ! $matcher( $token_value, $token_content ) ) {
				return false;
			}

			$next_to_match++;

			// All required tokens have matched.
			if( ! array_key_exists( $next_to_match, $matches ) ) {
				return true;
			}
		}

		// This will be reached if the script doesn't contain any unexpected tokens but there are some missing.
		return false;
	}


	/**
	 * Store new code in the snippet file.
	 *
	 * Also updates the snippet model if the file has been successfully updated.
	 *
	 * @param string $code
	 *
	 * @return bool True if the file has been updated.
	 */
	public function update_snippet_code( $code ) {
		$result = $this->files->file_put_contents( $this->snippet->get_absolute_file_path(), $code );

		$is_success = ( $result !== false );

		if( $is_success ) {
			$this->snippet->set_code( $code );
		}

		return $is_success;
	}
}