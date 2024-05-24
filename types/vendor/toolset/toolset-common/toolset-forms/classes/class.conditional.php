<?php

use OTGS\Toolset\Common\Utils\Condition\Plugin\Gutenberg\IsUsedForPost;

if ( ! class_exists( 'WPToolset_Forms_Conditional' ) ) {

	/**
	 * - Checks conditionals when form is displayed and values changed
	 * - Checks simple conditionals using JS
	 * - Checks custom conditinals via AJAX/PHP
	 * - PHP simple and custom checks available using class methods
	 *
	 * Simple conditionals
	 *
	 * Data
	 * [id] - Trigger ID to match data-wpt-id
	 * [type] - field type (trigger)
	 * [operator] - operator
	 * [args] - array(value, value2...)
	 *
	 * Example
	 * $config['conditional'] = array(
	 *  'relation' => 'OR'|'AND',
	 *  'conditions' => array(
	 *      array(
	 *          'id' => 'wpcf-text',
	 *          'type' => 'textfield',
	 *          'operator' => '==',
	 *          'args' => array('show')
	 *      ),
	 *      array(
	 *          'id' => 'wpcf-date',
	 *          'type' => 'date',
	 *          'operator' => 'beetween',
	 *          'args' => array('21/01/2014', '24/01/2014') // Accepts timestamps or string date
	 *      )
	 *  ),
	 * );
	 *
	 * Custom conditionals
	 *
	 * Variable name should match trigger ID - data-wpt-id
	 * Example
	 * $config['conditional'] = array(
	 *  'custom' => '($wpcf-text = show) OR ($wpcf-date > '21-01-2014')'
	 * );
	 *
	 * Note that sometimes we try to initialize conditionals on an unknown form.
	 * In this case, no conditional data will be prnted, nor ptocessed by the related javascript.
	 *
	 * @todo BUG common function wpv_condition has some flaws
	 *      (dashed names, mixed checks for string and numeric values causes failure)
	 *
	 */
	class WPToolset_Forms_Conditional {

		/** @var array */
		protected $form_selectors;

		/** @var string */
		private $post_type;

		protected $_collected = array(), $_triggers = array(), $_fields = array(), $_custom_triggers = array(), $_custom_fields = array();

		/**
		 * State scripts are loaded
		 *
		 * @var bool
		 */
		protected static $scripts_loaded = false;


		/**
		 * Register and enqueue scripts and actions.
		 *
		 * @param string $form_selector
		 */
		public function __construct( $form_selector ) {
			$this->set_form_selectors( $form_selector );

			self::load_scripts();

			wp_enqueue_script( 'wptoolset-parser' );

			// Render settings
			add_action( 'admin_print_footer_scripts', array( $this, 'renderJsonData' ), 30 );
			add_action( 'wp_footer', array( $this, 'renderJsonData' ), 30 );

			// Gather settings dynamically.
			add_filter( 'wptoolset_forms_get_partial_conditionals', array( $this, 'get_partial_data' ) );

			/**
			 * @deprecated 2.4.0
			 * @deprecated 1.9.0 CRED
			 */
			add_action( 'wptoolset_field_class', array( $this, 'wptoolset_field_class_deprecated' ) );

			/**
			 * Adds necessary CSS classes to fields with conditional output data.
			 *
			 * @since 2.4.0
			 * @since 1.9.0 CRED
			 */
			add_filter( 'toolset_field_additional_classes', array( $this, 'actionFieldClass' ), 10, 2 );
		}


		/**
		 * Required for a Toolset_Condition_Post_Type_Editor_Is_Block check
		 * for the case it's called when $current_screen is not set (ajax / to early called)
		 *
		 * @param $post_type
		 */
		public function set_post_type( $post_type ) {
			if ( ! is_string( $post_type ) || empty( $post_type ) ) {
				throw new InvalidArgumentException( '$post_type must be a non-empty string.' );
			}

			$this->post_type = $post_type;
		}


		/**
		 * Set Form Selectors depending on given $form_selector and if the block editor is active.
		 *
		 * @param $form_selector
		 */
		protected function set_form_selectors( $form_selector ) {
			$this->form_selectors = $this->get_block_editor_form_selectors();
			if ( ! $this->form_selectors && is_string( $form_selector ) ) {
				// gutenberg not active and given form_selector is a string
				if ( ! preg_match( '#^[\.\#]{1}#', $form_selector ) ) {
					// if it has neither a . or a # as starting character we need to add # as in the past this only
					// worked with id attribute, BUT it was possible to pass the selector with out the starting #
					$form_selector = '#' . $form_selector;
				}

				// convert to array
				$this->form_selectors = array( $form_selector );
			}

			if ( ! $this->form_selectors ) {
				// neither a string given nor gutenberg active
				throw new RuntimeException( '$form_selector must be a string.' );
			}
		}


		/**
		 * With block editor active we need to rewrite the form selector, which will later be used on javascript to apply
		 * field conditions and triggers.
		 *
		 * As the tree is very weird including Enlimbo forms and the fact that the entry points could be anywhere (at least
		 * they are on Types and Forms) we're manipulating it directly here instead of passing it through the whole tree.
		 *
		 * @return false|array
		 */
		private function get_block_editor_form_selectors() {

			// Defensively prevent this from passing if we're in the per post editor mode.
			global $post;
			$current_post = $post;
			// phpcs:disable WordPress.Security.NonceVerification.Recommended
			if ( $this instanceof WPToolset_Forms_Conditional_RFG && array_key_exists( 'parent_post_id', $_REQUEST ) ) {
				$current_post = (int) $_REQUEST['parent_post_id'];
			}
			// phpcs:enable
			if ( $current_post ) {
				$post_using_block_editor = new IsUsedForPost();
				$post_using_block_editor->set_post( $current_post );
				if ( ! $post_using_block_editor->is_met() ) {
					return false;
				};
			}

			$post_type_using_block_editor = new Toolset_Condition_Post_Type_Editor_Is_Block();

			if ( $this->post_type ) {
				$post_type_using_block_editor->set_post_type( $this->post_type );
			}

			try {
				if ( ! $post_type_using_block_editor->is_met() ) {
					// block editor not active for this post type
					return false;
				}
			} catch ( RuntimeException $e ) {
				// happens on ajax call for rendering RFGs, because RFGs requires to run legacy field rendering
				// but cannot use the field validation of it. Problem, the legacy field rendering is hard coupled
				// with field validation (also when it's not wanted/used). For that case we simply treat the validation
				// build as for classic editor (could also be treated as block or 'anything').
				// Important here is that we don't exit caused by an uncaught exception.
				return false;
			}

			// possible location for our fields on gutenberg editor
			return array( '.metabox-location-normal', '.metabox-location-advanced' );
		}


		/**
		 * Loads the conditional script and makes sure that it's only initiated once.
		 */
		public static function load_scripts() {
			if ( self::$scripts_loaded ) {
				// already loaded
				return;
			}
			// Register and enqueue
			wp_register_script( 'wptoolset-form-conditional', WPTOOLSET_FORMS_RELPATH
				. '/js/conditional.js', array( 'jquery', 'jquery-effects-scale' ), WPTOOLSET_FORMS_VERSION, true );
			wp_enqueue_script( 'wptoolset-form-conditional' );
			$js_data = array(
				'ajaxurl' => admin_url( 'admin-ajax.php', null ),
			);
			wp_localize_script( 'wptoolset-form-conditional', 'wptConditional', $js_data );

			// store state
			self::$scripts_loaded = true;
		}


		/**
		 * Collects data.
		 *
		 * Called from form_factory.
		 *
		 * @param type $config
		 */
		public function add( $config ) {
			if ( ! empty( $config['conditional'] ) ) {
				$this->_collected[ $config['id'] ] = $config['conditional'];

				return;
			}
		}


		/**
		 * Sets JSON data to be used with conditional.js
		 */
		protected function _parseData() {
			foreach ( $this->_collected as $id => $config ) {
				if ( ! empty( $config['custom'] ) ) {

					$evaluate = $config['custom'];
					//###############################################################################################
					//https://icanlocalize.basecamphq.com/projects/7393061-toolset/todo_items/193583580/comments
					//Fix REGEX conditions that contains \ that is stripped out
					if ( strpos( $evaluate, "REGEX" ) === false ) {
						$evaluate = wpv_filter_parse_date( $evaluate );
						$evaluate = self::handle_user_function( $evaluate );
					}
					//###############################################################################################
					$fields = self::extractFields( $evaluate );

					foreach ( $fields as $field ) {
						$this->_custom_fields[ $id ]['custom'] = $evaluate;
						$this->_custom_fields[ $id ]['triggers'][] = $field;
						$this->_custom_triggers[ $field ][] = $id;
					}
					// Do not let items repeat themselves!
					$this->_custom_fields[ $id ]['triggers'] = $this->array_unique_md( $this->_custom_fields[ $id ]['triggers'] );
					$this->_custom_triggers[ $field ] = array_unique( $this->_custom_triggers[ $field ] );
				} else {
					if ( isset( $config ) && isset( $config['conditions'] ) ) {
						if ( isset( $config ) && isset( $config['relation'] ) ) {
							$this->_fields[ $id ]['relation'] = $config['relation'];
						}

						foreach ( $config['conditions'] as &$c ) {
							/*
							 * $c[id] - field id
							 * $c[type] - field type
							 * $c[operator] - operator
							 * $c[args] - array(value, [value2]...)
							 */
							if ( ! isset( $this->_triggers[ $c['id'] ] ) ) {
								$this->_triggers[ $c['id'] ] = array();
							}
							$c['args'] = apply_filters( 'wptoolset_conditional_args_js', $c['args'], $c['type'] );
							$this->_fields[ $id ]['conditions'][] = $c;
							if ( ! in_array( $id, $this->_triggers[ $c['id'] ] ) ) {
								$this->_triggers[ $c['id'] ][] = $id;
							}
						}
						// Do not let items repeat themselves!
						$this->_fields[ $id ]['conditions'] = $this->array_unique_md( $this->_fields[ $id ]['conditions'] );
						$this->_triggers[ $c['id'] ] = array_unique( $this->_triggers[ $c['id'] ] );
					}
				}
			}
		}

		/**
		 * Generate conditionals data to be passed to the form as a data attribute.
		 *
		 * @param array $dummy
		 * @return mixed[]
		 */
		public function get_partial_data( $dummy = [] ) {
			$this->_parseData();

			$data = [];

			foreach ( $this->form_selectors as $form_selector ) {
				$data[ $form_selector ] = [
					'wptCondTriggers' => $this->_triggers,
					'wptCondFields' => $this->_fields,
					'wptCondCustomTriggers' => $this->_custom_triggers,
					'wptCondCustomFields' => $this->_custom_fields,
				];
			}

			return $data;
		}

		/**
		 * Generate unique multidimmensional arrays.
		 *
		 * @param array $source
		 * @return array
		 */
		protected function array_unique_md( $source ) {
			$serialized = array_map('serialize', $source);
			$unique = array_unique($serialized);
			return array_intersect_key($source, $unique);
		}


		/**
		 * Renders JSON data in footer to be used with conditional.js
		 */
		public function renderJsonData() {
			$this->_parseData();

			$cond_triggers
				= $this->jsArrayToEachFormSelectorByNameAndValue( 'wptCondTriggers', $this->_triggers )
				. $this->jsArrayToEachFormSelectorByNameAndValue( 'wptCondFields', $this->_fields )
				. $this->jsArrayToEachFormSelectorByNameAndValue( 'wptCondCustomTriggers', $this->_custom_triggers )
				. $this->jsArrayToEachFormSelectorByNameAndValue( 'wptCondCustomFields', $this->_custom_fields );

			if ( ! empty( $cond_triggers ) ) {
				echo '<script type="text/javascript">' . $cond_triggers . '</script>';
			}
		}


		/**
		 * Helper function to build condition and triggers data for each form selector
		 *
		 * @param string $name
		 * @param array $value will run through json_encode
		 *
		 * @return string
		 */
		private function jsArrayToEachFormSelectorByNameAndValue( $name, $value ) {
			if ( empty( $value ) ) {
				return '';
			}
			$output = '';
			$json_values = json_encode( $value );

			foreach ( $this->form_selectors as $form_selector ) {
				if (
					empty( $form_selector )
					|| '#' === $form_selector
				) {
					continue;
				}

				// We're making the output idempotent (object properties will be shallow-merged) in case this class is
				// instantiated multiple times within a single request (which shouldn't be happening but we're
				// still dealing with legacy code here).
				$output .= sprintf(
					'%1$s = Object.assign( %2$s, \'undefined\' === typeof( %1$s ) ? {} : %1$s );',
					$name . '[\'' . $form_selector . '\']',
					$json_values
				);
			}

			return $output;
		}


		/**
		 * Compares values.
		 *
		 * @param array $config
		 *
		 * @return bool
		 */
		public static function evaluate( $config ) {
			// Custom conditional
			if ( ! empty( $config['custom'] ) ) {
				return self::evaluateCustom( $config['custom'], $config['values'] );
			}

			/**
			 * check conditions
			 */
			if ( ! array_key_exists( 'conditions', $config ) ) {
				return true;
			}

			$passedOne = false;
			$passedAll = true;
			$relation = $config['relation'];

			foreach ( $config['conditions'] as $c ) {
				// Add filters
				wptoolset_form_field_add_filters( $c['type'] );
				$c['args'] = apply_filters( 'wptoolset_conditional_args_php', $c['args'], $c['type'] );
				$value = isset( $config['values'][ $c['id'] ] ) ? $config['values'][ $c['id'] ] : null;
				$value = apply_filters( 'wptoolset_conditional_value_php', $value, $c['type'], $c['id'] );
				$compare = $c['args'][0];
				switch ( $c['operator'] ) {
					case '=':
					case '==':
						$passed = $value == $compare;
						break;

					case '>':
						$passed = floatval( $value ) > floatval( $compare );
						break;

					case '>=':
						$passed = floatval( $value ) >= floatval( $compare );
						break;

					case '<':
						$passed = floatval( $value ) < floatval( $compare );
						break;

					case '<=':
						$passed = floatval( $value ) <= floatval( $compare );
						break;

					case '===':
						$passed = $value === $compare;
						break;

					case '!==':
						$passed = $value !== $compare;
						break;

					case '<>':
						$passed = $value <> $compare;
						break;

					case 'between':
						$passed = floatval( $value ) > floatval( $compare )
							&& floatval( $value )
							< floatval( $c['args'][1] );
						break;

					default:
						$passed = false;
						break;
				}
				if ( ! $passed ) {
					$passedAll = false;
				} else {
					$passedOne = true;
				}
			}
			if ( $relation == 'AND' && $passedAll ) {
				return true;
			}
			if ( $relation == 'OR' && $passedOne ) {
				return true;
			}

			return false;
		}


		/**
		 * Evaluates conditions using custom conditional statement.
		 *
		 * @param string $evaluate
		 * @param $values
		 *
		 * @return bool
		 * @uses wpv_condition()
		 */
		public static function evaluateCustom( $evaluate, $values ) {

			$toolset_bootstrap = Toolset_Common_Bootstrap::getInstance();
			$toolset_bootstrap->register_parser();

			//Fix REGEX conditions that contains \ that is stripped out
			if ( strpos( $evaluate, "REGEX" ) === false ) {
				$evaluate = trim( stripslashes( $evaluate ) );
				// Check dates
				$evaluate = wpv_filter_parse_date( $evaluate );
				$evaluate = self::handle_user_function( $evaluate );
			}

			$fields = self::extractFields( $evaluate );
			$evaluate = self::_update_values_in_expression( $evaluate, $fields, $values );
			$check = false;
			try {
				$parser = new Toolset_Parser( $evaluate );
				$parser->parse();
				$check = $parser->evaluate();
			} catch ( Exception $e ) {
				$check = false;
			}

			return $check;
		}


		static function sortByLength( $a, $b ) {
			return strlen( $b ) - strlen( $a );
		}


		private static function _update_values_in_expression( $evaluate, $fields, $values ) {
			// use string replace to replace any fields with their values.
			// Sort by length just in case a field name contians a shorter version of another field name.
			// eg.  $my-field and $my-field-2

			$keys = array_keys( $fields );
			usort( $keys, 'WPToolset_Forms_Conditional::sortByLength' );

			foreach ( $keys as $key ) {
				$is_numeric = false;
				$is_array = false;
				$value = isset( $values[ $fields[ $key ] ] ) ? $values[ $fields[ $key ] ] : '';
				if ( $value == '' ) {
					$value = "''";
				}
				if ( is_numeric( $value ) ) {
					$value = '\'' . $value . '\'';
					$is_numeric = true;
				}

				if ( 'array' === gettype( $value ) ) {
					$is_array = true;
					// workaround for datepicker data to cover all cases
					if ( array_key_exists( 'timestamp', $value ) ) {
						if ( is_numeric( $value['timestamp'] ) ) {
							$value = $value['timestamp'];
						} elseif ( is_array( $value['timestamp'] ) ) {
							$value = implode( ',', array_values( $value['timestamp'] ) );
						}
					} elseif ( array_key_exists( 'datepicker', $value ) ) {
						if ( is_numeric( $value['datepicker'] ) ) {
							$value = $value['datepicker'];
						} elseif ( is_array( $value['datepicker'] ) ) {
							$value = implode( ',', array_values( $value['datepicker'] ) );
						}
					} else {
						$value = implode( ',', array_values( $value ) );
					}
				}

				if ( ! empty( $value ) && $value != "''" && ! $is_numeric && ! $is_array ) {
					$value = '\'' . $value . '\'';
				}

				// First replace the $(field_name) format
				$evaluate = str_replace( '$(' . $fields[ $key ] . ')', $value, $evaluate );
				// next replace the $field_name format
				$evaluate = str_replace( '$' . $fields[ $key ], $value, $evaluate );
			}

			return $evaluate;
		}


		/**
		 * Extracts fields from custom conditional statement.
		 *
		 * @param type $evaluate
		 *
		 * @return type
		 */
		public static function extractFields( $evaluate ) {
			//###############################################################################################
			//Fix REGEX conditions that contains \ that is stripped out
			if ( strpos( $evaluate, "REGEX" ) === false ) {
				$evaluate = trim( stripslashes( $evaluate ) );
				// Check dates
				$evaluate = wpv_filter_parse_date( $evaluate );
				$evaluate = self::handle_user_function( $evaluate );
			}

			// Add quotes = > < >= <= === <> !==
			$strings_count = preg_match_all( '/[=|==|===|<=|<==|<===|>=|>==|>===|\!===|\!==|\!=|<>]\s(?!\$)(\w*)[\)|\$|\W]/', $evaluate, $matches );

			if ( ! empty( $matches[1] ) ) {
				foreach ( $matches[1] as $temp_match ) {
					$temp_replace = is_numeric( $temp_match ) ? $temp_match : '\'' . $temp_match . '\'';
					$evaluate = str_replace( ' ' . $temp_match . ')', ' ' . $temp_replace . ')', $evaluate );
				}
			}
			// if new version $(field-value) use this regex
			if ( preg_match( '/\$\(([^()]+)\)/', $evaluate ) ) {
				preg_match_all( '/\$\(([^()]+)\)/', $evaluate, $matches );
			} // if old version $field-value use this other
			else {
				preg_match_all( '/\$([^\s]*)/', $evaluate, $matches );
			}


			$fields = array();
			if ( ! empty( $matches ) ) {
				foreach ( $matches[1] as $field_name ) {
					$fields[ trim( $field_name, '()' ) ] = trim( $field_name, '()' );
				}
			}

			return $fields;
		}


		public static function handle_user_function( $evaluate ) {
			$evaluate = stripcslashes( $evaluate );
			$occurrences = preg_match_all( '/(\\w+)\(([^\)]*)\)/', $evaluate, $matches );

			if ( $occurrences > 0 ) {
				for ( $i = 0; $i < $occurrences; $i ++ ) {
					$result = false;
					$function = $matches[1][ $i ];
					$field = isset( $matches[2] ) ? rtrim( $matches[2][ $i ], ',' ) : '';

					if ( $function === 'USER' ) {
						$result = WPV_Handle_Users_Functions::get_user_field( $field );
					}

					if ( $result ) {
						$evaluate = str_replace( $matches[0][ $i ], $result, $evaluate );
					}
				}
			}

			return $evaluate;
		}


		/**
		 * Custom conditional AJAX check (called from bootstrap.php)
		 */
		public static function ajaxCustomConditional() {
			$res = array( 'passed' => array(), 'failed' => array() );
			$conditional = stripslashes_deep( $_POST['conditions'] );
			foreach ( $conditional as $k => $c ) {
				$post_values = stripslashes_deep( $_POST['values'] );
				$values = array();
				foreach ( $post_values as $fid => $value ) {
					if ( isset( $_POST['field_types'][ $fid ] ) ) {
						$field_type = stripslashes_deep( $_POST['field_types'][ $fid ] );
						wptoolset_form_field_add_filters( $field_type );
						$value = apply_filters( 'wptoolset_conditional_value_php', $value, $field_type );
					}
					$values[ $fid ] = $value;
				}
				if ( $passed = self::evaluateCustom( $c, $values ) ) {
					$res['passed'][] = $k;
				} else {
					$res['failed'][] = $k;
				}
			}
			wp_send_json( $res );
		}


		/**
		 * Callback for a deprecated action.
		 *
		 * @since 2.4.0
		 */
		public function wptoolset_field_class_deprecated() {
			_doing_it_wrong(
				'wptoolset_field_class',
				__( 'This action was deprecated in CRED 1.9.0.', 'wpv-views' ),
				'1.9.0'
			);
		}


		/**
		 * Check conditionals for a field and generate the related classnames for its metaform.
		 *
		 * @param string $classes The classnames fo the field
		 * @param array $config The field configuration
		 *
		 * @return string
		 *
		 * @since unknown
		 * @since 2.4.0 Turn into a filter callback, hence make it return instead of echo.
		 */
		public function actionFieldClass( $classes, $config ) {
			if (
				! empty( $config['conditional'] ) && array_key_exists( 'conditions', $config['conditional'] )
			) {
				$classes .= ' js-toolset-conditional';

				if ( ! self::evaluate( $config['conditional'] ) ) {
					$classes .= ' wpt-hidden wpt-conditional-hidden js-wpt-validation-ignore';
				}
			}

			return $classes;
		}


		/**
		 * Returns collected JSON data
		 *
		 * @return array
		 */
		public function getData() {
			$this->_parseData();

			return array(
				'triggers' => $this->_triggers,
				'fields' => $this->_fields,
				'custom_triggers' => $this->_custom_triggers,
				'custom_fields' => $this->_custom_fields,
			);
		}

	}
}

if ( ! class_exists( 'WPV_Handle_Users_Functions' ) ) {

	class WPV_Handle_Users_Functions {

		private static $field;


		public static function get_user_field( $field ) {
			if ( ! $field ) {
				return false;
			}

			self::$field = str_replace( "'", '', $field );

			$ret = self::get_info();

			if ( $ret !== false ) {
				return "'" . $ret . "'";
			}

			return false;
		}


		private static function get_info() {
			if ( ! is_user_logged_in() ) {
				return false;
			}
			global $current_user;

			$current_user = wp_get_current_user();

			switch ( self::$field ) {
				case 'role':
					return isset( $current_user->roles[0] ) ? $current_user->roles[0] : 'Subscriber';
					break;
				case 'login':
					return $current_user->data->user_login;
					break;
				case 'name':
					return $current_user->data->display_name;
					break;
				case 'id':
					return $current_user->data->ID;
					break;
				default:
					return $current_user->data->ID;
					break;
			}

			return false;
		}

	}

}
