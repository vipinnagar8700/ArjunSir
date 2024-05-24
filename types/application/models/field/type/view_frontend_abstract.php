<?php

/**
 * @since 2.3
 */
abstract class Types_Field_Type_View_Frontend_Abstract implements Types_Interface_Value {

	/** @var Types_Field_Interface */
	protected $entity;

	/** @var array */
	protected $params;

	/**
	 * @var Types_Interface_Value[]
	 */
	protected $decorators = array();

	/**
	 * Checks if Types_View_Decorator_Output_HTML has been added, so in repetitive fields it is not added several times
	 *
	 * @var bool
	 * @since 3.0.7
	 */
	private $output_decorator_added = false;


	protected function add_decorator( Types_Interface_Value $decorator ) {
		$this->decorators[] = $decorator;
	}


	protected function get_decorated_value( $value, $dont_show_name = false ) {
		if ( ! $this->output_decorator_added && ! $dont_show_name && $this->is_html_output() ) {
			$this->add_decorator( new Types_View_Decorator_Output_HTML() );
			$this->output_decorator_added = true;
		}

		if ( empty( $this->decorators ) ) {
			return $value;
		}

		foreach ( $this->decorators as $decorator ) {
			if ( $decorator instanceof Types_Interface_Value_With_Context ) {
				$value = $decorator->get_value( $value, $this->params, $this->entity );
			} elseif ( $decorator instanceof Types_Interface_Value_With_Params ) {
				$value = $decorator->get_value( $value, $this->params );
			} else {
				$value = $decorator->get_value();
			}
		}

		return $value;
	}


	protected function to_string( $value ) {
		if ( empty( $value ) ) {
			return '';
		}

		if ( $this->entity->is_repeatable() ) {
			$index = toolset_getarr( $this->params, 'index' );
			if ( null === $index || '' === $index ) {
				$decorator_separator = new Types_View_Decorator_Separator();
				$value = $decorator_separator->get_value( $value, $this->params );
			} else {
				$decorator_index = new Types_View_Decorator_Index();
				$value = $decorator_index->get_value( $value, $this->params );
			}
		}

		return is_array( $value )
			? implode( '', $value )
			: $value;
	}


	protected function filter_field_value_after_decorators( $value_after_decorators, $value_before_decorators = null ) {
		if ( ! function_exists( 'apply_filters' ) ) {
			return $value_after_decorators;
		}

		$value_before_decorators = $value_before_decorators !== null
			? $value_before_decorators
			: $value_after_decorators;

		return apply_filters(
			'types_view',
			$value_after_decorators,
			$value_before_decorators,
			$this->entity->get_type(),
			$this->entity->get_slug(),
			$this->entity->get_title(),
			$this->params
		);
	}


	/**
	 * Checks if user wants raw output
	 *
	 * @return bool
	 */
	public function is_raw_output() {
		// first check "output" param
		if ( isset( $this->params['output'] ) && $this->params['output'] === 'raw' ) {
			return true;
		}

		// check legacy "raw" param
		// (the "&& $this->params['raw']" part is important, because user write things like '... raw="false" ...')
		if ( isset( $this->params['raw'] ) && $this->params['raw'] ) {
			return true;
		}

		return false;
	}


	/**
	 * Check if user wants html output
	 * You may think that there is only 'raw' & 'html' but that's not the case. 'html' adds an extra div surrounding
	 * with a bunch of css class built out of the name and type of the field. 'raw' just outputs the value, e.g. for
	 * an image it would be just the url. Whereas neither 'raw' or 'html' would output the image tag and 'html' would
	 * add another div surrounding the image with extra classes.
	 *
	 * @return bool
	 */
	protected function is_html_output() {
		if ( $this->is_raw_output() ) {
			// 'raw' and 'html' do not work together... raw has higher priority
			return false;
		}

		if ( isset( $this->params['output'] ) && $this->params['output'] === 'html' ) {
			return true;
		}

		return false;
	}


	/**
	 * Gets field when show_name is active
	 *
	 * @param string $value Field Value.
	 *
	 * @return string
	 * @since 3.0.5
	 */
	protected function maybe_show_field_name( $value ) {
		$show_name_param = toolset_getarr( $this->params, 'show_name', null );
		if (
			$show_name_param // it could be false or null
			&& (
				// The next line should ideally be `'true' === $show_name_param` but we were checking only
				// the parameter existence until now - keeping it for backward compatibility
				'if-not-empty' !== $show_name_param
				|| ( 'if-not-empty' === $show_name_param && ! empty( $value ) )
			)
			&& ! $this->is_html_output()
		) {
			$placeholder_field = new Types_View_Placeholder_Field();
			$value = $placeholder_field->replace( 'FIELD_NAME: FIELD_VALUE', $this->entity, $value );
		}

		return $value;
	}


	/**
	 * Users like to use "false" (as string) or "no" as parameter values.
	 * Let's normalise these values to save a lot of checks afterwards.
	 *
	 * @param array $atts List of attributes.
	 *
	 * @return array
	 */
	protected function normalise_user_values( $atts ) {
		array_walk( $atts, static function ( &$value ) {
			if ( $value === 'false' || $value === 'no' ) {
				$value = false;
			} elseif ( 'true' === $value ) {
				$value = true;
			}
		} );

		return $atts;
	}


	/**
	 * Gets initial rendered values
	 *
	 * @return array|string
	 * @since 3.0.7
	 */
	protected function get_initial_rendererd_values() {
		$is_html_output = $this->is_html_output();
		$values = $this->entity->get_value_filtered( $this->params );
		if ( empty( $values ) ) {
			return '';
		}
		// Transform each value to HTML
		$rendered_values = array();
		$decorator_html = new Types_View_Decorator_Output_HTML();
		foreach ( $values as $value ) {
			$value = $this->filter_field_value_after_decorators( $this->get_decorated_value( $value, $is_html_output ), $value );
			if ( $is_html_output && $value ) {
				$value = $decorator_html->get_value( $value, $this->params, $this->entity, true, true );
			}
			$rendered_values[] = $value;
		}

		return $rendered_values;
	}


	/**
	 * Gets rendered values, common in all fields
	 *
	 * @param string[] $rendered_values
	 */
	protected function get_rendered_value( $rendered_values ) {
		$is_html_output = $this->is_html_output();

		if ( $this->empty_values( $rendered_values ) ) {
			return '';
		}

		$index = toolset_getarr( $this->params, 'index' );
		if ( null === $index || '' === $index ) {
			$decorator_separator = new Types_View_Decorator_Separator();
			$rendered_value = $decorator_separator->get_value( $rendered_values, $this->params );
		} else {
			$decorator_index = new Types_View_Decorator_Index();
			$rendered_value = $decorator_index->get_value( $rendered_values, $this->params );
		}
		if ( $is_html_output ) {
			$decorator_html = new Types_View_Decorator_Output_HTML();
			$rendered_value = $decorator_html->get_value( $rendered_value, $this->params, $this->entity );
		}

		return $this->maybe_show_field_name( $rendered_value );
	}


	/**
	 * Returns if the array is empty or full of empty values
	 *
	 * @param array $values
	 *
	 * @return boolean
	 * @since 3.0.7
	 */
	protected function empty_values( $values ) {
		if ( ! is_array( $values ) ) {
			$values = array( $values );
		}

		return ! $this->has_value( $values );
	}


	/**
	 * Loop function for $this->empty_values() to check if the array has any value
	 *
	 * @param array $arr
	 *
	 * @return bool
	 */
	private function has_value( $arr ) {
		foreach ( $arr as $val ) {
			if ( is_array( $val ) ) {
				if ( $this->has_value( $val ) ) {
					return true;
				}

				continue;
			}

			if ( $val === '0' ) {
				// 0 is also a valid value
				return true;
			}

			if ( ! empty( $val ) ) {
				// not empty
				return true;
			}
		}

		// empty array
		return false;
	}
}
