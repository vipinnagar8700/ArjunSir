<?php

use OTGS\Toolset\Common\M2M\Association\Repository;
use OTGS\Toolset\Common\Relationships\API\Factory;
use OTGS\Toolset\Common\Result\ResultInterface;
use OTGS\Toolset\Types\Post\Meta\Associations;
use OTGS\Toolset\Types\Wordpress\Option\Associations\ImportAvailable;
use OTGS\Toolset\Types\Wordpress\Postmeta\Storage;

/**
 * No namespace possible, need to refactor our Ajax Handler
 *
 * @since 3.0
 */
class Types_Ajax_Handler_Associations_Import extends Toolset_Ajax_Handler_Abstract {


	/** @var Factory */
	private $relationships_factory;


	public function __construct( Toolset_Ajax $ajax_manager, Factory $factory ) {
		parent::__construct( $ajax_manager );
		$this->relationships_factory = $factory;
	}


	/**
	 * Processes the Ajax call
	 *
	 * @param array $arguments Original action arguments.
	 *
	 * @return void
	 */
	public function process_call( $arguments ) {
		$this->get_ajax_manager()->ajax_begin(
			array( 'nonce' => Types_Ajax::CALLBACK_ASSOCIATIONS_IMPORT )
		);

		// Action
		$action = sanitize_text_field( toolset_getpost( 'associations_import_action' ) );

		// route action
		$this->route( $action );
	}


	/**
	 * Route calls via ajax
	 *
	 * @param string $action
	 */
	private function route( $action ) {
		switch ( $action ) {
			case 'getAssociations':
				$this->getAssociations();
				return;
			case 'importAssociations':
				$this->importAssociations();
				return;
			case 'deleteExistingAssociationsFromImportList':
				$this->deleteExistingAssociationsFromImportList();
				return;
			case 'deleteBrokenAssociationsFromImportList':
				$this->deleteBrokenAssociationsFromImportList();
				return;
		}
	}


	/**
	 * On page load this function is called to load associations
	 */
	private function getAssociations() {
		$limit = (int) toolset_getpost( 'associations_import_limit', null );
		$offset = (int) toolset_getpost( 'associations_import_offset', null );

		global $wpdb;

		$association_repository = new Repository(
			new Toolset_Relationship_Query_Factory(),
			new Toolset_Relationship_Role_Parent(),
			new Toolset_Relationship_Role_Child(),
			new Toolset_Relationship_Role_Intermediary(),
			new Toolset_Element_Domain()
		);

		$associations = new \OTGS\Toolset\Types\Post\Import\Associations(
			new Associations(),
			new \OTGS\Toolset\Types\Wordpress\Post\Storage( $wpdb ),
			new Storage( $wpdb ),
			Toolset_Relationship_Definition_Repository::get_instance(),
			$association_repository,
			new \OTGS\Toolset\Types\Post\Import\Association\Factory(),
			$this->relationships_factory
		);

		$associations->loadAssociationsByChunks( $limit, $offset );

		$this->get_ajax_manager()->ajax_finish( array(
			'associations' => $associations->getAssociations(),
		) );
	}


	/**
	 * The import process
	 */
	private function importAssociations() {
		// delete the notice for available associations
		$this->deleteNoticeAboutAssociationsAvailable();

		// return preparation
		$result = array(
			'success' => 0,
			'error' => 0,
		);

		// associations to import
		$associations = html_entity_decode( stripslashes( toolset_getpost( 'associations_import', null ) ) );
		$associations = json_decode( $associations, true );

		if ( empty( $associations ) ) {
			$this->get_ajax_manager()->ajax_finish( $result );
		}

		// we have associations
		global $wpdb;
		$storage_postmeta = new Storage( $wpdb );
		$postmeta_associations = new Associations();

		foreach ( $associations as $association ) {
			try {
				$imported = $this->relationships_factory->database_operations()->create_association(
					sanitize_text_field( $association['relationship']['slug'] ),
					(int) $association['parent']['id'],
					(int) $association['child']['id'],
					(int) $association['intermediary']['id']
				);

				if ( $imported instanceof ResultInterface ) {
					$result['error'] ++;
				} else {
					$relationship = $imported->get_definition();
					$origin = $relationship->get_origin();
					if ( $origin instanceof Toolset_Relationship_Origin_Post_Reference_Field ) {
						update_post_meta(
							$imported->get_element_id( new Toolset_Relationship_Role_Child() ),
							'wpcf-' . $relationship->get_slug(),
							$imported->get_element_id( new Toolset_Relationship_Role_Parent() )
						);
					}

					$result['success'] ++;
					$storage_postmeta->deleteStringFromPostMeta(
						(int) $association['meta']['postId'],
						sanitize_text_field( $association['meta']['key'] ),
						sanitize_text_field( $association['meta']['associationString'] ),
						$postmeta_associations::BETWEEN_MULTIPLE_ASSOCIATIONS
					);
				}

			} catch ( Exception $e ) {
				$result['error'] ++;
			}
		}

		$this->get_ajax_manager()->ajax_finish( $result );
	}


	/**
	 * This will just remove all associations postmeta which has no empty value.
	 */
	private function deleteExistingAssociationsFromImportList() {
		// delete the notice for available associations
		$this->deleteNoticeAboutAssociationsAvailable();

		global $wpdb;

		$meta_associations = new Associations();
		$meta_storage = new Storage( $wpdb );

		$postmeta = $meta_associations->getKeyWithWildcardForMysql();
		$deleted_rows = $meta_storage->deleteEmptyPostMetaByKey( $postmeta );

		$this->get_ajax_manager()->ajax_finish( array( 'deleted' => $deleted_rows ) );
	}


	/**
	 * Deletes all broken associations.
	 * This runs after the import process and only includes broken associations, which were broken BEFORE the import.
	 * If the very very edge case happens that an association breaks on the import process it's not affected by this,
	 * so the user can check the issue and decided to delete or fix it.
	 */
	private function deleteBrokenAssociationsFromImportList() {
		// delete the notice for available associations
		$this->deleteNoticeAboutAssociationsAvailable();

		$associations = toolset_getpost( 'associations_to_delete', null );

		if ( empty( $associations ) ) {
			return;
		}

		global $wpdb;
		$storage_postmeta = new Storage( $wpdb );
		$postmeta_associations = new Associations();

		foreach ( $associations as $association ) {
			$storage_postmeta->deleteStringFromPostMeta(
				(int) $association['meta']['postId'],
				sanitize_text_field( $association['meta']['key'] ),
				sanitize_text_field( $association['meta']['associationString'] ),
				$postmeta_associations::BETWEEN_MULTIPLE_ASSOCIATIONS
			);
		}
	}


	/**
	 * This removes the wp_option that there are associations available.
	 * It doesn't hurt to remove it, even if not all associations are imported.
	 */
	private function deleteNoticeAboutAssociationsAvailable() {
		// delete notice about associations available
		$option = new ImportAvailable();
		$option->deleteOption();

	}
}
