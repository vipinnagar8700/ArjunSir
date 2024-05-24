<?php

/**
 * @since 2.3
 */
class Types_View_Decorator_Skype implements Types_Interface_Value_With_Params {

	/**
	 *
	 * @param array|string $value
	 * @param array $params
	 *
	 * @return string
	 */
	public function get_value( $value = '', $params = array() ) {
		if ( ! $receiver = $this->get_receiver( $params, $value ) ) {
			// no receiver given
			return '';
		}

		if ( ! is_array( $params ) ) {
			// invalid params
			return '';
		}

		// enqueue required script
		wp_enqueue_script( 'skype-sdk' );

		// button form
		$button_form = isset( $params['button'] )
			? strtolower( trim( $params['button'] ) )
			: 'bubble';

		// allowed forms without default
		$allowed_buttons_without_default = array( 'rectangle', 'rounded' );

		if ( ! in_array( $button_form, $allowed_buttons_without_default ) ) {
			// "bubble" selected. Or an unknown value -> use default
			return '<span class="skype-button bubble '
				. $this->get_extra_class( $params ) . '" '
				. $receiver
				. $this->get_button_color( $params ) . ' ></span>'
				. $this->get_chat_color( $params );
		}

		// 'rectangle' or 'rounded' form, which have a few more options
		return '<span class="skype-button ' . $button_form
			. $this->get_button_icon_class( $params )
			. $this->get_extra_class( $params ) . '" '
			. $receiver
			. $this->get_button_label( $params )
			. $this->get_button_color( $params ) . ' ></span>'
			. $this->get_chat_color( $params );
	}


	/**
	 * Get receiver, can be a skype user or a bot
	 *
	 * @param array $params
	 * @param int|string $id
	 *
	 * @return bool|string
	 */
	private function get_receiver( $params, $id ) {
		if ( empty( $id ) || ! is_string( $id ) ) {
			return false;
		}

		$receiver = isset( $params['receiver'] ) && ! empty( $params['receiver'] )
			? strtolower( trim( $params['receiver'] ) )
			: 'user';

		if ( $receiver === 'bot' ) {
			// bot
			return ' data-bot-id="' . $id . '"';
		}

		// we only support user or bot, so if anything else than bot -> use default (user)
		return ' data-contact-id="' . $id . '"';

	}


	/**
	 * Get the icon
	 *
	 * @param array $params
	 *
	 * @return string
	 */
	private function get_button_icon_class( $params ) {
		if ( ! isset( $params['button-icon'] ) || empty( $params['button-icon'] ) ) {
			return '';
		}

		$icon = strtolower( $params['button-icon'] );

		if ( $icon === 'disabled' ) {
			return ' textonly';
		}

		// default value / unknown value
		return '';
	}


	/**
	 * Get button label
	 *
	 * @param array $params
	 *
	 * @return string
	 */
	private function get_button_label( $params ) {
		if ( ! isset( $params['button-label'] ) || empty( $params['button-label'] ) ) {
			// default label
			return '';
		}

		return ' data-text="' . $params['button-label'] . '"';
	}


	/**
	 * Get color code of button
	 *
	 * @param array $params
	 *
	 * @return string
	 */
	private function get_button_color( $params ) {
		if ( ! isset( $params['button-color'] ) || empty( $params['button-color'] ) ) {
			return '';
		}

		// get rid of any unwanted spaces
		$color = trim( $params['button-color'] );

		if ( ! preg_match( '/^#([0-9a-f]{3}|[0-9a-f]{6})$/i', $color ) ) {
			// invalid color code
			return '';
		}

		return ' data-color="' . $color . '"';
	}


	/**
	 * Get color of chat
	 *
	 * @param array $params
	 *
	 * @return string
	 */
	private function get_chat_color( $params ) {
		if ( ! isset( $params['chat-color'] ) || empty( $params['chat-color'] ) ) {
			return '';
		}

		// get rid of any unwanted spaces
		$color = trim( $params['chat-color'] );

		if ( ! preg_match( '/^#([0-9a-f]{3}|[0-9a-f]{6})$/i', $color ) ) {
			// invalid color code
			return '';
		}

		return '<span class="skype-chat" data-color-message="' . $color . '"></span>';
	}


	/**
	 * Get extra class set by user
	 *
	 * @param array $params
	 *
	 * @return string
	 */
	private function get_extra_class( $params ) {
		if ( ! isset( $params['class'] ) || empty( $params['class'] ) ) {
			return '';
		}

		$class = trim( $params['class'] );

		return ' ' . $class;
	}
}
