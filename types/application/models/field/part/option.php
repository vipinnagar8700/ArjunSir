<?php

/**
 * @since 2.3
 */
class Types_Field_Part_Option implements Types_Field_Part_Interface, Types_Interface_Value {

	/**
	 * WPML ST id suffix
	 *
	 * @param string
	 *
	 * @since 3.0.5
	 */
	const WPML_SUFFIX_CHECKBOX_SELECTED = 'checkbox value selected';

	/**
	 * WPML ST id suffix
	 *
	 * @param string
	 *
	 * @since 3.0.5
	 */
	const WPML_SUFFIX_CHECKBOX_NOT_SELECTED = 'checkbox value not selected';

	/**
	 * WPML ST id suffix
	 *
	 * @param string
	 *
	 * @since 3.0.5
	 */
	const WPML_SUFFIX_GENERIC_SELECTED = 'display value selected';

	/**
	 * WPML ST id suffix
	 *
	 * @param string
	 *
	 * @since 3.0.5
	 */
	const WPML_SUFFIX_GENERIC_NOT_SELECTED = 'display value not selected';

	/** @var Types_Field_Interface */
	private $field;

	/** @var string|int */
	private $id;

	/** @var string */
	private $title;

	/** @var string */
	private $store_value;

	/**
	 * The user defined value, which should be shown if the checkbox is checked
	 *
	 * @var string
	 */
	private $display_value_checked;

	/**
	 * The user defined value, which should be shown if the checkbos is unchecked
	 *
	 * @var string
	 */
	private $display_value_unchecked;

	/** @var bool */
	private $is_checked;

	/** @var string|null */
	private $db_value;

	/**
	 * Field data
	 *
	 * @var array
	 * @since 3.0
	 */
	private $data;


	/**
	 * Types_Field_Part_Option constructor.
	 *
	 * @param Types_Field_Interface $field
	 * @param array $data
	 */
	public function __construct( Types_Field_Interface $field, $data ) {
		$this->field = $field;

		$this->set_id( $data );
		$this->set_title( $data );
		$this->set_stored_value( $data );
		$this->set_display_value_checked( $data );
		$this->set_display_value_unchecked( $data );
		$this->set_checked( $data );
		$this->set_db_value( $data );
		$this->data = $data;
	}


	/**
	 * @return string|int
	 */
	public function get_id() {
		return $this->id;
	}


	/**
	 * @return array
	 */
	public function get_data() {
		return $this->data;
	}


	/**
	 * Gets the option value
	 *
	 * @return string
	 */
	public function get_value() {
		$is_checkbox_or_checkboxes = $this->field instanceof Types_Field_Type_Checkboxes
			|| $this->field instanceof Types_Field_Type_Checkbox;

		if ( $is_checkbox_or_checkboxes || $this->is_active() ) {

			if ( $is_checkbox_or_checkboxes ) {
				if ( $this->field instanceof Types_Field_Type_Checkbox ) {
					$display_mode = $this->field->get_display_mode();
				} else {
					$display_mode = isset( $this->data['display'] )
						? $this->data['display']
						: Types_Field_Abstract::DISPLAY_MODE_DB;
				}

				if ( $display_mode === Types_Field_Abstract::DISPLAY_MODE_DB ) {
					return $this->db_value;
				}

				$value = $this->is_active()
					? $this->display_value_checked
					: $this->display_value_unchecked;

				if ( $this->field instanceof Types_Field_Type_Checkbox ) {
					$type = $this->is_active() ? self::WPML_SUFFIX_CHECKBOX_SELECTED
						: self::WPML_SUFFIX_CHECKBOX_NOT_SELECTED;

					return $this->get_translated_value( $value, $type, null, '' );
				}

				// Checkboxes
				$type = $this->is_active() ? self::WPML_SUFFIX_GENERIC_SELECTED
					: self::WPML_SUFFIX_GENERIC_NOT_SELECTED;

				return $this->get_translated_value( $value, $type );
			}

			if ( $this->field instanceof Types_Field_Type_Radio
				&& $this->field->get_display_mode() === Types_Field_Abstract::DISPLAY_MODE_DB ) {
				return $this->get_translated_value( $this->title );
			}

			$value = ! empty( $this->display_value_checked )
				? $this->display_value_checked
				: $this->store_value;

			return $this->get_translated_value( $value );
		}

		if ( ! empty( $this->display_value_unchecked ) ) {
			return $this->get_translated_value( $this->display_value_unchecked );
		}

		return '';
	}


	/**
	 * @return string
	 */
	public function get_value_raw() {
		return $this->get_translated_value( $this->db_value );
	}


	/**
	 * @return bool
	 */
	public function is_active() {
		return $this->is_checked !== false && $this->is_checked !== null;
	}


	/**
	 * @param array $data
	 */
	private function set_id( $data ) {
		if ( ! isset( $data['id'] ) ) {
			throw new InvalidArgumentException( 'Types_Field_Part_Option requires "id".' );
		}

		$this->id = $data['id'];
	}


	/**
	 * @param array $data
	 */
	private function set_checked( $data ) {
		if ( ! isset( $data['checked'] ) ) {
			$this->is_checked = false;

			return;
		}

		$this->is_checked = (bool) $data['checked'];
	}


	/**
	 * @param array $data
	 */
	private function set_db_value( $data ) {
		if ( ! isset( $data['db_value'] ) ) {
			$this->db_value = null;

			return;
		}

		while ( is_array( $data['db_value'] ) ) {
			$data['db_value'] = array_shift( $data['db_value'] );
		}

		$this->db_value = is_string( $data['db_value'] )
			? stripslashes( $data['db_value'] )
			: $data['db_value'];
	}


	/**
	 * @param array $data
	 */
	private function set_title( $data ) {
		$this->title = isset( $data['title'] )
			? stripslashes( $data['title'] )
			: '';
	}


	/**
	 * @param array $params
	 *
	 * @return mixed
	 */
	public function get_value_filtered( $params ) {
		$filtered = $this->field->get_value_filtered( $params, $this );

		return is_array( $filtered )
			? array_shift( $filtered )
			: $filtered;
	}


	/**
	 * @param array $data
	 */
	private function set_stored_value( $data ) {
		$this->store_value = isset( $data['store_value'] )
			? $data['store_value']
			: '';
	}


	/**
	 * @param array $data
	 */
	private function set_display_value_checked( $data ) {
		$this->display_value_checked = isset( $data['display_value_checked'] )
			? stripslashes( $data['display_value_checked'] )
			: '';
	}


	/**
	 * @param array $data
	 */
	private function set_display_value_unchecked( $data ) {
		$this->display_value_unchecked = isset( $data['display_value_unchecked'] )
			? stripslashes( $data['display_value_unchecked'] )
			: '';
	}


	/**
	 * Gets WPML ST id
	 *
	 * @param string $value String to translate
	 * @param string $type Returned type. Default title.
	 * @param string|null $id WPML ID part.
	 * @param string $option WPML ID part.
	 *
	 * @return string
	 * @noinspection PhpSameParameterValueInspection
	 */
	private function get_translated_value( $value, $type = 'title', $id = null, $option = 'option' ) {
		if ( ! $this->field instanceof Types_Field_Abstract ) {
			return $value;
		}

		$field_data = $this->field->to_array();
		if ( null === $id ) {
			$id = $field_data['id'];
		}
		// @see Types_Shortcode_Generator::get_shortcode_default_parameters()
		$wpml_id = 'field ' . $id . ' ' . $option . ' ' . $this->id . ' ' . $type;
		$wpml_id = str_replace( '  ', '', $wpml_id );

		return wpcf_translate( $wpml_id, $value );
	}
}
