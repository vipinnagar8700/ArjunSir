<?php

namespace OTGS\Toolset\Types\Field\Group;


/**
 * Implements functionality shared among field group viewmodels of all domains.
 *
 * @since 3.2.5
 */
abstract class AbstractGroupViewmodel implements ViewmodelInterface {


	/** @var \Toolset_Field_Group */
	protected $field_group;


	/**
	 * AbstractGroupViewmodel constructor.
	 *
	 * @param \Toolset_Field_Group $field_group
	 */
	public function __construct( \Toolset_Field_Group $field_group ) {
		$this->field_group = $field_group;
	}


	/**
	 * @inheritdoc
	 * @return array
	 */
	public function to_json() {
		$json = array(
			'id' => $this->field_group->get_id(),
			'slug' => $this->field_group->get_slug(),
			'name' => $this->field_group->get_name(),
			'description' => $this->field_group->get_description(),
			'containsRFG' => $this->field_group->contains_repeating_field_group(),
			'displayName' => $this->field_group->get_name(),
			'editLink' => $this->get_edit_link(),
			'deleteLink' => $this->get_delete_link(),
			'isActive' => $this->field_group->is_active(),
			'isRequired' => $this->field_group->is_required(),
		);

		return $json;
	}


	/**
	 * Get the backend edit link.
	 *
	 * @return string
	 * @since 2.3
	 */
	private function get_edit_link() {
		return admin_url() . 'admin.php?page=' . $this->get_edit_page_slug() . '&group_id=' . $this->field_group->get_id();
	}


	/**
	 * Gets delete group link.
	 *
	 * @return string
	 * @since 2.3
	 */
	private function get_delete_link() {
		return esc_url(
			add_query_arg(
				array(
					'action' => 'wpcf_ajax',
					'wpcf_action' => $this->get_delete_page_slug(),
					'group_id' => $this->field_group->get_id(),
					'wpcf_ajax_update' => 'wpcf_list_ajax_response_' . $this->field_group->get_id(),
					'_wpnonce' => wp_create_nonce( 'delete_group' ),
					'wpcf_warning' => rawurlencode( __( 'Are you sure?', 'wpcf' ) ),
				),
				admin_url( 'admin-ajax.php' )
			)
		);
	}


	/**
	 * Gets edit page slug.
	 *
	 * @return string
	 */
	protected abstract function get_edit_page_slug();


	/**
	 * Gets delete page slug.
	 *
	 * @return string
	 */
	protected abstract function get_delete_page_slug();


	/**
	 * @inheritdoc
	 *
	 * @param null|bool $new_value
	 *
	 * @return bool
	 */
	public function is_active( $new_value = null ) {
		return $this->field_group->is_active( $new_value );
	}


}
