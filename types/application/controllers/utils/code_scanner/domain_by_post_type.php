<?php

namespace OTGS\Toolset\Types\Utils\CodeScanner;

/**
 * Common functionality for scanner classes that deal with posts and post types.
 *
 * @package OTGS\Toolset\Types\Utils\CodeScanner
 * @since 3.0-b6
 */
abstract class DomainByPostType extends \Toolset_Wpdb_User {

	/**
	 * @param string $post_type
	 *
	 * @return string
	 */
	public function get_result_domain( $post_type ) {
		switch( $post_type ) {
			case \Toolset_Post_Type_List::VIEW_OR_WPA:
				return __( 'View', 'wpcf' ) . ' / ' . __( 'WordPress Archive', 'wpcf' );
			case \Toolset_Post_Type_List::CONTENT_TEMPLATE:
				return __( 'Content Template', 'wpcf' );
			case \Toolset_Post_Type_List::CRED_POST_FORM:
			case \Toolset_Post_Type_List::CRED_USER_FORM:
			case \Toolset_Post_Type_List::CRED_RELATIONSHIP_FORM:
				return __( 'Toolset Form', 'wpcf' );
			default:
				return __( 'Post content', 'wpcf' );
		}
	}


}