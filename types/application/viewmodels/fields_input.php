<?php

use OTGS\Toolset\Common\Utils\RequestMode;

/**
 * Gets HTML inputs elements from a list of fields
 *
 * @since m2m
 */
class Types_Viewmodel_Field_Input {


	/**
	 * List of fields
	 *
	 * The driver can return a list of Toolset_Field_Definition or Toolset_Field_Instance depending on they are instanced or not.
	 *
	 * @var Toolset_Field_Definition[]|Toolset_Field_Instance[]
	 * @see Toolset_Relationship_Driver::get_field_definitions()
	 * @since m2m
	 */
	protected $fields;


	/**
	 * Constructor
	 *
	 * @param Toolset_Field_Definition[]|Toolset_Field_Instance[] $fields List of fields.
	 * @since m2m
	 */
	public function __construct( $fields ) {
		$this->fields = $fields;
	}


	/**
	 * Gets fields data
	 *
	 * @param string $render_purpose A render purpose type (Toolset_Field_Renderer_Purpose).
	 * @return Array
	 * @since m2m
	 */
	public function get_fields_data( $render_purpose = Toolset_Field_Renderer_Purpose::PREVIEW ) {
		$fields_data = array();
		if ( empty( $this->fields ) ) {
			return $fields_data;
		}
		$is_new_field = ! $this->fields[ key( $this->fields ) ] instanceof Toolset_Field_Instance;
		if ( is_array( $this->fields ) ) {
			add_filter( 'toolset_field_factory_get_attributes', array( $this, 'add_filter_attributes' ), 10, 2 );
			foreach ( $this->fields as $undertermined_field ) {
				// The driver can return a list of Toolset_Field_Definition or Toolset_Field_Instance depending on if they are instanced or not.
				/**
				 * @var mixed $field (Toolset_Field_Instance|Toolset_Field_Type_Definition)
				 * @refactoring Bring sanity here, PHPStan goes a bit crazy
				 */
				$field = $is_new_field
					? $undertermined_field->get_type()
					: $undertermined_field;
				$definition = $is_new_field
					? $undertermined_field
					: $field->get_definition();
				$slug = $definition->get_slug();
				/** @noinspection PhpParamsInspection */
				/* @phpstan-ignore-next-line */
				$fields_data[ $slug ] = array(
					'name' => $slug,
					'value' => $is_new_field ? '' : $field->get_value(),
					// Renders it as admin info.
					'rendered' => $is_new_field
						? $field->get_renderer( $render_purpose, RequestMode::ADMIN, new Toolset_Field_Instance_Unsaved( $definition ), array() )->render()
						: $field->get_renderer( $render_purpose, RequestMode::ADMIN, array() )->render(),
				);
			}
			remove_filter( 'toolset_field_factory_get_attributes', array( $this, 'add_filter_attributes' ), 10 );
			// 'admin_footer' hook need to be executed, for example the skype field, so this hook emulates it.
			if ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) {
				do_action( 'toolset_related_content_footer' );
			}
		}
		return $fields_data;
	}


	/**
	 * Add filter attributes
	 * The wysiwyg editors need to have different IDs, so a new attribute is needed
	 * When a wp_editor is instanced in WPToolset_Field_Wysiwyg, the id used is the same for the each instance of the same editor.
	 * Keep in mind that the related content repeats the same editor in each Quick Edit action and Add New and Connect actions.
	 * tinyMce can't handle different editors with the same ID, so it is needed to modify the ID with a random suffix in order to have different editors with the same name attribute but different ids.
	 * The only way to do it is adding an attribute to WPToolset_Field_Wysiwyg with the filter toolset_field_factory_get_attributes
	 *
	 * @param Array         $attributes The list of attributes.
	 * @param Toolset_Common_Forms_FieldAbstract $field The field.
	 *
	 * @return Array
	 * @see WPToolset_Field_Wysiwyg::_editor()
	 * @since m2m
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function add_filter_attributes( $attributes, $field ) {
		$attributes['types-related-content'] = true;
		return $attributes;
	}
}
