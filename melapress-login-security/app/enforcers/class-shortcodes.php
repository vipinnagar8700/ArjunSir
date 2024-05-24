<?php // phpcs:disable WordPress.Files.FileName.InvalidClassFileName
/**
 * PPM New User Register
 *
 * @package WordPress
 * @subpackage wpassword
 * @author Melapress
 */

namespace PPMWP;

use \PPMWP\Helpers\OptionsHelper;

/**
 * Check if this class already exists.
 */
if ( ! class_exists( '\PPMWP\PPM_Shortcodes' ) ) {

	/**
	 * Declare PPM_Shortcodes Class
	 */
	class PPM_Shortcodes {

		/**
		 * Init hooks.
		 */
		public function init() {
			// Only load further if needed.
			if ( ! OptionsHelper::get_plugin_is_enabled() ) {
				return;
			}

			add_shortcode( 'ppmwp-custom-form', array( $this, 'custom_form_shortcode' ) );
		}

		/**
		 * Simple function to add custom form support via a shortcode to avoid
		 * loading assets on all front-end pages.
		 *
		 * @param  array $atts Attributes (css classes, IDs) passed to shortcode.
		 */
		public function custom_form_shortcode( $atts ) {
			$shortcode_attributes = shortcode_atts(
				array(
					'element'          => '',
					'button_class'     => '',
					'elements_to_hide' => '',
				),
				$atts,
				'ppmwp-custom-form'
			);

			$custom_forms = new \PPMWP\PPM_WP_Forms();
			$custom_forms->enable_custom_form( $shortcode_attributes );
		}
	}
}
