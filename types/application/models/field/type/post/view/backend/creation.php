<?php

/**
 * Class Types_Field_Type_Post_View_Backend_Creation
 *
 * @since 2.3
 */
class Types_Field_Type_Post_View_Backend_Creation {

	const INPUT_REFERENCE_TYPE = 'post_reference_type';

	/**
	 * @var Types_Field_Type_Post
	 */
	private $entity;

	/**
	 * Types_Field_Type_Single_Line_View_Frontend constructor.
	 *
	 * @param Types_Field_Type_Post $entity
	 */
	public function __construct( Types_Field_Type_Post $entity ) {
		$this->entity = $entity;
	}

	/**
	 * @return string
	 */
	public function get_title() {
		return __( 'Post Reference', 'wpcf' );
	}

	/**
	 * Setup Array for Post Reference Field
	 * @return array
	 */
	public function legacy_get_settings_array() {
		return array(
			'id'           => 'wpcf-' . $this->entity->get_type(),
			'title'        => $this->get_title(),
			'description'  => $this->get_title(),
			'wp_version'   => '4.8',
			'font-awesome' => 'thumb-tack',
			'validate' => array(
				'required' => array(
					'form-settings' => include( WPCF_EMBEDDED_ABSPATH . '/includes/fields/patterns/validate/form-settings/required.php' )
				)
			),
		);
	}

	/**
	 *  Enlimbo Select for Post Types
	 */
	public function legacy_get_input_array_for_post_type() {
		$post_type_repository = Toolset_Post_Type_Repository::get_instance();

		// this way we position the description field above the post reference field
		// some strange legacy behaviour... the value we set here has no relevance
		$form['description'] = null;

		// get all public post types
		$post_types = $post_type_repository->get_all();

		// store them as enlimbo options
		$post_types_as_options = array( array(
			'#name' => '',
			'#value' => '',
			'#title' => __( 'Select a post type...', 'wpcf' ),
			'#attributes' => array( 'disabled' => 'disabled', 'selected' => 'selected' )
		));
		foreach ( $post_types as $post_type ) {
			if( ! $post_type->is_public() || $post_type->get_slug() == 'attachment' ) {
				// only public post types are usable for post reference field
				continue;
			}

			$attributes = array();
			$title = $post_type->get_label();

			$can_be_used_in_relationship = $post_type->can_be_used_in_relationship();
			if( ! $can_be_used_in_relationship->is_success() ) {
				$is_wpml_motive = false !== strpos( $can_be_used_in_relationship->get_message(), 'WPML' );
				$motive = ! $is_wpml_motive
					? __( '(not supported post type)', 'wpcf' )
					: __( '(not supported translation mode)', 'wpcf' );
				$title = $post_type->get_label() . ' ' . $motive;
				if ( $is_wpml_motive ) {
					$attributes['data-prf-no-valid-type'] = 1;
				}
			}

			$one = array(
				'#name' => $post_type->get_slug(),
				'#value' => $post_type->get_slug(),
				'#title' => $title,
				'#attributes' => $attributes
			);

			$post_types_as_options[] = $one;
		}

		// setup the post type select
		$form['post_reference_type'] = array(
			'#type' => 'select',
			'#title' => __( 'Post Type to connect', 'wpcf' ),
			'#inline' => true,
			'#name' => self::INPUT_REFERENCE_TYPE,
			'#options' => $post_types_as_options,
			'#attributes' => array( 'data-types-validate-required' => '1', 'data-prf-proof-selected-type' => '1' ),
			'#pattern' => '<tr class="wpcf-border-top js-wpcf-post-reference-field"><td><LABEL></td><td><ERROR><BEFORE><ELEMENT><AFTER></td></tr>'
		);

		$form['post_reference_type_pre_save'] = array(
			'#type'  => 'hidden',
			'#name'  => 'post_reference_type_pre_save',
			'#value' => ''
		);

		// Morph to one-to-many relationship (make it repeatable)
		$form['post_reference_type_make_relationship'] = array(
			'#type' => 'markup',
			'#title' => __( 'Single or repeating field?', 'wpcf' ),
			'#markup' => '<a href="javascript:void(0);" class="js-types-post-reference-field-make-repeatable">' . __( 'I need this field to repeat.', 'wpcf' ) . '</a>'
				. '<i class="js-wpcf-tooltip wpcf-tooltip dashicons dashicons-editor-help hidden" data-tooltip="' . __( 'You cannot make this field repeatable because it references the same post type on both side, and such setup is not supported at the moment.', 'wpcf' ). '"></i>',
			'#inline' => true,
			'#pattern' => '<tr class="wpcf-border-top"><td><LABEL></td><td><ERROR><BEFORE><ELEMENT><AFTER>',
		);

		return $form;
	}
}
