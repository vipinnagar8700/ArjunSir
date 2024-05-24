<?php

namespace OTGS\Toolset\Common\Utils\Condition\Plugin\Gutenberg;


/**
 * Safely determine whether the block editor should be used for a particular post.
 *
 * Works with both the Gutenberg plugin and the native block editor in core.
 * Note that set_post() must be called before is_met().
 *
 * @since Types 3.2.2
 */
class IsUsedForPost extends \Toolset_Condition_Plugin_Gutenberg_Active {

	/** @var \WP_Post|int */
	private $post;


	/**
	 * Set the post for which the condition will be performed.
	 *
	 * @param \WP_Post|int $post
	 */
	public function set_post( $post ) {
		$this->post = $post;
	}


	/**
	 * @inheritdoc
	 *
	 * @return bool
	 */
	public function is_met() {
		if( ! parent::is_met() ) {
			// No block editor.
			return false;
		}

		if( function_exists( 'use_block_editor_for_post' ) ) {
			// Native block editor, part of the core
			return use_block_editor_for_post( $this->post );
		}

		if( function_exists( 'gutenberg_can_edit_post' ) ) {
			// Gutenberg plugin.
			return gutenberg_can_edit_post( $this->post );
		}

		// Unable to determine.
		return false;
	}


}
