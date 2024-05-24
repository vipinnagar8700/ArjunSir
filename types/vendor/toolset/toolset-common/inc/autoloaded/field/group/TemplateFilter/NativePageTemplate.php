<?php

namespace OTGS\Toolset\Common\Field\Group\TemplateFilter;

/**
 * Filter by a native WordPress template.
 *
 * @since Types 3.3
 */
class NativePageTemplate implements TemplateFilterInterface {


	/** @var string */
	private $template_name;


	/**
	 * NativePageTemplate constructor.
	 *
	 * @param string $template_name Name of the template file.
	 * @throws \InvalidArgumentException If an obviously invalid $template_name is provided.
	 */
	public function __construct( $template_name ) {
		if ( ! is_string( $template_name ) ) {
			throw new \InvalidArgumentException();
		}

		$this->template_name = $template_name;
	}


	/**
	 * @param \IToolset_Post $post
	 *
	 * @return bool True if the template matches given post.
	 */
	public function is_match_for_post( \IToolset_Post $post ) {
		return ( $this->template_name === $post->get_assigned_native_page_template() );
	}


	/**
	 * @inheritDoc
	 * @param string $post_type_slug
	 * @return bool
	 * @since Types 3.3.4
	 */
	public function is_default_for_post_type( $post_type_slug ) { // phpcs:disable VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
		return false;
	}
}
