<?php

namespace OTGS\Toolset\Types\Field\Group\User;

/**
 * Handles the Deletion of a User Field Group.
 *
 * @package OTGS\Toolset\Types\Field\Group\User
 *
 * @since 3.2
 */
class Deletion {
	/** @var \OTGS\Toolset\Types\Wordpress\Post\Storage */
	private $wp_post_storage;

	/**
	 * Deletion constructor.
	 * @param \OTGS\Toolset\Types\Wordpress\Post\Storage $wp_post_storage
	 */
	public function __construct( \OTGS\Toolset\Types\Wordpress\Post\Storage $wp_post_storage ) {
		$this->wp_post_storage = $wp_post_storage;
	}

	/**
	 * Deletes given Field Group
	 *
	 * @param \Toolset_Field_Group $group
	 */
	public function delete( \Toolset_Field_Group $group ) {
		// Finally delete the Field Group post
		$this->wp_post_storage->deletePostById( $group->get_id() );
	}
}