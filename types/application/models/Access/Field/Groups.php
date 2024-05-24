<?php

namespace OTGS\Toolset\Types\Access\Field;

use OTGS\Toolset\Types\Access\Exception;

/**
 * Class Groups
 *
 * @package OTGS\Toolset\Types\Access\Field
 *
 * @todo consider refactoring of \WPCF_Rolse to make this testable (or a bridge)
 *
 * @since 3.2
 */
class Groups {

	/** @var \WPCF_Roles */
	private $legacy_access_control;

	/**
	 * Groups constructor.
	 *
	 * @param \WPCF_Roles $legacy_control
	 */
	public function __construct( \WPCF_Roles $legacy_control ) {
		$this->legacy_access_control = $legacy_control;
	}


	/**
	 * Check if the current user can delete a group for posts.
	 *
	 * @return bool
	 * @throws Exception
	 */
	public function current_user_can_delete_group_for_posts() {
		if ( ! $this->legacy_access_control->user_can_create( 'custom-field' ) ) {
			throw new Exception( __( 'You do not have permissions for that.', 'wpcf' ) );
		}

		return true;
	}

	/**
	 * Check if the current user can delete a group for posts.
	 *
	 * @return bool
	 * @throws Exception
	 */
	public function current_user_can_delete_group_for_users() {
		if ( ! $this->legacy_access_control->user_can_create( 'user-meta-field' ) ) {
			throw new Exception( __( 'You do not have permissions for that.', 'wpcf' ) );
		}

		return true;
	}

	/**
	 * Check if the current user can delete a group for posts.
	 *
	 * @return bool
	 * @throws Exception
	 */
	public function current_user_can_delete_group_for_terms() {
		if ( ! $this->legacy_access_control->user_can_create( 'term-field' ) ) {
			throw new Exception( __( 'You do not have permissions for that.', 'wpcf' ) );
		}

		return true;
	}
}