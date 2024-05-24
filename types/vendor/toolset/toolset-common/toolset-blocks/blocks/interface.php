<?php


/**
 * Interface for a Toolset Gutenberg Block
 *
 * @since 2.5.0
 */
interface Toolset_Gutenberg_Block_Interface {

	/**
	 * Block initialization.
	 *
	 * @return void
	 */
	public function init_hooks();

	/**
	 * Block editor asset registration.
	 *
	 * @return void
	 */
	public function register_block_editor_assets();

	/**
	 * Server side block registration.
	 *
	 * @return void
	 */
	public function register_block_type();
}
