<?php

/**
 * Class Types_View_Decorator_Skype_Legacy
 *
 * @since 2.3
 * @codeCoverageIgnore legacy copy
 */
class Types_View_Decorator_Skype_Legacy implements Types_Interface_Value_With_Params {

	/**
	 *
	 * @param array|string $value
	 * @param array $params
	 *  'class' => add css class
	 *
	 * @return string
	 */
	public function get_value( $value = '', $params = array() ) {
		if ( empty( $value ) ) {
			return '';
		}

		while( is_array( $value ) ) {
			$value = array_shift( $value );
		}

		$css_class = isset( $params['class'] ) && ! empty( $params['class'] )
			? ' class="' . $params['class'] . '"'
			: '';

		$params['button_style'] = isset( $params['button_style'] ) ? $params['button_style'] : '';

		switch ( $params['button_style'] ) {

			case 'btn1':
				$icon = '<img src="//download.skype.com/share/skypebuttons/buttons/call_green_white_153x63.png"' .
				        ' style="border: none;" width="153" height="63" alt="Skype Me™!"' . $css_class . ' />';
				break;

			case 'btn4':
				$icon = '<img src="//download.skype.com/share/skypebuttons/buttons/call_blue_transparent_34x34.png"' .
				       ' style="border: none;" width="34" height="34" alt="Skype Me™!"' . $css_class . ' />';
				break;

			case 'btn3':
				$icon = '<img src="//download.skype.com/share/skypebuttons/buttons/call_green_white_92x82.png"' .
				        ' style="border: none;" width="92" height="82" alt="Skype Me™!"' . $css_class . ' />';
				break;

			default:
				$icon = '<img src="//download.skype.com/share/skypebuttons/buttons/call_blue_white_124x52.png"' .
				        ' style="border: none;" width="124" height="52" alt="Skype Me™!"' . $css_class . ' />';
				break;
		}

		return '<script type="text/javascript" src="//download.skype.com/share/skypebuttons/js/skypeCheck.js"></script>' .
		       '<a href="skype:' . $value . '?call">' . $icon . '</a>';
	}


}
