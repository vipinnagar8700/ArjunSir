<?php
/**
 * Editor class for the Divi Builder.
 *
 * Handles all the functionality needed to allow the Divi Builder to work with Content Template editing.
 *
 * @since 2.5.0
 */

class Toolset_User_Editors_Editor_Gutenberg
	extends Toolset_User_Editors_Editor_Abstract {

	const GUTENBERG_SCREEN_ID = 'gutenberg';

	/**
	 * @var string
	 */
	protected $id = self::GUTENBERG_SCREEN_ID;

	/**
	 * @var string
	 */
	protected $name = 'Gutenberg';

	public function initialize() {
		add_action( 'init', array( $this, 'add_support_for_ct_edit_by_gutenberg_editor' ), 9 );
	}

	public function required_plugin_active() {
		$views_active = new Toolset_Condition_Plugin_Views_Active();
		$gutenberg_active = new Toolset_Condition_Plugin_Gutenberg_Active();

		if (
			$views_active->is_met() &&
			$gutenberg_active->is_met()
		) {
			$this->name = __( 'Block Editor', 'wpv-views' );
			return true;
		}

		return false;
	}

	public function run() {}

	public function add_support_for_ct_edit_by_gutenberg_editor() {
		add_filter( 'register_post_type_args', array( $this, 'make_ct_editable_by_gutenberg_editor' ), 10, 2 );
	}

	/**
	 * For the "view-template" custom post type to be editable by the native post editor, we need to temporarily set
	 * the "show_ui" argument that is used during the custom post type registration to true.
	 *
	 * @param  array  $args The arguments of the custom post type for its registration.
	 * @param  string $name The name of the custom post type to be registered.
	 *
	 * @return mixed        The filtered arguments.
	 *
	 * @since 2.5.0
	 * @since Views 3.0 Also include the show_ui argument so WPML can create new CT instances as translations.
	 */
	public function make_ct_editable_by_gutenberg_editor( $args, $name ) {
		if ( 'view-template' === $name ) {
			$args['show_in_rest'] = true;
			$args['supports'][] = 'custom-fields';
			$args['show_ui'] = true;
		}
		return $args;
	}

}
