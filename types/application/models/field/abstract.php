<?php

/**
 * @since 2.3
 */
abstract class Types_Field_Abstract implements Types_Field_Interface {

	/**
	 * @var string (unique)
	 */
	protected $slug;

	/**
	 * @var string
	 */
	protected $title;

	/**
	 * @var string
	 */
	protected $description;

	/**
	 * User stored value
	 *
	 * @var string|array
	 */
	protected $value;

	/**
	 * @var Types_Field_Validation[]
	 */
	protected $validations = array();

	/**
	 * @var int
	 */
	protected $wpml_translation_mode;


	/**
	 * Default display mode
	 *
	 * @var string
	 */
	const DISPLAY_MODE_DB = 'db';


	/**
	 * DB data
	 *
	 * @var array
	 * @since m2m
	 */
	private $data;


	/**
	 * @param array $data
	 *  'title', 'slug', 'description', 'value'
	 *
	 * @since 2.3
	 */
	public function __construct( $data ) {
		$this->set_slug( $data['slug'] );
		$this->set_title( $data['title'] );
		$this->set_description( $data['description'] );
		$this->set_value( $data['value'] );
		// Has to be validated only in banckend.
		if ( isset( $data['data']['validate'] ) && $this->is_validation_required() ) {
			$this->set_validation( $data['data']['validate'] );
		}
		if ( isset( $data['wpml_action'] ) ) {
			$this->set_wpml_translation_mode( $data['wpml_action'] );
		}
		$this->data = $data;
	}


	/**
	 * Check if the the validation is required for the current request.
	 *
	 * @return bool
	 */
	protected function is_validation_required() {
		if ( isset( $_REQUEST['action'] )
			&& (
				$_REQUEST['action'] === 'wpv_get_view_query_results'
				|| $_REQUEST['action'] === 'wpv_get_archive_query_results'
			)
		) {
			// view ajax query -> no validation required
			return false;
		}

		if ( ! is_admin() ) {
			// no admin page = no validation required
			return false;
		}

		// validation required
		return true;
	}


	/**
	 * @return string
	 */
	public function get_slug() {
		return $this->slug;
	}


	/**
	 * @return string
	 */
	public function get_title() {
		return $this->title;
	}


	/**
	 * @return string
	 */
	public function get_description() {
		return $this->description;
	}


	/**
	 * @return string|array
	 */
	public function get_value() {
		return $this->value;
	}


	/**
	 * @param string $data
	 */
	protected function set_slug( $data ) {
		if ( ! is_string( $data ) ) {
			return;
		}

		$this->slug = $data;
	}


	/**
	 * @param string $data
	 */
	protected function set_title( $data ) {
		if ( ! is_string( $data ) ) {
			return;
		}

		$this->title = $data;
	}


	/**
	 * @param string $data
	 */
	protected function set_description( $data ) {
		if ( ! is_string( $data ) ) {
			return;
		}

		$this->description = $data;
	}


	/**
	 * @param string|array|mixed $data
	 */
	protected function set_value( $data ) {
		if ( ! is_string( $data ) && ! is_array( $data ) ) {
			return;
		}

		$this->value = $data;
	}


	/**
	 * @return bool
	 */
	public function is_repeatable() {
		return false;
	}


	/**
	 * @param integer $int
	 */
	protected function set_wpml_translation_mode( $int ) {
		if ( is_numeric( $int ) ) {
			$this->wpml_translation_mode = (int) $int;
		}
	}


	/**
	 * @param array $user_params
	 *
	 * @param Types_Interface_Value|null $source Allow Types_Field_Part_Option to filter its value
	 *
	 * @return array|string
	 */
	public function get_value_filtered( $user_params = array(), $source = null ) {
		$source = $source && $source instanceof Types_Interface_Value
			? $source
			: $this;

		if ( ( isset( $user_params['output'] ) && 'raw' === $user_params['output'] )
			|| ( isset( $user_params['raw'] ) && $user_params['raw'] ) ) {
			// raw means raw
			return $source->get_value();
		}

		if ( ! function_exists( 'apply_filters' ) ) {
			return $source->get_value();
		}

		global $post;
		$post_id = is_object( $post ) && property_exists( $post, 'ID' )
			? $post->ID
			: '';

		$stored_value = $source->get_value();
		$modified_values = array();

		foreach ( (array) $stored_value as $value ) {
			// legacy filter for all fields
			$value = apply_filters(
				'wpcf_fields_value_display',
				$value,
				$user_params,
				$post_id,
				$this->get_slug(),
				null
			);

			// legacy filter by field slug
			$value = apply_filters(
				'wpcf_fields_slug_' . $this->get_slug() . '_value_display',
				$value,
				$user_params,
				$post_id,
				$this->get_slug(),
				null
			);

			// legacy filter by field type
			$value = apply_filters(
				'wpcf_fields_type_' . $this->get_type() . '_value_display',
				$value,
				$user_params,
				$post_id,
				$this->get_slug(),
				null
			);

			$modified_values[] = $value;
		}

		return ! empty( $modified_values )
			? $modified_values
			: '';
	}


	/**
	 * Sets validation rules
	 *
	 * @param array $data Validation data from the creation field. Example:
	 *                array (
	 *                  'required' =>
	 *                  array (
	 *                    'active' => '1',
	 *                    'value' => 'true',
	 *                    'message' => 'Field not required',
	 *                  ),
	 *                  ...
	 *                ).
	 */
	public function set_validation( $data ) {
		$this->validations = array();
		foreach ( $data as $type => $rule ) {
			try {
				$this->validations[] = Types_Field_Validation_Factory::get_validator_by_type( $type, $rule );
			} catch ( Exception $e ) {
				// validation class not found
				//if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					// Disabling the log because some field types (like numeric ) do include validation rules
					// not managed here, hence they produce logged errors on field rendering.
					// Restore once the TODO in the Types_Field_Validation_Factory class gets completed,
					// to avoid pulluting logs for now.
					//error_log( $e->getMessage() );
				//}

				continue;
			}

		}
	}


	/**
	 * Gets JSON well-formatted data
	 *
	 * @return String
	 * @since 2.3
	 */
	public function get_formatted_validation_data() {
		$validation_data = array();
		foreach ( $this->validations as $validation ) {
			$validation_data += $validation->get_data();
		}

		return wp_json_encode( $validation_data );
	}


	/**
	 * True if it's translatable by WPML
	 *
	 * @return bool
	 */
	public function is_translatable() {
		return $this->wpml_translation_mode === 2;
	}


	public function to_array() {
		return $this->data;
	}


	/**
	 * @return Toolset_Field_Type_Definition|null
	 */
	public function get_type_definition() {
		return Toolset_Field_Type_Definition_Factory::get_instance()->load_field_type_definition( $this->get_type() );
	}
}
