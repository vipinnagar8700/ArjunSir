<?php

/**
 * Handles view specific tasks for field "WYSIWYG"
 *
 * @since 2.3
 */
class Types_Field_Type_Wysiwyg_View_Frontend extends Types_Field_Type_View_Frontend_Abstract {

	/**
	 * Types_Field_Type_Wysiwyg_View_Frontend constructor.
	 *
	 * @param Types_Field_Type_Wysiwyg $entity
	 * @param array $params
	 */
	public function __construct( Types_Field_Type_Wysiwyg $entity, $params = array() ) {
		$this->entity = $entity;
		$this->params = $this->normalise_user_values( $params );
	}


	/**
	 * @return string
	 */
	public function get_value() {
		if ( ! $this->is_raw_output() ) {
			// as wysiwyg
			$this->add_decorator( new Types_View_Decorator_Wysiwyg(
				new Types_Wordpress_Filter()
			) );
		}

		// add support for 3rd party syntaxhighlighter
		$this->params['syntax_highlighter'] = new Types_Wordpress_3rd_Syntaxhighlighter();

		$rendered_value = array();
		$values = (array) $this->entity->get_value_filtered( $this->params );
		if ( $this->empty_values( $values ) ) {
			return '';
		}
		foreach ( $values as $value ) {
			$rendered_value[] = $this->filter_field_value_after_decorators(
				$this->get_decorated_value( $value ),
				$value
			);
		}

		$value = $this->to_string( $rendered_value );

		return $this->maybe_show_field_name( $value );
	}
}
