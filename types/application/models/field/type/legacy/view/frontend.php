<?php

/**
 * @since 2.3
 */
class Types_Field_Type_Legacy_View_Frontend extends Types_Field_Type_View_Frontend_Abstract {

	/**
	 * @var Types_Field_Type_Legacy
	 */
	protected $entity;


	/**
	 * Types_Field_Type_Legacy_View_Frontend constructor.
	 *
	 * @param Types_Field_Type_Legacy $entity
	 * @param array $params
	 */
	public function __construct( Types_Field_Type_Legacy $entity, $params = array() ) {
		$this->entity = $entity;
		$this->params = $this->normalise_user_values( $params );
	}


	/**
	 * @return string
	 */
	public function get_value() {
		$values_after_view_func = array();
		$_view_func = 'wpcf_fields_' . strtolower( $this->entity->get_type() ) . '_view';
		$field_data = array_merge( $this->entity->get_data_raw(), $this->params );

		if ( is_callable( $_view_func ) ) {
			foreach ( (array) $this->entity->get_value_filtered() as $value ) {
				$field_data['field_value'] = $value;
				$return_of_external_function = $_view_func( $field_data );

				if ( ! is_array( $return_of_external_function )
					&& $return_of_external_function !== '__wpcf_skip_empty' ) {
					$values_after_view_func[] = strval( $return_of_external_function );
				}
			}
		}

		if ( count( $values_after_view_func ) > 1 ) {
			$wpv_index = toolset_getarr( $this->params, 'index' );
			if ( $wpv_index !== null && $wpv_index !== '' ) {
				// Support for 'index' attribute
				$decorator_index = new Types_View_Decorator_Index();
				$values_after_view_func = $decorator_index->get_value( $values_after_view_func, $this->params );
			} else {
				$decorator_separator = new Types_View_Decorator_Separator();
				$values_after_view_func = $decorator_separator->get_value( $values_after_view_func, $this->params );
			}
		}


		$output = implode( '', (array) $values_after_view_func );

		return $this->filter_field_value_after_decorators( $output );
	}
}
