<?php

/**
 * Class Types_Shortcode_Types_View
 *
 * @since 2.3
 */
class Types_Shortcode_Types_View implements Types_Shortcode_Interface_View  {

	/**
	 * @var Types_Shortcode_Types
	 */
	private $shortcode;


	public function __construct( Types_Shortcode_Types $types ) {
		$this->shortcode = $types;
	}

	/**
	 * Shortcode callback
	 *
	 * @param array $atts
	 * @param string|null $content
	 *
	 * @return string|null
	 */
	public function render( $atts, $content = null ) {
		try {
			return $this->shortcode->get_value( $atts, $content );

		} catch( Exception_Invalid_Shortcode_Attr_Item $e_invalid_item ) {
			if( current_user_can( 'manage_options' ) ) {
				// todo implement response for admins, see toolsetcommon-174
				// msg: No valid item
				return '';
			}

			// invalid shortcode, don't show anything to users
			return null;

		} catch( Exception_Invalid_Shortcode_Attr_Field $e_invalid_field ) {
			if( current_user_can( 'manage_options' ) ) {
				// todo implement response for admins, see toolsetcommon-174
				// msg: No valid field
				return '';
			}

			// invalid shortcode, don't show anything to users
			return null;
		}
	}
}
