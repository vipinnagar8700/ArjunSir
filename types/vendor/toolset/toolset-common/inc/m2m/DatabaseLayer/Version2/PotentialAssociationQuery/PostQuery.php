<?php

namespace OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\PotentialAssociationQuery;

/**
 * Potential association query for posts when using the second version of the database layer mode.
 *
 * Obviously, see the parent class for full context.
 *
 * @since 4.0
 */
class PostQuery extends \OTGS\Toolset\Common\Relationships\DatabaseLayer\PotentialAssociation\PostQuery {

	/**
	 * This key can be passed as an argument for the query to influence whether the "display as translated"
	 * mode should be enforced for all post types. That may be useful when using the query for the front-end.
	 */
	const FORCE_DISPLAY_AS_TRANSLATED_MODE_ARG = 'force_display_as_translated';


	/**
	 * @return bool
	 */
	private function should_force_display_as_translated_mode() {
		return ! array_key_exists( self::FORCE_DISPLAY_AS_TRANSLATED_MODE_ARG, $this->args )
			|| $this->args[ self::FORCE_DISPLAY_AS_TRANSLATED_MODE_ARG ];
	}

	/**
	 * @inheritDoci
	 */
	protected function alter_wpml_query_hooks_before_query() {
		if ( ! $this->should_force_display_as_translated_mode() ) {
			return;
		}

		// We now allow associations even for posts without a default language version.
		// That means, we need to query all posts of translatable types with the "display
		// as translated" mode even if their post type doesn't have this setting.
		//
		// The filter wpml_should_use_display_as_translated_snippet makes WPML apply the mode
		// in the WP admin but only for post types with the correct translatability setting.
		add_filter( 'wpml_should_force_display_as_translated_snippet', $this->return_true );
	}


	/**
	 * @inheritDoc
	 */
	protected function alter_wpml_query_hooks_after_query() {
		if ( ! $this->should_force_display_as_translated_mode() ) {
			return;
		}

		remove_filter( 'wpml_should_force_display_as_translated_snippet', $this->return_true );
	}

}
