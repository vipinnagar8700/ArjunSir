<?php
/**
 * Editor class for the WPBakery Page Builder (former Visual Composer).
 *
 * Handles all the functionality needed to allow the WPBakery Page Builder (former Visual Composer) to work with Content Template editing.
 *
 * @since 2.5.0
 */

class Toolset_User_Editors_Editor_Visual_Composer
	extends Toolset_User_Editors_Editor_Abstract {

	const VC_SCREEN_ID = 'vc';

	/**
	 * @var string
	 */
	protected $id = self::VC_SCREEN_ID;

	/**
	 * @var string
	 */
	protected $name = 'WPBakery Page Builder';

	/**
	 * @var string
	 */
	protected $option_name = '_toolset_user_editors_vc';

	/**
	 * @var string
	 */
	protected $logo_image_svg = 'vc.svg';

	/**
	 * Minimum Version
	 * @var string version number
	 */
	protected $minimum_version = '4.11';

	/**
	 * Minimum WP Bakery page builder Version (new branding)
	 * @var string version number
	 */
	protected $minimum_wp_bakery_pb_version = '5.4';

	/**
	 * Minimum WP Bakery page builder Version using the legacy editor container ID
	 * @var string version number
	 */
	protected $minimum_wp_wpbakery_container_id = '6.8.0';

	/**
	 * WP Bakery page builder editor container ID
	 * @var string version number
	 */
	protected $container_id = 'wpb_wpbakery';

	public function required_plugin_active() {

		if ( ! apply_filters( 'toolset_is_views_available', false ) ) {
			return false;
		}

		if( ! defined( 'WPB_VC_VERSION' ) )
			return false;

		// version too low
		// Todo generalise prove of version and move to abstract for all editors
		if( version_compare( WPB_VC_VERSION, $this->minimum_version ) < 0 ) {
			add_filter( 'wpv_ct_control_switch_editor_buttons', array( $this, 'add_disabled_button' ) );
			return false;
		}

		if( version_compare( WPB_VC_VERSION, $this->minimum_wp_bakery_pb_version ) < 0 ) {
			$this->name = 'Visual Composer';
			$this->logo_image_svg = 'vc_old.svg';
		}

		if( version_compare( WPB_VC_VERSION, $this->minimum_wp_wpbakery_container_id ) < 0 ) {
			$this->container_id = 'wpb_visual_composer';
		}

		return true;
	}

	public function run() {
		// register medium slug
		add_filter( 'vc_check_post_type_validation', array( $this, 'support_medium' ), 10, 2 );
	}

	/**
	 * If version requirements does not met, we show a hint.
	 *
	 * @param $buttons
	 * @return array
	 */
	public function add_disabled_button( $buttons ) {
		$buttons[] = '<button class="button-secondary" onClick="javascript:alert( jQuery( this ).attr( \'title\' ) );" title="' . sprintf( __( 'Version %s or higher required', 'wpv-views' ), $this->minimum_version ) . '">' . $this->name . '</button>';
		$buttons = array_reverse( $buttons );
		return $buttons;
	}

	/**
	 * We need to add Views type of content templates
	 * to the allowed types of WPBakery Page Builder (former Visual Composer).
	 *
	 *
	 * @param $default
	 * @param $type
	 *
	 * @return bool
	 */
	public function support_medium( $default, $type ) {
		if( $type == $this->medium->get_slug() )
			return true;

		return $default;
	}

	public function get_container_id() {
		return $this->container_id;
	}
}
