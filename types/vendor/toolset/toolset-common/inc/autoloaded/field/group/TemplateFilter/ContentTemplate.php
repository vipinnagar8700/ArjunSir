<?php

namespace OTGS\Toolset\Common\Field\Group\TemplateFilter;

use OTGS\Toolset\Common\Utils\ContentTemplateInterface;

/**
 * Represents a filter by a Content Template from Toolset.
 *
 * @since Types 3.3
 */
class ContentTemplate implements TemplateFilterInterface {


	/** @var \WP_Post */
	private $template_post;

	/** @var ContentTemplateInterface|null|false */
	private $template_model = false;


	/**
	 * ContentTemplate constructor.
	 *
	 * @param \WP_Post $template_post Post holding the content template.
	 */
	public function __construct( \WP_Post $template_post ) {
		$this->template_post = $template_post;
	}


	/**
	 * @param \IToolset_Post $post
	 *
	 * @return bool True if the template matches given post.
	 */
	public function is_match_for_post( \IToolset_Post $post ) {
		return ( $this->template_post->ID === $post->get_assigned_content_template() );
	}


	/**
	 * @inheritDoc
	 *
	 * @param string $post_type_slug
	 *
	 * @return bool
	 * @since Types 3.3.4
	 */
	public function is_default_for_post_type( $post_type_slug ) {
		$template = $this->get_content_template_model();
		if (
			null === $template
			|| ( ! $template instanceof ContentTemplateInterface && get_class( $template ) !== 'WPV_Content_Template' )
		) {
			return false;
		}

		$assigned_post_types = $template->get_assigned_single_post_type_slugs();

		return in_array( $post_type_slug, $assigned_post_types, true );
	}


	/**
	 * @return ContentTemplateInterface|null Note that the object returned may actually not implement
	 *     ContentTemplateInterface but it must implement all of its methods.
	 * @since Types 3.3.4
	 */
	private function get_content_template_model() {
		if ( false === $this->template_model ) {
			$template = apply_filters( 'wpv_get_content_template_model', null, $this->template_post );
			// Can't do this yet, check WPV_Content_Template for details:
			// $this->template_model = ( $template instanceof ContentTemplateInterface ? $template : null );
			// instead:
			$this->template_model = $template;
		}

		return $this->template_model;
	}
}
