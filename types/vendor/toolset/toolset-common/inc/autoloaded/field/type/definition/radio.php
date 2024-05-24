<?php

final class Toolset_Field_Type_Definition_Radio extends Toolset_Field_Type_Definition_Singular {

	public function __construct( $args ) {
		parent::__construct( Toolset_Field_Type_Definition_Factory::RADIO, $args );
	}


	/**
	 * @inheritdoc
	 *
	 * @param array $definition_array
	 * @return array
	 * @since 2.1
	 */
	protected function sanitize_field_definition_array_type_specific( $definition_array ) {
		$definition_array['type'] = Toolset_Field_Type_Definition_Factory::RADIO;

		$options = toolset_ensarr( toolset_getnest( $definition_array, array( 'data', 'options' ) ) );

		foreach( $options as $key => $option ) {
			if( 'default' === $key ) {
				continue;
			}

			$options[ $key ] = $this->sanitize_single_option( $option );
		}

		$default_option = toolset_getarr( $options, 'default' );
		if( !is_string( $default_option ) || !array_key_exists( $default_option, $options ) ) {
			$default_option = 'no-default';
		}
		$options['default'] = $default_option;

		$definition_array['data']['options'] = $options;

		return $definition_array;
	}


	/**
	 * Sanitize single checkboxes option definition.
	 *
	 * @param array $option
	 * @return array Sanitized option.
	 * @since 2.1
	 */
	private function sanitize_single_option( $option ) {
		$option = $this->sanitize_element_isset( toolset_ensarr( $option ), 'value' );
		$option = $this->sanitize_element_isset( $option, 'title', $option['value'] );
		$option = $this->sanitize_element_isset( $option, 'display_value', $option['value'] );
		return $option;
	}


	/**
	 * @inheritdoc
	 *
	 * @param string $purpose
	 * @param string $environment
	 * @param Toolset_Field_Instance $field
	 * @param array $renderer_args
	 *
	 * @return Toolset_Field_Renderer_Abstract
	 */
	public function get_renderer( $purpose, $environment, $field, $renderer_args = array() ) {
		if ( $purpose === Toolset_Field_Renderer_Purpose::PREVIEW ) {
			return new Toolset_Field_Renderer_Preview_Radio( $field, $renderer_args );
		}

		return parent::get_renderer( $purpose, $environment, $field, $renderer_args );
	}


	/**
	 * @inheritDoc
	 */
	public function filter_save_empty_setting( $value ) {
		return Toolset_Field_Definition::SAVE_EMPTY_NO;
	}


	/**
	 * @inheritDoc
	 */
	public function get_empty_field_value() {
		return 0;
	}

}
