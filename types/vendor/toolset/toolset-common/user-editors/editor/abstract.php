<?php
/**
 * Abstract Editor class.
 *
 * @since 2.5.0
 */


abstract class Toolset_User_Editors_Editor_Abstract
	implements Toolset_User_Editors_Editor_Interface {

	protected $id;
	protected $name;
	protected $option_name = '_toolset_user_editors_editor_default';
	protected $logo_class;
	protected $logo_image_svg;

	/**
	 * All possible screens.
	 *
	 * @var Toolset_User_Editors_Editor_Screen_Interface[]
	 */
	protected $screens;

	/** @var Toolset_User_Editors_Medium_Interface */
	protected $medium;

	/** @var Toolset_Constants */
	protected $constants;

	/** @var Toolset_Common_Bootstrap */
	protected $tc_bootstrap;

	/** @var Toolset_Condition_Plugin_Views_Active */
	protected $is_views_active;

	/**
	 * Toolset_User_Editors_Editor_Abstract constructor.
	 *
	 * @param \Toolset_User_Editors_Medium_Interface $medium
	 * @param \Toolset_Common_Bootstrap              $tc_bootstrap
	 * @param \Toolset_Constants                     $constants
	 * @param \Toolset_Condition_Plugin_Views_Active $is_views_active
	 */
	public function __construct(
		\Toolset_User_Editors_Medium_Interface $medium,
		\Toolset_Common_Bootstrap $tc_bootstrap,
		\Toolset_Constants $constants,
		\Toolset_Condition_Plugin_Views_Active $is_views_active
	) {
		$this->medium = $medium;

		$this->constants = $constants;

		$this->tc_bootstrap = $tc_bootstrap;

		$this->is_views_active = $is_views_active;
	}

	/**
	 * Initialize basic editor logic.
	 *
	 * Note that this runs even if the user editor is not available
	 * because dependencies are not met.
	 *
	 * @since 3.4.8
	 */
	public function initialize() {}

	/**
	 * Support extra postmeta required when assigning or unassigning the user editor.
	 *
	 * Each chldren class might implement this and even extend it for further actions.
	 *
	 * @since Views 3.1
	 */
	public function set_extra_postmeta_support() {
		if (
			isset( $this->medium )
			&& $this->medium->get_id()
		) {
			$this->update_extra_post_meta_from_request( $this->medium->get_id(), 'ct_editor_choice' );
		}

		add_action( 'wpv_content_template_duplicated', array( $this, 'update_extra_post_meta_from_clone' ), 10, 3 );
	}

	/**
	 * Add or remove extra postmeta when performing specific requests,
	 * like when switchign between user editors.
	 *
	 * @param int $post_id ID of the related CT
	 * @param string $key Key to look for in the reuest
	 * @since Views 3.1
	 */
	public function update_extra_post_meta_from_request( $post_id, $key ) {
		if ( ! array_key_exists( $key, $_REQUEST ) ) {
			return;
		}

		if ( $this->get_id() !== sanitize_text_field( wp_unslash( $_REQUEST[ $key ] ) ) ) {
			$this->delete_extra_post_meta( $post_id );
			return;
		}

		$this->update_extra_post_meta( $post_id );
	}

	/**
	 * Add or remove extra postmeta when cloning a CT.
	 *
	 * @param \WPV_Content_Template $cloned_ct The duplicate CT
	 * @param \WPV_Content_Template $original_ct The original CT
	 * @param array $cloned_ct_postmeta The list of postmeta that was cloned
	 * @since Views 3.1
	 */
	public function update_extra_post_meta_from_clone( $cloned_ct, $original_ct, $cloned_ct_postmeta ) {
		if ( ! array_key_exists( '_toolset_user_editors_editor_choice', $cloned_ct_postmeta ) ) {
			return;
		}

		if ( $this->get_id() === toolset_getarr( $cloned_ct_postmeta, '_toolset_user_editors_editor_choice' ) ) {
			$this->update_extra_post_meta( $cloned_ct->id );
		}
	}

	/**
	 * Generic method to set the extra postmeta for a given user editor.
	 * To be defined in children classes.
	 *
	 * @param int $post_id ID of the related CT
	 * @since Views 3.1
	 */
	public function update_extra_post_meta( $post_id ) {}

	/**
	 * Generic method to delete the extra postmeta for a given user editor.
	 * To be defined in children classes.
	 *
	 * @param int $post_id ID of the related CT
	 * @since Views 3.1
	 */
	public function delete_extra_post_meta( $post_id ) {}

	/**
	 * Initialize basic editor logic.
	 *
	 * Note that this runs only if the user editor is available
	 * because self::required_plugin_active has already run.
	 *
	 * @since 3.4.8
	 */
	public function after_editor_added() {}

	public function get_id() {
		return $this->id;
	}

	public function get_name() {
		return $this->name;
	}

	public function get_logo_class() {
		return $this->logo_class;
	}

	public function get_logo_image_svg() {
		return $this->logo_image_svg;
	}

	public function set_name( $name ) {
		return $this->name = $name;
	}

	public function get_option_name() {
		return $this->option_name;
	}

	public function required_plugin_active() {
		return false;
	}

	public function add_screen( $id, Toolset_User_Editors_Editor_Screen_Interface $screen ) {
		$screen->add_editor( $this );
		$screen->add_medium( $this->medium );
		$this->screens[$id] = $screen;
	}

	/**
	 * @param $id
	 *
	 * @return false|Toolset_User_Editors_Editor_Screen_Interface
	 */
	public function get_screen_by_id( $id ) {
		if( $this->screens === null )
			return false;

		if( array_key_exists( $id, $this->screens ) )
			return $this->screens[$id];

		return false;
	}
}

