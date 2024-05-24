<?php


namespace OTGS\Toolset\Types\Controller\Field\Group\Term;

use OTGS\Toolset\Types\Access\Field\Groups as AccessGroups;
use OTGS\Toolset\Types\Field\Group\Term\Deletion as FieldGroupDeletion;

/**
 * Class Deletion
 * @package OTGS\Toolset\Types\Controller\Field\Group\Term
 *
 * @since 3.2
 */
class Deletion {
	/** @var AccessGroups */
	private $access;

	/** @var FieldGroupDeletion  */
	private $field_group_deletion;

	/**
	 * Deletion constructor.
	 *
	 * @param AccessGroups $access
	 * @param FieldGroupDeletion $field_group_deletion
	 */
	public function __construct( AccessGroups $access, FieldGroupDeletion $field_group_deletion ) {
		$this->access = $access;
		$this->field_group_deletion = $field_group_deletion;
	}

	/**
	 *
	 * @param \Toolset_Field_Group_Term $group
	 *
	 * @throws \OTGS\Toolset\Types\Access\Exception
	 */
	public function delete( \Toolset_Field_Group_Term $group ) {
		// check if current user can delete the posts fields group
		$this->access->current_user_can_delete_group_for_terms();

		// delete the group
		$this->field_group_deletion->delete( $group );
	}
}