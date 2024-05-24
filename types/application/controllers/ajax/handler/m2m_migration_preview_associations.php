<?php

use OTGS\Toolset\Common\Relationships\DatabaseLayer\Version1\Toolset_Relationship_Migration_Controller;

/**
 * Get a list of legacy post associations (in batches) that are about to be migrated to m2m.
 *
 * Used from the m2m activation dialog in Toolset Settings.
 *
 * @since m2m
 */
class Types_Ajax_Handler_M2M_Migration_Preview_Associations extends Toolset_Ajax_Handler_Abstract {

	/** @var Toolset_Relationship_Controller */
	private $relationship_controller;

	/** @var Toolset_Relationship_Migration_Controller */
	private $migration_controller;

	/** @var Toolset_Element_Factory */
	private $element_factory;


	/**
	 * Types_Ajax_Handler_M2M_Migration_Preview_Associations constructor.
	 *
	 * @param Toolset_Ajax $ajax_manager
	 * @param Toolset_Relationship_Controller|null $di_relationship_controller
	 * @param Toolset_Relationship_Migration_Controller|null $di_migration_controller
	 * @param Toolset_Element_Factory|null $di_element_factory
	 *
	 * @since m2m
	 */
	public function __construct(
		$ajax_manager,
		Toolset_Relationship_Controller $di_relationship_controller = null,
		Toolset_Relationship_Migration_Controller $di_migration_controller = null,
		Toolset_Element_Factory $di_element_factory = null
	) {
		parent::__construct( $ajax_manager );

		$this->relationship_controller = (
			null === $di_relationship_controller
				? Toolset_Relationship_Controller::get_instance()
				: $di_relationship_controller
		);

		$this->element_factory = (
			null === $di_element_factory
				? new Toolset_Element_Factory()
				: $di_element_factory
		);

		$this->migration_controller = $di_migration_controller;
	}



	/**
	 * @param array $arguments Original action arguments.
	 *
	 * @return void
	 */
	public function process_call( $arguments ) {

		$am = $this->get_am();

		$am->ajax_begin( array( 'nonce' => Types_Ajax::CALLBACK_M2M_MIGRATION_PREVIEW_ASSOCIATIONS ) );

		$this->relationship_controller->initialize();
		$this->relationship_controller->force_autoloader_initialization();

		$migration_controller = $this->get_migration_controller();

		$step_number = (int) toolset_getpost( 'step', 0 );

		// Documented in Toolset_Ajax_Handler_Migrate_To_M2M::process_call().
		$items_per_step = apply_filters( 'toolset_m2m_migration_items_per_step', 100 );

		$offset = $items_per_step * $step_number;

		$associations_to_migrate = $migration_controller->get_associations_to_migrate( $offset, $items_per_step );

		$results = array();
		foreach( $associations_to_migrate as $association_to_migrate ) {

			$parent_id = toolset_getarr( $association_to_migrate, 'parent_id' );
			$child_id = toolset_getarr( $association_to_migrate, 'child_id' );

			try {
				// We specifically require posts and not translation sets.
				$parent = $this->element_factory->get_post_untranslated( $parent_id );
				$child = $this->element_factory->get_post_untranslated( $child_id );

			} catch( Exception $e ) {
				continue;
			}

			$results[] = array(
				'parent' => array(
					'title' => $parent->get_title(),
					'slug' => $parent->get_slug()
				),
				'child' => array(
					'title' => $child->get_title(),
					'slug' => $child->get_slug()
				),
				'relationshipSlug' => $association_to_migrate['relationship_slug']
			);
		}

		$this->ajax_finish( array( 'results' => $results ), true );
	}


	private function get_migration_controller() {
		if( null === $this->migration_controller ) {
			$this->migration_controller = new Toolset_Relationship_Migration_Controller();
		}
		return $this->migration_controller;
	}

}
