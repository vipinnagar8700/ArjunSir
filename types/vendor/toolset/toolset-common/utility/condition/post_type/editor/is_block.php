<?php

/**
 * Toolset_Condition_Post_Type_Editor_Is_Block
 *
 * @since 3.3.0
 */
class Toolset_Condition_Post_Type_Editor_Is_Block implements Toolset_Condition_Interface {

	/** @var string */
	private $post_type;

	/**
	 * @param string $post_type
	 */
	public function set_post_type( $post_type ) {
		if ( ! is_string( $post_type ) || empty( $post_type ) ) {
			throw new InvalidArgumentException( '$post_type must be a non-empty string.' );
		}

		$this->post_type = $post_type;
	}

	/**
	 * Check if post type uses gutenberg
	 * If "set_post_type()" is not used before the currents screen post type is used (when available).
	 *
	 * @return bool
	 */
	public function is_met() {
		if ( $this->post_type === null ) {
			$this->post_type = $this->get_post_type_by_current_screen();
		}

		if ( ! $this->post_type ) {
			// no post type given
			throw new RuntimeException( 'No post type given for Toolset_Condition_Post_Type_Editor_Is_Block check.' );
		}

		if ( function_exists( 'use_block_editor_for_post_type' ) ) {
			// >= WP 5.0
			if ( use_block_editor_for_post_type( $this->post_type ) ) {
				// block editor active for this post type
				return true;
			}
		} else {
			// < WP 5.0, but we need to check if Gutenberg plugin is active
			if ( function_exists( 'gutenberg_can_edit_post_type' )
				 && gutenberg_can_edit_post_type( $this->post_type ) ) {
				// gutenberg plugin active and post type using block editor
				return true;
			}
		}

		// block editor not active for this post type
		return false;
	}

	/**
	 * Get post type of current screen.
	 *
	 * Note: this just works after init hook.
	 *
	 * @return bool
	 */
	private function get_post_type_by_current_screen() {
		if ( ! function_exists( 'get_current_screen' ) || ! $current_screen = get_current_screen() ) {
			// called to early
			return false;
		}

		return $current_screen->post_type;
	}
}
