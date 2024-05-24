<?php

namespace OTGS\Toolset\Types\Field\Group;


/**
 * Factory for ViewmodelInterface instances.
 *
 * @since 3.2.5
 */
class ViewmodelFactory {

	/**
	 * Produce an viewmodel instance depending on the domain of the field group.
	 *
	 * @param \Toolset_Field_Group $field_group
	 * @return ViewmodelInterface
	 */
	public function create_viewmodel( \Toolset_Field_Group $field_group ) {
		if( $field_group instanceof \Toolset_Field_Group_Post ) {
			return new PostGroupViewmodel( $field_group );
		} elseif( $field_group instanceof  \Toolset_Field_Group_User ) {
			return new UserGroupViewmodel( $field_group );
		} elseif( $field_group instanceof  \Toolset_Field_Group_Term ) {
			return new TermGroupViewmodel( $field_group );
		}

		throw new \InvalidArgumentException();
	}

}
