<?php

/**
 * This class currently only does actions related to RFG groups.
 * Any future or legacy actions for "delete_post" should go here aswell.
 *
 * This class is currently only loaded if m2m is active (by Types_M2M controller).
 *
 * @since m2m
 */
class Types_Post_Deletion {

	/**
	 * State of process
	 *
	 * @var bool
	 */
	private static $is_running;


	/**
	 * @action before_delete_post
	 *
	 * @param int $postid
	 */
	public function before_delete_post( $postid ) {
		if ( self::$is_running ) {
			// prevent self calls on our item deletion
			return;
		}
		self::$is_running = true;

		$post = get_post( $postid );

		if ( $post instanceof WP_Post ) {
			$types_post_builder = new Types_Post_Builder();
			$types_post_builder->set_wp_post( $post );
			$types_post_builder->load_assigned_field_groups( 9999 );
			$types_post = $types_post_builder->get_types_post();

			if ( $field_groups = $types_post->get_field_groups() ) {
				$this->delete_field_groups_items( $field_groups );
			}
		}

		self::$is_running = false;
	}


	/**
	 * @param Types_Field_Group_Post[] $field_groups
	 */
	private function delete_field_groups_items( array $field_groups ) {
		if ( empty( $field_groups ) ) {
			// no field groups to delete
			return;
		}

		foreach ( $field_groups as $field_group ) {
			if ( ! $field_group instanceof Types_Field_Group_Repeatable ) {
				// NO RFG
				// search for rfg and delete if exists
				if ( $rfgs = $field_group->get_repeatable_groups() ) {
					$this->delete_field_groups_items( $rfgs );
				}

				continue;
			}

			// repeatable field group
			if ( $items = $field_group->get_posts() ) {
				foreach ( $items as $item ) {
					/**@var $item Types_Field_Group_Repeatable_Item */
					if ( $nested_rfgs = $item->get_field_groups() ) {
						$this->delete_field_groups_items( $nested_rfgs );
					}

					// delete item (second parameter to true will bypass the trash and really delete the item)
					wp_delete_post( $item->get_wp_post()->ID, true );
				}
			}
		}
	}
}
