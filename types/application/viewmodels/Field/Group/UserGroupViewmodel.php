<?php

namespace OTGS\Toolset\Types\Field\Group;

/**
 * Viewmodel for the user field group model.
 *
 * @since 3.2.5
 */
class UserGroupViewmodel extends AbstractGroupViewmodel {

	const EDIT_PAGE_SLUG = 'wpcf-edit-usermeta';

	const DELETE_PAGE_SLUG = 'delete_group';


	/**
	 * @return array
	 */
	public function to_json() {
		$json_data = parent::to_json();

		$json_data['availableFor'] = $this->get_available_for();

		return $json_data;
	}


	/**
	 * Gets edit page slug.
	 *
	 * @return string
	 */
	protected function get_edit_page_slug() {
		return self::EDIT_PAGE_SLUG;
	}


	/**
	 * Gets delete page slug.
	 *
	 * @return string
	 */
	protected function get_delete_page_slug() {
		return self::DELETE_PAGE_SLUG;
	}


	private function get_available_for() {
		$show_for = get_post_meta( $this->field_group->get_id(), '_wp_types_group_showfor', true );
		if ( empty( $show_for ) || 'all' === $show_for ) {
			$show_for = array();
		} else {
			$show_for = explode( ',', trim( $show_for, ',' ) );
		}

		if ( function_exists( 'wpcf_access_register_caps' ) ) {
			$show_for = __( 'This groups visibility is also controlled by the Access plugin.', 'wpcf' );
		} else {
			$show_for = ( 0 === count( $show_for ) ) ?
				__( 'Displayed for all users roles', 'wpcf' ) :
				ucwords( implode( ', ', $show_for ) );
		}
		return $show_for;
	}

}
