<?php

/**
 * Class Toolset_Field_Renderer_Toolset_Forms_Repeatable_Group
 *
 * This class extends Toolset_Field_Renderer_Toolset_Forms by adding the post_id to the html name attribute of the
 * field input. This way it's easy to store a field for a post wherever the field edit is shown.
 *
 * See:
 * <input name="wpcf[name-of-field]" /> (output of Toolset_Field_Renderer_Toolset_Forms)
 * <input name="wpcf[post-id-the-field-belongs-to][name-of-field] /> (output of Toolset_Field_Renderer_Toolset_Forms_Repeatable_Group)
 *
 * Note that passing an empty $form_id will not include th field conditionals data in its output:
 * conditionals will need to be managed in a separate way.
 * This happens, for example, on fields in Types related content metaboxes.
 *
 * @since 2.3
 */
class Toolset_Field_Renderer_Toolset_Forms_Repeatable_Group extends Toolset_Field_Renderer_Toolset_Forms {

	/** @var array */
	private $field_config;

	/** @var int */
	private $group_id;

	/** @var Toolset_Relationship_Query_Factory */
	private $query_factory;

	/** @var \OTGS\Toolset\Types\Field\Group\Repeatable\TreeFactory  */
	private $tree_factory;

	/**
	 * Toolset_Field_Renderer_Toolset_Forms_Repeatable_Group constructor.
	 *
	 * @param $field
	 * @param string $form_id
	 * @param \OTGS\Toolset\Types\Field\Group\Repeatable\TreeFactory|null $tree_factory
	 * @param Toolset_Relationship_Query_Factory|null $query_factory
	 */
	public function __construct(
		$field,
		$form_id = '',
		\OTGS\Toolset\Types\Field\Group\Repeatable\TreeFactory $tree_factory = null,
		Toolset_Relationship_Query_Factory $query_factory = null
	) {
		parent::__construct( $field, $form_id );

		// tree factory
		$this->tree_factory = $tree_factory;
		if( $this->tree_factory === null ) {
			$field_group_factory = Toolset_Field_Group_Post_Factory::get_instance();
			$this->tree_factory = new \OTGS\Toolset\Types\Field\Group\Repeatable\TreeFactory(
				$field_group_factory,
				new Types_Field_Group_Repeatable_Service()
			);
		}

		// query factory
		$this->query_factory = $query_factory ?: new Toolset_Relationship_Query_Factory();
	}

	/**
	 * Get the legacy field config and modifiy the field names to work with rfg items.
	 *
	 * @param int|null $group_id
	 *
	 * @return array
	 *
	 * @since 2.6.5
	 */
	public function get_field_config( $group_id = null ) {
		if( $this->field_config === null || ( $group_id !== null && $group_id !== $this->group_id ) ) {
			$this->group_id = $group_id;

			if( ! $tree = $this->tree_factory->getTreeByRFGId( $this->group_id ) ) {
				// this only happens if the provided group id is not the id of a rfg
				return $this->field_config;
			}

			// done by legacy
			$field_config = $this->get_toolset_forms_config();

			if ( $this->hide_field_title ) {
				// $field_config['title'] = ''; No need to be set to empty if 'hide_field_title' is true and needed for `wpt_field_options`
				$field_config['hide_field_title'] = true;
			}

			// no repetitive fields on rfg
			$field_config['repetitive'] = false;

			// convert field name to types-repeatable-group[item-id][field-slug]
			$field_config['name'] = 'types-repeatable-group[' . $this->field->get_object_id() . '][' . $field_config['slug'] . ']';
			if( isset( $field_config['options'] ) ) {
				foreach ( (array) $field_config['options'] as $option_key => $option_value ) {
					if ( isset( $option_value['name'] ) ) {
						$field_config['options'][$option_key]['name'] =
							'types-repeatable-group[' . $this->field->get_object_id() . '][' . $field_config['slug'] . '][' . $option_key . ']';
					}
				}
			}

			// check conditionals and rename field names
			if( isset( $field_config['conditional'] ) && isset( $field_config['conditional']['conditions'] ) ) {
				$group_fields = get_post_meta( $group_id, '_wp_types_group_fields', true );
				$group_fields = explode( ',', $group_fields );

				foreach( $field_config['conditional']['conditions']  as $con_key => $con_value ) {
					$con_field_slug = str_replace( 'wpcf-', '', $con_value['id'] );
					// change the conditions field slug if the field is part of the rfg
					if( in_array( $con_field_slug, $group_fields ) ) {
						$field_config['conditional']['conditions'][ $con_key ][ 'id' ] =
							'types-repeatable-group[' . $this->field->get_object_id() . '][' . $con_field_slug . ']';
					} else {
						// check parent rfgs
						$rfg_parents = $tree->getParentsOf( $group_id, $from_bottom_to_top = true );

						$parent_id = $this->field->get_object_id();
						foreach( $rfg_parents as $parent ) {
							$parent_id = $this->get_rfg_parent_id_by_rfg_id( $parent_id );
							if( in_array( $con_field_slug, $parent->get_field_slugs() ) ) {
								$field_config['conditional']['conditions'][ $con_key ][ 'id' ] =
									'types-repeatable-group[' . $parent_id . '][' . $con_field_slug . ']';

								// the nearest matched field will be used for condition
								// (for the edge case the condition field is on more than one level)
								break;
							}
						}
					}
				}

				// we also need to renaeme the keys of the values
				// before 'wpcf-field-slug' => 1  /  after: 'types-repeatable-group[item-id][field-slug]' => 1
				if( isset( $field_config['conditional']['values'] ) && is_array( $field_config['conditional']['values'] ) ) {
					foreach( $field_config['conditional']['values'] as $original_field_slug => $field_value ) {
						$field_slug = str_replace( 'wpcf-', '', $original_field_slug );

						// check if field is part of rfg
						if( in_array( $field_slug, $group_fields ) ) {
							$rfg_item_field_slug = 'types-repeatable-group[' . $this->field->get_object_id() . '][' . $field_slug . ']';
							$field_config['conditional']['values'][$rfg_item_field_slug] = $field_value;
							unset( $field_config['conditional']['values'][$original_field_slug] );
						} else if( isset( $rfg_parents ) ) {
							// check parent rfgs
							$parent_id = $this->field->get_object_id();
							foreach( $rfg_parents as $parent ) {
								$parent_id = $this->get_rfg_parent_id_by_rfg_id( $parent_id );
								if( in_array( $field_slug, $parent->get_field_slugs() ) ) {
									$rfg_item_field_slug = 'types-repeatable-group[' . $parent_id. '][' . $field_slug . ']';
									$field_config['conditional']['values'][$rfg_item_field_slug] = $field_value;
									unset( $field_config['conditional']['values'][$original_field_slug] );

									break;
								}
							}
						}
					}
				}
			}

			$this->field_config = $field_config;
		}

		return $this->field_config;
	}

	/**
	 * Render group
	 *
	 * @param bool $echo
	 * @param int|null $group_id
	 *
	 * @return string
	 */
	public function render(
		$echo = false,
		$group_id = null
	) {
		/**
		 * Use filter to set types-related-content to "true"
		 * This is necessary to make sure that all WYSIWYG fields will have unique ID
		 */
		add_filter( 'toolset_field_factory_get_attributes', array( $this, 'add_filter_attributes' ), 10, 2 );

		$field_config = $this->get_field_config( $group_id );

		$value_in_intermediate_format = $this->field->get_value();
		$output = wptoolset_form_field( $this->get_form_id(), $field_config, $value_in_intermediate_format );

		if ( $echo ) {
			echo $output;
		}
		remove_filter( 'toolset_field_factory_get_attributes', array( $this, 'add_filter_attributes' ), 10 );

		return $output;
	}


	/**
	 * In case of wysiwyg fields set types-related-content to true
	 * to make sure that field ID is unique
	 * @param $attributes
	 * @param $field
	 *
	 * @return array
	 */
	public function add_filter_attributes( $attributes, $field ) {
		$field_type = $field->getType();
		if( 'wysiwyg' ==  $field_type ){
			$attributes['types-related-content'] = true;
		}
		return $attributes;
	}

	/**
	 * Helper function to get the parent ID of an RFG by using the ID of the rfg
	 * In the context that this is called on the post edit screen, we know for sure the RFG has a parent.
	 *
	 * @param $rfg_id
	 *
	 * @return int
	 */
	private function get_rfg_parent_id_by_rfg_id( $rfg_id ) {
		$associations_query = $this->query_factory->associations_v2();
		$parent_id = $associations_query
			->add( $associations_query->child_id( $rfg_id ) )
			->return_element_ids( new Toolset_Relationship_Role_Parent() )
			->limit( 1 )
			->get_results();

		return $parent_id[0];
	}
}
