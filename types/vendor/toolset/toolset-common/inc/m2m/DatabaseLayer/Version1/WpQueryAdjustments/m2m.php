<?php

namespace OTGS\Toolset\Common\Relationships\DatabaseLayer\Version1;

use IToolset_Post;
use OTGS\Toolset\Common\Relationships\API\RelationshipRole;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\WpQueryExtension\AbstractRelationshipsExtension;
use WP_Query;

/**
 * Adjust the WP_Query functionality for m2m relationships.
 *
 * This assumes m2m is enabled.
 *
 * See the superclass for details.
 *
 * Additionally, we also check for meta_key, meta_value, meta_value_num and meta_query
 * for the legacy relationship postmeta and try to transform it into a toolset_relationships condition.
 * See process_legacy_meta_query() for details.
 *
 * @since 2.6.1
 */
class Toolset_Wp_Query_Adjustments_M2m extends AbstractRelationshipsExtension {

	/**
	 * Get the table join manager object attached to the WP_Query instance or create and attach a new one.
	 *
	 * @param WP_Query $query
	 *
	 * @return Toolset_Wp_Query_Adjustments_Table_Join_Manager
	 */
	protected function get_table_join_manager( WP_Query $query ) {
		// This is a dirty hack but still cleanest considering we need to use this object
		// in different callbacks from WP_Query.
		$property_name = 'toolset_join_manager';
		if ( ! property_exists( $query, $property_name ) ) {
			$query->{$property_name} = $this
				->database_layer_factory
				->join_manager_for_wp_query_extension();
		}

		return $query->{$property_name};
	}


	protected function get_join_clause( WP_Query $wp_query ) {
		return $this->get_table_join_manager( $wp_query )->get_join_clauses();
	}


	protected function get_where_clause( WP_Query $wp_query, $relationship_slug, RelationshipRole $role_to_return, RelationshipRole $role_to_query_by, IToolset_Post $related_to_post ) {
		$related_to_post_id = $related_to_post->get_default_language_id();

		$associations_table = $this->get_table_join_manager( $wp_query )->associations_table(
			$relationship_slug, $role_to_return, $role_to_query_by, $related_to_post_id
		);

		$role_to_query_by_column = $this->database_layer_factory->association_database_operations()->role_to_column( $role_to_query_by );

		return $this->wpdb->prepare(
			" AND $associations_table.$role_to_query_by_column = %d ",
			$related_to_post_id
		);
	}
}
