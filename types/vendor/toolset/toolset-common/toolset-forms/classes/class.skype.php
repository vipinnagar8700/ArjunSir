<?php

require_once 'class.textfield.php';

class WPToolset_Field_Skype extends WPToolset_Field_Textfield {

	public function init() {
		$this->set_placeholder_as_attribute();
	}

	public function metaform() {
		$maybe_legacy_value = $value = $this->getValue();

		if( is_array( $maybe_legacy_value ) && isset( $maybe_legacy_value['skypename'] ) ) {
			$value = $maybe_legacy_value['skypename'];
		}

		$attributes = $this->getAttr();
		$shortcode_class = array_key_exists( 'class', $attributes ) ? $attributes['class'] : "";
		$attributes['class'] = "js-wpt-skypename js-wpt-cond-trigger regular-text form-control {$shortcode_class}"; // What is this js-wpt-cond-trigger classname for?

		$wpml_action = $this->getWPMLAction();

		$form = array();
		$form[] = array(
			'#type' => 'textfield',
			'#title' => $this->getTitle(),
			'#description' => $this->getDescription(),
			'#name' => $this->getName(),
			'#value' => $value,
			'#validate' => $this->getValidationData(),
			'#attributes' => $attributes,
			'#repetitive' => $this->isRepetitive(),
			'wpml_action' => $wpml_action,
		);

		if ( ! Toolset_Utils::is_real_admin() ) {
			return $form;
		}

		return $form;
	}

	/**
	 * No edit dialog anymore, just keeping this to prevent any fatal error as it's public
	 *
	 * @deprecated 3.1
	 */
	public function editButtonTemplate() {
		echo '';
	}

	public function editform( $config = null ) {

	}

	public function mediaEditor() {
		return array();
	}

}
