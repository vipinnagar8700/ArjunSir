<?php

namespace OTGS\Toolset\Common\CodeSnippets;

use OTGS\Toolset\Common\Wordpress\Option\AOption;

/**
 * Represents a WordPress option that stores serialized options for individual stores code snippets.
 *
 * @since 3.3.5
 */
class SnippetOptionsRecord extends AOption {

	/**
	 * @var string Name of the option for all code snippets.
	 *
	 * The option as a following structure:
	 *
	 * array(
	 *    'snippets' => array( $snippet_data, ... )
	 * )
	 *
	 * For the structure of $snippet_data, check the SnippetOption class.
	 */
	const SNIPPET_OPTION_NAME = 'toolset_code_snippet_options';


	/**
	 * Returns the option key
	 *
	 * @return string
	 */
	public function getKey() {
		return self::SNIPPET_OPTION_NAME;
	}


	/**
	 * @inheritdoc
	 * @return bool
	 */
	protected function isAlwaysAutoloaded() {
		return true;
	}


}
