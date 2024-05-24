<?php

/**
 * Repository for templates in Types.
 *
 * See Toolset_Renderer for a detailed usage instructions.
 *
 * @since m2m
 */
class Types_Output_Template_Repository extends Toolset_Output_Template_Repository_Abstract {

	const INSERT_POST_REFERENCE_FIELD_TEMPLATE = 'post_reference_insert.phtml';

	const INSERT_POST_REFERENCE_FIELD_WIZARD_FIRST_TEMPLATE = 'post_reference_insert_wizard_first.phtml';

	const INSERT_POST_REFERENCE_FIELD_WIZARD_SECOND_TEMPLATE = 'post_reference_insert_wizard_second.phtml';

	const INSERT_POST_REFERENCE_FIELD_WIZARD_THIRD_TEMPLATE = 'post_reference_insert_wizard_third.phtml';

	const INSERT_REPEATING_FIELDS_GROUP_TEMPLATE = 'rfg_insert.phtml';

	const INSERT_REPEATING_FIELDS_GROUP_WIZARD_FIRST_TEMPLATE = 'rfg_insert_wizard_first.phtml';

	const INSERT_REPEATING_FIELDS_GROUP_WIZARD_SECOND_TEMPLATE = 'rfg_insert_wizard_second.phtml';

	const INSERT_REPEATING_FIELDS_GROUP_WIZARD_THIRD_TEMPLATE = 'rfg_insert_wizard_third.phtml';

	const FIELD_GROUP_EDIT_INTERMEDIARY_MODAL_TEMPLATE = 'intermediary_posts_modal.phtml';

	const RELATIONSHIPS_PAGE_M2M_INACTIVE = '/page/relationships/inactive.twig';

	const POST_TYPE_METABOX_RELATIONSHIPS = '/page/post_type/metabox_relationships.twig';

	const POST_TYPE_METABOX_RELATIONSHIPS_UNSAVED = '/page/post_type/metabox_relationships_unsaved.twig';

	const POST_TYPE_METABOX_RELATIONSHIPS_INTERMEDIARY = '/page/post_type/metabox_relationships_intermediary.twig';

	const POST_TYPE_METABOX_RELATIONSHIPS_INTERMEDIARY_ORPHAN = '/page/post_type/metabox_relationships_intermediary_orphan.twig';

	/**
	 * @var array|null Template definition cache.
	 */
	private $templates;


	/** @var Types_Output_Template_Repository */
	private static $instance;


	/**
	 * @return Types_Output_Template_Repository
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}


	/**
	 * @inheritdoc
	 * @return string
	 */
	protected function get_default_base_path() {
		return $this->constants->constant( 'TYPES_TEMPLATES' );
	}


	/**
	 * Get the array with template definitions.
	 *
	 * @return array
	 */
	protected function get_templates() {
		if ( null === $this->templates ) {
			$this->templates = array(
				self::INSERT_POST_REFERENCE_FIELD_TEMPLATE => array(
					'base_path' => TYPES_TEMPLATES . '/field/post-reference',
					'namespaces' => array(),
				),
				self::INSERT_POST_REFERENCE_FIELD_WIZARD_FIRST_TEMPLATE => array(
					'base_path' => TYPES_TEMPLATES . '/field/post-reference',
					'namespaces' => array(),
				),
				self::INSERT_POST_REFERENCE_FIELD_WIZARD_SECOND_TEMPLATE => array(
					'base_path' => TYPES_TEMPLATES . '/field/post-reference',
					'namespaces' => array(),
				),
				self::INSERT_POST_REFERENCE_FIELD_WIZARD_THIRD_TEMPLATE => array(
					'base_path' => TYPES_TEMPLATES . '/field/post-reference',
					'namespaces' => array(),
				),
				self::INSERT_REPEATING_FIELDS_GROUP_TEMPLATE => array(
					'base_path' => TYPES_TEMPLATES . '/field/group/repeatable/backend/post-edit',
					'namespaces' => array(),
				),
				self::INSERT_REPEATING_FIELDS_GROUP_WIZARD_FIRST_TEMPLATE => array(
					'base_path' => TYPES_TEMPLATES . '/field/group/repeatable/backend/post-edit',
					'namespaces' => array(),
				),
				self::INSERT_REPEATING_FIELDS_GROUP_WIZARD_SECOND_TEMPLATE => array(
					'base_path' => TYPES_TEMPLATES . '/field/group/repeatable/backend/post-edit',
					'namespaces' => array(),
				),
				self::INSERT_REPEATING_FIELDS_GROUP_WIZARD_THIRD_TEMPLATE => array(
					'base_path' => TYPES_TEMPLATES . '/field/group/repeatable/backend/post-edit',
					'namespaces' => array(),
				),
				self::FIELD_GROUP_EDIT_INTERMEDIARY_MODAL_TEMPLATE => array(
					'base_path' => TYPES_TEMPLATES . '/page/field_group_edit',
					'namespaces' => array(),
				),
				self::RELATIONSHIPS_PAGE_M2M_INACTIVE => array(
					'base_path' => TYPES_TEMPLATES,
					'namespaces' => array(),
				),
				self::POST_TYPE_METABOX_RELATIONSHIPS => array(
					'base_path' => TYPES_TEMPLATES,
					'namespaces' => array(),
				),
				self::POST_TYPE_METABOX_RELATIONSHIPS_UNSAVED => array(
					'base_path' => TYPES_TEMPLATES,
					'namespaces' => array(),
				),
				self::POST_TYPE_METABOX_RELATIONSHIPS_INTERMEDIARY => array(
					'base_path' => TYPES_TEMPLATES,
					'namespaces' => array(),
				),
				self::POST_TYPE_METABOX_RELATIONSHIPS_INTERMEDIARY_ORPHAN => [
					'base_path' => TYPES_TEMPLATES,
					'namespaces' => [],
				],
			);
		}

		return $this->templates;
	}

}
