<?php

namespace OTGS\Toolset\Types\Compatibility\Yoast\Field;

use Exception;
use Toolset_Field_Definition;
use Toolset_Field_Group_Post_Factory;
use WP_Post;

/**
 * @since 3.1
 */
class Repository {

	/** @var Toolset_Field_Group_Post_Factory */
	private $field_group_factory;

	/** @var Factory */
	private $field_factory;


	/**
	 * FieldRepository constructor.
	 *
	 * @param Toolset_Field_Group_Post_Factory $field_group_factory
	 * @param Factory $field_factory
	 */
	public function __construct( Toolset_Field_Group_Post_Factory $field_group_factory, Factory $field_factory ) {
		$this->field_group_factory = $field_group_factory;
		$this->field_factory = $field_factory;
	}


	/**
	 * @param WP_Post|int|mixed $post
	 *
	 * @return IField[]
	 */
	public function getFieldsByPost( $post ) {
		if ( ! is_object( $post ) && ! is_array( $post ) && is_numeric( $post ) ) {
			// support id as input
			$post = get_post( $post );
		}

		if ( is_array( $post ) && isset( $post['ID'] ) ) {
			// support array of post as input
			$post = get_post( $post['ID'] );
		}

		if ( ! $post instanceof WP_Post ) {
			// something went wrong
			return array();
		}

		$field_groups = $this->field_group_factory->get_groups_by_post_type( $post->post_type );
		$fields = array();

		foreach ( $field_groups as $group ) {
			foreach ( $group->get_field_definitions() as $field ) {
				if ( $field_yoast = $this->getFieldByDefinition( $field ) ) {
					$fields[] = $field_yoast;
				}
			}
		}

		return $fields;
	}


	/**
	 * @param Toolset_Field_Definition $field_definition
	 * @param int|null $rfg_id
	 *
	 * @return IField|false
	 */
	public function getFieldByDefinition( Toolset_Field_Definition $field_definition, $rfg_id = null ) {
		try {
			$field = $this->field_factory->createField(
				$field_definition->get_type()->get_slug(),
				$field_definition->get_slug()
			);

			$field_arr = $field_definition->get_definition_array();
			$display_as = isset( $field_arr['data'], $field_arr['data']['extra-yoast-display-as'] )
				? $field_arr['data']['extra-yoast-display-as']
				: false;

			if ( AField::OPTION_DO_NOT_USE === $display_as ) {
				// user don't want to use the field on Yoast Analysis
				return false;
			}

			if ( empty( $display_as ) ) {
				// if nothing is set we use the default display as
				$display_as = $field->getDefaultDisplayAs();
			}

			$field->setDisplayAs( $display_as );

			if ( $rfg_id === null ) {
				// normal field group
				$field->setInputName( 'wpcf[' . $field_definition->get_slug() . ']' );
			} else {
				// rfg
				$field->setInputName(
					'types-repeatable-group[' . (int) $rfg_id . '][' . $field_definition->get_slug() . ']'
				);
			}

			return $field;
		} catch ( Exception $e ) {
			return false;
		}
	}
}
