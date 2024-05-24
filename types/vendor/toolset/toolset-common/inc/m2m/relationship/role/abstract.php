<?php

use OTGS\Toolset\Common\Relationships\API\RelationshipRole;

/**
 * Note: Keep the IToolset_Relationship_Role interface here for backward compatibility purposes.
 * All role classes must implement it, just RelationshipRole is not enough. Code like this
 * still needs to pass:
 *
 * `$role instanceof IToolset_Relationship_Role`
 */
abstract class Toolset_Relationship_Role_Abstract implements IToolset_Relationship_Role {

	public function __toString() {
		return $this->get_name();
	}


	/**
	 * @inheritDoc
	 */
	public function equals( RelationshipRole $other ) {
		return $this->get_name() === $other->get_name();
	}


	/**
	 * @inheritDoc
	 */
	public function is_in_array( $roles ) {
		foreach ( $roles as $other ) {
			if ( $this->equals( $other ) ) {
				return true;
			}
		}

		return false;
	}


}
