<?php

namespace OTGS\Toolset\Common\Condition;

/**
 * Check whether we are on an admin editor page using the Gutenberg editor.
 *
 * @since 3.2.6 (~Types 3.2)
 */
class IsInGutenbergEditor extends \Toolset_Condition_Plugin_Gutenberg_Active {


	/**
	 * @return bool
	 */
	public function is_met() {

		// Checks if Gutenberg is active either as a plugin or in Core.
		if ( ! parent::is_met() ) {
			// Gutenberg is not available on this site
			return false;
		}

		// Determines if the current page is edited by Gutenberg for the case where Gutenberg is a plugin.
		/** @noinspection PhpUndefinedFunctionInspection */
		$is_plugin_gutenberg_page = (
			is_callable( 'is_gutenberg_page' )
			&& is_gutenberg_page()
		);

		// Determines if the current page is edited by Gutenberg for the case where Gutenberg is in Core.
		global $post;
		/**
		 * @noinspection PhpUndefinedFunctionInspection
		 * @noinspection RedundantSuppression
		 */
		$is_using_block_editor = (
			$post instanceof \WP_Post
			&& is_callable( 'use_block_editor_for_post_type' )
			&& use_block_editor_for_post_type( $post->post_type )
		);

		$is_gutenberg_user_editor_active = apply_filters( 'toolset_filter_toolset_gutenberg_user_editor_active', false );

		return ( $is_plugin_gutenberg_page || $is_using_block_editor || $is_gutenberg_user_editor_active );
	}


}
