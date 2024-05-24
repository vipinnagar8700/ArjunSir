<?php

/**
 * item attribute GUI selector provider for one-to-many relationships.
 *
 * @since m2m
 */
class Toolset_Shortcode_Attr_Item_Gui_O2m extends Toolset_Shortcode_Attr_Item_Gui_Base {


	/**
	 * Set options for post selectors on O2M relationships.
	 *
	 * @since m2m
	 */
	protected function set_options() {
		$origin = $this->relationship_definition->get_origin()->get_origin_keyword();

		switch ( $origin ) {
			case Toolset_Relationship_Origin_Post_Reference_Field::ORIGIN_KEYWORD:
				$this->set_parent_reference_option();
				break;
			case Toolset_Relationship_Origin_Repeatable_Group::ORIGIN_KEYWORD:
				$this->set_parent_repeatable_group_option();
				break;
			default:
				$this->set_parent_option();
				$this->set_child_option();
				break;
		}
	}

	protected function set_parent_reference_option() {
		if (
			null === $this->current_post_object
			|| ! in_array( $this->current_post_object->name, $this->parent_types )
		) {
			$option = new Toolset_Shortcode_Attr_Item_Gui_Option(
				$this->relationship_definition,
				Toolset_Relationship_Role::PARENT,
				$this
			);
			$this->options[] = $option->get_option();

		}
	}

	/**
	 * Register o2m relationships defined as RFGs.
	 *
	 * @since Views 2.9.3
	 */
	protected function set_parent_repeatable_group_option() {
		if (
			null === $this->current_post_object
			|| ! in_array( $this->current_post_object->name, $this->parent_types, true )
		) {
			$option = new Toolset_Shortcode_Attr_Item_Gui_Option(
				$this->relationship_definition,
				Toolset_Relationship_Role::PARENT,
				$this
			);
			$this->options[] = $option->get_option();

		}
	}

	protected function set_parent_option() {
		if (
			null === $this->current_post_object
			|| ! in_array( $this->current_post_object->name, $this->parent_types )
		) {
			$option = new Toolset_Shortcode_Attr_Item_Gui_Option(
				$this->relationship_definition,
				Toolset_Relationship_Role::PARENT,
				$this
			);

			$this->options[] = $option->get_option();

		}
	}

	private function set_child_option() {
		$option = new Toolset_Shortcode_Attr_Item_Gui_Option(
			$this->relationship_definition,
			Toolset_Relationship_Role::CHILD,
			$this
		);
		$option->set_property( 'is_disabled', true );
		$option->set_property(
			'pointer_content',
			'<h3>' . sprintf(
				__( '%1$s (one-to-many relationship)', 'wpv-views' ),
				$this->relationship_definition->get_display_name()
			) . '</h3><p>' . sprintf(
				__( 'To display the %1$s that are connected to each %2$s, you will need to create a View.', 'wpv-views' ),
					'<strong>' . $this->get_post_type_label_by_role( Toolset_Relationship_Role::CHILD ) . '</strong>',
					'<strong>' . $this->get_post_type_label_by_role( Toolset_Relationship_Role::PARENT, true ) . '</strong>'
			) . '</p><p>' . sprintf(
				__( '%1$sDocumentation %2$s%3$s', 'wpv-views' ),
				'<a href="'
					. $this->get_documentation_link(
						'https://toolset.com/course-lesson/displaying-related-posts/',
						array(
							'utm_source'	=> 'plugin',
							'utm_campaign'	=> 'toolset',
							'utm_medium'	=> 'gui',
							'utm_term'		=> 'Documentation'
						)
					)
					. '" target="_blank">',
				'<i class="fa fa-external-link"></i>',
				'</a>'
			) . '</p>'
		);

		$this->options[] = $option->get_option();
	}

}
