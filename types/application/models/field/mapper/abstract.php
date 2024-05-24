<?php

/**
 * @since 2.3
 */
abstract class Types_Field_Mapper_Abstract implements Types_Field_Mapper_Interface {

	/**
	 * @var Types_Field_Factory_Interface
	 */
	protected $field_factory;


	/**
	 * @var Types_Field_Gateway_Interface
	 */
	protected $gateway;

	/**
	 * Types_Field_Mapper_Abstract constructor.
	 *
	 * @param Types_Field_Factory_Interface $factory
	 * @param Types_Field_Gateway_Interface $gateway
	 */
	public function __construct( Types_Field_Factory_Interface $factory, Types_Field_Gateway_Interface $gateway ) {
		$this->field_factory = $factory;
		$this->gateway = $gateway;
	}

	/**
	 * @param int $id
	 *
	 * @return null|array
	 */
	protected function database_get_field_by_id( $id ) {
		return $this->gateway->get_field_by_id( $id );
	}

	/**
	 * @param array $field
	 *
	 * @return array
	 */
	protected function map_common_field_properties( $field ) {
		if( isset( $field['name'] ) ) {
			$field['title'] = $field['name'];
		}

		if( ! isset( $field['data'] ) ) {
			// no data by the legacy structure
			return $field;
		}

		// reorder legacy structure to new
		if( isset( $field['data']['user_default_value'] ) ) {
			$field['default_value'] = $field['data']['user_default_value'];
		}

		if( isset( $field['data']['placeholder'] ) ) {
			$field['placeholder'] = $field['data']['placeholder'];
		}

		$field['repeatable'] = isset( $field['data']['repetitive'] ) && $field['data']['repetitive'];

		return $field;
	}


	/**
	 * @param int $id
	 * @param string $field_slug
	 * @param bool $repeatable
	 * @param bool $controlled
	 *
	 * @return array
	 */
	protected function get_user_value( $id, $field_slug, $repeatable = false, $controlled = false ) {
		return $this->gateway->get_field_user_value( $id, $field_slug, $repeatable, $controlled );
	}

	/**
	 * Returns if the field is controlled by Types
	 *
	 * @param array $field Field data.
	 * @return boolean
	 * @since 3.0
	 */
	protected function is_controlled_by_types( $field ) {
		return isset( $field['data']['controlled'] ) && $field['data']['controlled'];
	}

	/**
	 * Apply legacy filters to options (select and radio fields)
	 *
	 * @param array $field Field data.
	 * @param array $options List of options.
	 * @return array
	 * @since 3.0.2
	 */
	protected function apply_options_filter( $field, $options ) {
		// Needed for legacy code
		// @see wpt_field_options
		$legacy_options = array();
		$default = 'no-default';
		foreach ( $options as $option ) {
			if ( is_array( $option ) ) {
				if ( isset( $option['title'] ) ) {
					$legacy_options[] = array(
						'#title' => $option['title'],
						'#value' => $option['value'],
					);
				}
			} else {
				$default = $option;
			}
		}
		$sprevious_options = $legacy_options;
		// @link https://git.onthegosystems.com/toolset/types/wikis/hooks-reference/filters/wpt_field_options
		$legacy_options = apply_filters( 'wpt_field_options', $legacy_options, $field['name'], 'select' );
		if ( $legacy_options !== $sprevious_options && is_array(  $legacy_options ) ) {
			$options = array();
			foreach ( $legacy_options as $option ) {
				$id = 'wpcf-fields-select-option-' . wpcf_unique_id( serialize( $option ) );
				$options[ $id ] = array(
					'title' => $option['#title'],
					'value' => $option['#value'],
				);
			}
			$options['default'] = $default;
		}

		return $options;
	}
}
