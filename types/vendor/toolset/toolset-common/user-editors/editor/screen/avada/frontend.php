<?php

/**
 * Editor class for the frontend editor screen of Avada Builder.
 *
 * Handles all the functionality needed to allow the Avada Builder to work with Content Template editing on the frontend.
 *
 * @since 3.0.4
 */

class Toolset_User_Editors_Editor_Screen_Avada_Frontend
	extends Toolset_User_Editors_Editor_Screen_Abstract {

	/**
	 * @var Toolset_Constants
	 */
	protected $constants;

	/**
	 * Toolset_User_Editors_Editor_Screen_Avada_Frontend constructor.
	 *
	 * @param Toolset_Constants|null $constants
	 */
	public function __construct( Toolset_Constants $constants = null ) {
		$this->constants = $constants
			? $constants
			: new Toolset_Constants();
	}

	public function initialize() {
		add_filter( 'wpv_filter_wpv_get_third_party_css', array( $this, 'set_custom_css_for_ct' ), 10, 2 );
	}

	/**
	 * Set the custom CSS of a Content Template built with Avada Builder, when a page assigned to that CT is loaded.
	 *
	 * @param string   $custom_css   The page's custom CSS.
	 * @param int      $template_id  The Content Template id.
	 *
	 * @return string  The filtered custom CSS containing the Custom CSS of the CT used.
	 */
	public function set_custom_css_for_ct( $custom_css, $template_id ) {
		if ( $this->is_avada_builder_used_in_ct( $template_id ) ) {
			// The CSS written inside the Views Content Template editor needs to be completely skipped and it should be
			// replaced by the CSS written in relevant section of the Avada builder.
			$custom_css = get_post_meta( $template_id, '_fusion_builder_custom_css', true );
		}

		return $custom_css;
	}

	/**
	 * Detect if Avada builder is used on the defined CT.
	 *
	 * @param  int   $template_id   The id of the CT.
	 *
	 * @return bool  Return true if Avada Builder is used for the defined CT or false otherwise.
	 */
	public function is_avada_builder_used_in_ct( $template_id ) {
		$avada_builder_enabled = false;
		if ( ! empty( $template_id ) ) {
			$builder_selected = get_post_meta( $template_id, '_toolset_user_editors_editor_choice', true );
			if ( Toolset_User_Editors_Editor_Avada::AVADA_SCREEN_ID === $builder_selected ) {
				$avada_builder_enabled = true;
			}
		}

		return $avada_builder_enabled;
	}
}
