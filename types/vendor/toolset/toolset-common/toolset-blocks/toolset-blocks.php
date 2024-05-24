<?php
/**
 * Handles the creation and initialization of the all the Gutenberg integration stuff.
 *
 * @since 2.6.0
 */
class Toolset_Blocks {
	const TOOLSET_GUTENBERG_BLOCKS_CATEGORY_SLUG = 'toolset';

	public function load_blocks() {
		$gutenberg_active = new Toolset_Condition_Plugin_Gutenberg_Active();

		if ( ! $gutenberg_active->is_met() ) {
			return;
		}

		$this->init_hooks();
	}

	/**
	 * Initialize common hooks for the Toolset Gutenberg blocks.
	 */
	public function init_hooks() {
		global $wp_version;
		if ( version_compare( $wp_version, '5.7.2', '<=' ) ) {
			add_filter( 'block_categories', array( $this, 'register_toolset_block_category' ) );
		} else {
			add_filter( 'block_categories_all', array( $this, 'register_toolset_block_category' ) );
		}
	}

	/**
	 * Registers the Toolset Gutenberg blocks category.
	 *
	 * @param array $categories The array with the categories of the Gutenberg widgets.
	 *
	 * @return array
	 */
	public function register_toolset_block_category( $categories ) {
		if ( ! array_search( 'toolset', array_column( $categories, 'slug' ), true ) ) {
			$categories = array_merge(
				$categories,
				array(
					array(
						'slug'  => 'toolset',
						'title' => __( 'Toolset', 'wpv-views' ),
					),
				)
			);
		}

		return $categories;
	}
}
