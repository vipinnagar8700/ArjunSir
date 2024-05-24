<?php

namespace OTGS\Toolset\Common\Relationships\DatabaseLayer;


use IToolset_Association;
use Toolset_Element_Exception_Element_Doesnt_Exist;
use Toolset_Result;

/**
 * Interface for handling the persistence of associations, from IToolset_Association object
 * to a wpdb call and back.
 *
 * Like Toolset_Relationship_Definition_Persistence, this should not be used from outside
 * of the m2m API. Everything required for working with associations should be
 * implemented on IToolset_Relationship_Definition.
 *
 * @since 4.0
 */
interface AssociationPersistence {

	/**
	 * Load a native association from the database.
	 *
	 * @param int $association_uid Association UID.
	 *
	 * @return null|IToolset_Association The association instance
	 *     or null if it couln't have been loaded.
	 * @deprecated Do not use this outside of the m2m API, instead, use the association query.
	 */
	public function load_association_by_uid( $association_uid );


	/**
	 * Insert a new association in the database.
	 *
	 * @param IToolset_Association $association
	 *
	 * @return IToolset_Association
	 * @throws Toolset_Element_Exception_Element_Doesnt_Exist
	 */
	public function insert_association( IToolset_Association $association );


	/**
	 * Delete an association from the database.
	 *
	 * Also delete an intermediary post if it exists.
	 *
	 * @param IToolset_Association $association
	 *
	 * @return Toolset_Result
	 * @since m2m
	 */
	public function delete_association( IToolset_Association $association );


	/**
	 * Do the toolset_before_association_delete action.
	 *
	 * See report_association_change() for action parameter information.
	 *
	 * @param IToolset_Association $association
	 *
	 * @since 2.7
	 */
	public function report_before_association_delete( IToolset_Association $association );
}
