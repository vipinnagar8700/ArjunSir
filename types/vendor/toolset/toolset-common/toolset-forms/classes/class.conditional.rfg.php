<?php

/**
 * Class WPToolset_Forms_Conditional_RFG
 *
 * Subclass of WPToolset_Forms_Conditional, which disables parent::__construct
 * and offers the method get_conditions() to allow the user getting all registered conditions.
 *
 * @since 2.6.5
 */
class WPToolset_Forms_Conditional_RFG extends WPToolset_Forms_Conditional {

	/**
	 * Other than the parent class, this does not need the $form_id
	 *
	 * @param string $form_selector Selector of the form.
	 * @param string $post_type
	 */
	public function __construct( $form_selector = '#post', $post_type = null ) {
		if ( $post_type ) {
			// important to run set_post_type before set_form_selectors
			$this->set_post_type( $post_type );
		}

		$this->set_form_selectors( $form_selector );
	}

	/**
	 * Get registered conditions (trigger & fields)
	 *
	 * @return array
	 */
	public function get_conditions() {
		$this->_parseData();

		$forms_conditions = array();
		foreach ( $this->form_selectors as $form_selector ) {
			$forms_conditions[ $form_selector ] = array(
				'triggers'        => $this->_triggers,
				'fields'          => $this->_fields,
				'custom_triggers' => $this->_custom_triggers,
				'custom_fields'   => $this->_custom_fields
			);
		}

		return $forms_conditions;
	}
}
