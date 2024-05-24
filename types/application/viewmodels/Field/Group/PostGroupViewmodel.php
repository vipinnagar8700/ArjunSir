<?php

namespace OTGS\Toolset\Types\Field\Group;

/**
 * Viewmodel for the post field group model.
 *
 * @since 3.2.5
 */
class PostGroupViewmodel extends AbstractGroupViewmodel {


	const EDIT_PAGE_SLUG = 'wpcf-edit';


	/**
	 * @return array
	 */
	public function to_json() {
		$json_data = parent::to_json();

		// Post types.
		global $wp_post_types;
		$post_types = wpcf_admin_get_post_types_by_group( $json_data['id'] );
		$supports = array();
		if ( ! empty( $post_types ) ) {
			foreach ( $post_types as $key => $post_type_slug ) {
				$supports[] = sprintf(
					'<a href="%1$s">%2$s</a>',
					esc_url( admin_url( 'admin.php?page=wpcf-edit-type&wpcf-post-type=' . $post_type_slug ) ),
					isset( $wp_post_types[$post_type_slug]->labels->singular_name ) ? $wp_post_types[$post_type_slug]->labels->singular_name : $post_type_slug
				);
			}
		}

		$json_data['postTypes'] = empty( $post_types ) ? __( 'All post types', 'wpcf' ) : implode( ', ', $supports );
		$json_data['taxonomies'] = $this->get_associated_taxonomies();

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
		return 'delete_group';
	}


	/**
	 * Get taxonomies related to the group
	 *
	 * @return string
	 * @since 2.3
	 */
	private function get_associated_taxonomies() {
		global $wp_taxonomies;
		$taxonomies = wpcf_admin_get_taxonomies_by_group( $this->field_group->get_id() );
		$data_taxonomies = '';
		if ( empty( $taxonomies ) ) {
			$data_taxonomies = __( 'None', 'wpcf' );
		} else {
			foreach ( $taxonomies as $taxonomy => $terms ) {
				$data_taxonomies .= '<em>' . sprintf(
						'<a href="%1$s">%2$s</a>',
						esc_url( admin_url( 'admin.php?page=wpcf-edit-tax&wpcf-tax=' .  $taxonomy ) ),
						isset( $wp_taxonomies[ $taxonomy ]->labels->singular_name ) ? $wp_taxonomies[ $taxonomy ]->labels->singular_name : $taxonomy
					) . '</em>: ';
				$terms_output = array();
				foreach ( $terms as $term_id => $term ) {
					$terms_output[] = $term['name'];
				}
				$data_taxonomies .= implode( ', ', $terms_output ) . '<br />';
			}
		}
		return $data_taxonomies;
	}

}
