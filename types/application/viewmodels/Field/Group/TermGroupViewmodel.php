<?php

namespace OTGS\Toolset\Types\Field\Group;

/**
 * Viewmodel for the term field group model.
 *
 * @since 3.2.5
 */
class TermGroupViewmodel extends AbstractGroupViewmodel {


	const EDIT_PAGE_SLUG = 'wpcf-termmeta-edit';

	const DELETE_PAGE_SLUG = 'delete_group';


	/**
	 * @return array
	 */
	public function to_json() {
		$json_data = parent::to_json();

		$taxonomies = $this->get_associated_taxonomies();
		if ( empty( $taxonomies ) ) {
			$json_data['taxonomies'] = __( 'Any', 'wpcf' );
		} else {
			$taxonomy_labels = array();
			foreach ( $taxonomies as $taxonomy_slug ) {
				$taxonomy_labels[] = sprintf(
					'<a href="%1$s">%2$s</a>',
					esc_url( admin_url( 'admin.php?page=wpcf-edit-tax&wpcf-tax=' .  $taxonomy_slug ) ),
					\Types_Utils::taxonomy_slug_to_label( $taxonomy_slug )
				);
			}
			$json_data['taxonomies'] = implode( ', ', $taxonomy_labels );
		}

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


	/**
	 * Get taxonomies that are associated with this field group.
	 *
	 * @return string[] Taxonomy slugs. Empty array means that this group should be displayed with all taxonomies.
	 * @since 2.3
	 */
	private function get_associated_taxonomies() {
		$postmeta = get_post_meta( $this->field_group->get_id(), \Toolset_Field_Group_Term::POSTMETA_ASSOCIATED_TAXONOMY, false );

		// Survive empty or whitespace taxonomy slugs (skip them). They are invalid values but
		// if we have only them, we need to return an empty array to keep the group displayed everywhere.
		foreach ( $postmeta as $index => $taxonomy_slug ) {
			$taxonomy_slug = trim( $taxonomy_slug );
			if ( empty( $taxonomy_slug ) ) {
				unset( $postmeta[ $index ] );
			}
		}

		$postmeta = array_filter( $postmeta, function ( $value ) {
			$value = trim( $value );
			return ( ! empty( $value ) );
		} );

		return toolset_ensarr( $postmeta );
	}

}
