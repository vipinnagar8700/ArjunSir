<?php

use OTGS\Toolset\Common\Relationships\DatabaseLayer\Version1\Toolset_Relationship_Migration_Controller;

/**
 * Get a list of legacy post relationships that are about to be migrated to m2m.
 *
 * Used from the m2m activation dialog in Toolset Settings.
 *
 * @since m2m
 */
final class Types_Ajax_Handler_M2M_Migration_Preview_Relationships extends Toolset_Ajax_Handler_Abstract {


	/** @var Toolset_Relationship_Controller */
	private $relationship_controller;


	/** @var Toolset_Relationship_Migration_Controller */
	private $migration_controller;


	/**
	 * Types_Ajax_Handler_M2M_Migration_Preview_Relationships constructor.
	 *
	 * @param Types_Ajax $ajax_manager
	 * @param Toolset_Relationship_Controller|null $di_relationship_controller
	 * @param Toolset_Relationship_Migration_Controller|null $di_migration_controller
	 */
	public function __construct(
		$ajax_manager,
		Toolset_Relationship_Controller $di_relationship_controller = null,
		Toolset_Relationship_Migration_Controller $di_migration_controller = null
	) {
		parent::__construct( $ajax_manager );

		$this->relationship_controller = (
		null === $di_relationship_controller
			? Toolset_Relationship_Controller::get_instance()
			: $di_relationship_controller
		);

		$this->migration_controller = $di_migration_controller;
	}


	/**
	 * @param array $arguments Original action arguments.
	 *
	 * @return void
	 */
	function process_call( $arguments ) {

		$am = $this->get_ajax_manager();

		$am->ajax_begin( array( 'nonce' => Types_Ajax::CALLBACK_M2M_MIGRATION_PREVIEW_RELATIONSHIPS ) );

		$this->relationship_controller->initialize();
		$this->relationship_controller->force_autoloader_initialization();

		$migration_controller = $this->get_migration_controller();

		$results = $migration_controller->get_legacy_relationship_post_type_pairs();

		$am->ajax_finish( array( 'results' => $results ), true );
	}


	private function get_migration_controller() {
		if( null === $this->migration_controller ) {
			$this->migration_controller = new Toolset_Relationship_Migration_Controller();
		}
		return $this->migration_controller;
	}


}
