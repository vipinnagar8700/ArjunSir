<?php

/**
 * Handles view specific tasks for field "Select"
 *
 * @since 2.3
 */
class Types_Field_Type_Select_View_Frontend extends Types_Field_Type_View_Frontend_Abstract {

	/**
	 * Types_Field_Type_Select_View_Frontend constructor.
	 *
	 * @param Types_Field_Type_Select $entity
	 * @param array $params
	 */
	public function __construct( Types_Field_Type_Select $entity, $params = array() ) {
		$this->entity = $entity;
		$this->params = $this->normalise_user_values( $params );
	}

	/**
	 * @return string
	 */
	public function get_value() {
		$options = $this->entity instanceof Types_Field_Type_Select ? $this->entity->get_options() : [];

		if( empty( $options ) ) {
			return '';
		}

		foreach( $options as $option ) {
			if( $option->is_active() ) {
				$active_option = $option;
				break;
			}
		}

		if( ! isset( $active_option ) ) {
			return '';
		}

		$filtered = $active_option->get_value_filtered( $this->params );
		if( $filtered !== $active_option->get_value_raw() && $filtered !== $active_option->get_value() ) {
			// filter has highest priority
			return $this->maybe_show_field_name( $this->filter_field_value_after_decorators( $filtered ) );
		}

		if ( $this->is_raw_output() ) {
			return $this->maybe_show_field_name( $this->filter_field_value_after_decorators( $active_option->get_value_raw() ) );
		}

		if( isset( $this->params['option'] ) ) {
			return $this->maybe_show_field_name( $this->filter_field_value_after_decorators( $this->get_user_value( $active_option ) ) );
		}

		$value = $this->translate_option( $active_option );
		$value = $this->filter_field_value_after_decorators( $value );
		$value = $this->get_decorated_value( $value );
		return $this->maybe_show_field_name( $value );
	}

	/**
	 * @param Types_Field_Part_Option $active_option
	 *
	 * @return mixed|string
	 */
	private function get_user_value( Types_Field_Part_Option $active_option ){
		/** @noinspection TypeUnsafeComparisonInspection */
		if( $active_option->get_id() != $this->params['option'] ) {
			return '';
		}

		return isset( $this->params['content'] )
			? $this->params['content']
			: $active_option->get_value();
	}


	/**
	 * Translate option
	 *
	 * @param Types_Field_Part_Option $option
	 * @return string
	 * @since 3.0.7
	 */
	private function translate_option( Types_Field_Part_Option $option ) {
		$post_id = null;
		if ( ! empty( $this->params['id'] ) && is_numeric( $this->params['id'] ) ) {
			$post_id = $this->params['id'];
		} elseif ( ! empty( $this->params['post_id'] ) && is_numeric( $this->params['post_id'] ) ) {
			$post_id = $this->params['post_id'];
		} elseif ( ! empty( $this->params['item'] ) && is_numeric( $this->params['item'] ) ) {
			$post_id = $this->params['item'];
		}
		$lang = $post_id
			? Toolset_WPML_Compatibility::get_instance()->get_post_language( $post_id )
			: null;
		return wpcf_translate( $this->get_wpml_id( $option ), $option->get_value(), 'plugin Types', $lang );
	}


	/**
	 * Gets WPML id
	 *
	 * @param Types_Field_Part_Option $option
	 * @return string
	 * @since 3.0.7
	 */
	private function get_wpml_id( Types_Field_Part_Option $option ) {
		return "field {$this->entity->get_slug()} option {$option->get_id()} title";
	}

}
