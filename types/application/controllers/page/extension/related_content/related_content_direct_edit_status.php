<?php

/**
 * Handles if a related content is editable by default for an user.
 *
 * @since m2m
 */
class Types_Page_Extension_Related_Content_Direct_Edit_Status {


	const USER_META_KEY = '_types_enable_related_content_direct_editing';

	/**
	 * User ID
	 *
	 * @var int
	 * @since m2m
	 */
	private $user_id;


	/**
	 * If it is enabled
	 *
	 * @var boolean
	 * @since m2m
	 */
	private $is_enabled;


	/**
	 * @param null|int $user_id The user id.
	 *
	 * @since m2m
	 */
	public function __construct( $user_id ) {
		$this->user_id = $user_id;
	}


	/**
	 * Gets if it is enabled
	 *
	 * @return boolean
	 * @since m2m
	 */
	public function get() {
		if ( null === $this->is_enabled ) {
			$this->is_enabled = (bool) get_user_meta( $this->user_id, self::USER_META_KEY, true );
		}

		return $this->is_enabled;
	}


	/**
	 * Sets if it is enabled
	 *
	 * @param boolean $is_enabled If it is enabled.
	 *
	 * @since m2m
	 */
	public function set( $is_enabled ) {
		$this->is_enabled = (bool) $is_enabled;
		update_user_meta( $this->user_id, self::USER_META_KEY, $this->is_enabled );
	}
}
