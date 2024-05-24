<?php

namespace OTGS\Toolset\Types\Field\Group\Post;

use OTGS\Toolset\Types\PostType\Part\Repository;

/**
 * Handles the Deletion of a Post Field Group including possible cleanups on CPTs / Relationships (RFG/PRF).
 *
 * @package OTGS\Toolset\Types\Field\Group
 *
 * @since 3.2
 */
class Deletion {

	/** @var \Types_Field_Group_Repeatable_Service */
	private $rfg_service;

	/** @var \Toolset_Relationship_Definition_Repository|null */
	private $_relationship_repository;

	/** @var Repository */
	private $cpt_parts_repository;

	/** @var \Toolset_Field_Definition_Factory_Post */
	private $field_factory;

	/** @var \OTGS\Toolset\Types\Wordpress\Post\Storage */
	private $wp_post_storage;

	/** @var bool */
	private $allow_convert_rfg_to_o2m = false;


	/**
	 * Deletion constructor.
	 *
	 * @param \Types_Field_Group_Repeatable_Service $rfg_service
	 * @param Repository $cpt_parts_repository
	 * @param \Toolset_Field_Definition_Factory_Post $field_factory
	 * @param \OTGS\Toolset\Types\Wordpress\Post\Storage $wp_post_storage
	 * @param \Toolset_Relationship_Definition_Repository $relationship_repository
	 */
	public function __construct(
		\Types_Field_Group_Repeatable_Service $rfg_service,
		Repository $cpt_parts_repository,
		\Toolset_Field_Definition_Factory_Post $field_factory,
		\OTGS\Toolset\Types\Wordpress\Post\Storage $wp_post_storage,
		$relationship_repository = null
	) {
		$this->rfg_service = $rfg_service;
		$this->cpt_parts_repository = $cpt_parts_repository;
		$this->_relationship_repository = $relationship_repository;
		$this->field_factory = $field_factory;
		$this->wp_post_storage = $wp_post_storage;
	}


	private function get_relationship_definition_repository() {
		// Can't use dependency injection for this class because m2m may not be enabled.
		if ( null === $this->_relationship_repository ) {
			$this->_relationship_repository = \Toolset_Relationship_Definition_Repository::get_instance();
		}

		return $this->_relationship_repository;
	}

	/**
	 * Set option to allow user to convert the rfg to a o2m on deletion
	 *
	 * @param bool $setting
	 */
	public function set_allow_convert_rfg_to_o2m( $setting ) {
		$this->allow_convert_rfg_to_o2m = (bool) $setting;
	}

	/**
	 * Deletes given Field Group
	 *
	 * @param \Toolset_Field_Group $group
	 */
	public function delete( \Toolset_Field_Group $group ) {
		// delete possible Repeatable Field Groups and Post Reference Fields
		$this->delete_groups_rfgs_prfs( $group );

		// make sure to have no orphan fields used on CPT listing tables
		$this->delete_orphan_fields_from_cpt_listing_table( $group );

		// Finally delete the Field Group post
		$this->wp_post_storage->deletePostById( $group->get_id() );
	}

	/**
	 * Deletes all Repeatable Field Groups and Post Reference Fields of the given $group
	 *
	 * @param \Toolset_Field_Group $group
	 */
	private function delete_groups_rfgs_prfs( \Toolset_Field_Group $group ) {
		if( ! apply_filters( 'toolset_is_m2m_enabled', false ) ) {
			return;
		}

		// Loop over field slugs and check for rfg and prf
		foreach ( $group->get_field_slugs() as $field_slug ) {
			if ( $rfg = $this->rfg_service->get_object_from_prefixed_string( $field_slug ) ) {
				// delete rfg by respecting the option to convert the rfg to o2m relationship
				$this->rfg_service->delete( $rfg, $this->allow_convert_rfg_to_o2m );
				continue;
			}

			if ( $prf_relationship = $this->get_relationship_definition_repository()->get_definition( $field_slug ) ) {
				// delete relationship of prf
				$this->get_relationship_definition_repository()->remove_definition( $prf_relationship );

				if ( $prf_field_definition = $this->field_factory->load_field_definition( $field_slug ) ) {
					// delete field definition
					$this->field_factory->delete_definition( $prf_field_definition );
				}
			}
		}
	}


	/**
	 * On CPT edit screen the user can select fields to be shown on the CPT listing table.
	 * When a field is set to be shown on the listing table and gets orphan, by deleting the last field group it's
	 * used on, it's impossible for the user to make it not shown on the listing table anymore. To prevent this
	 * state we need to remove the field from any listing table when it's no longer assigned to any group belonging
	 * to the CPT.
	 *
	 * @param \Toolset_Field_Group $group
	 */
	private function delete_orphan_fields_from_cpt_listing_table( \Toolset_Field_Group $group ) {
		$cpts_slug = $this->cpt_parts_repository->get_all_cpt_slugs();
		$group_field_slugs = $group->get_field_slugs();

		foreach ( $cpts_slug as $cpt_slug ) {
			$cpt_listing_fields = $this->cpt_parts_repository->get_cpt_listing_fields_by_slug( $cpt_slug );
			$cpt_field_groups = null; // we load this later, if needed
			$update_required = false;

			foreach ( $group_field_slugs as $field_slug ) {
				if ( ! $cpt_listing_fields->has_field_by_slug( $field_slug ) ) {
					// the cpt does not use this $field_slug on the listing table
					continue;
				}

				// the cpt uses the $field_slug on the listing table,
				// load cpt_field_groups, if not loaded, and check if it's on another field group of the cpt
				if ( $cpt_field_groups === null ) {
					$cpt_field_groups = $this->cpt_parts_repository->get_cpt_field_groups_by_slug( $cpt_slug );
					// the "to-delete" field group needs to be removed
					$cpt_field_groups->remove_field_group_by_slug( $group->get_slug() );
				}

				if ( $cpt_field_groups->contains_field_by_slug( $field_slug ) ) {
					// the field is assigned by another field group,
					// means we do not need to drop it from the listing table
					continue;
				}

				// at this point we know the $field_slug is on the listing table
				// and is on no other field group assigned to the cpt -> REMOVE from listing table
				$cpt_listing_fields->remove_field_by_slug( $field_slug );
				$update_required = true;
			}

			// persist possible changes to the cpt listing fields
			if ( $update_required ) {
				$this->cpt_parts_repository->store_cpt_parts( array( $cpt_listing_fields ) );
			}
		}
	}
}
