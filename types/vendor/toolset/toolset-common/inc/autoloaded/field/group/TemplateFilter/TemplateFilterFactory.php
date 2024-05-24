<?php

namespace OTGS\Toolset\Common\Field\Group\TemplateFilter;

/**
 * Factory for creating an TemplateFilterInterface object based on the type of the template.
 *
 * @package OTGS\Toolset\Common\Field\Group\TemplateFilter
 */
class TemplateFilterFactory {


	/**
	 * @param string|int $template_name Post ID, native template name, content template slug...
	 *
	 * @return null|TemplateFilterInterface
	 */
	public function build_from_name( $template_name ) {
		$template_post = get_page_by_path( $template_name, OBJECT, 'view-template' );

		if ( $template_post instanceof \WP_Post ) {
			return new ContentTemplate( $template_post );
		}

		// Maybe we have the old format stored (which stores the template IDs).
		if ( is_numeric( $template_name ) ) {
			$template_post = get_post( $template_name );
			if ( $template_post instanceof \WP_Post ) {
				return new ContentTemplate( $template_post );
			}
		}

		// At this point it's probably a page template of the theme.
		if ( is_string( $template_name ) ) {
			return new NativePageTemplate( $template_name );
		}

		// Invalid value.
		return null;
	}
}
