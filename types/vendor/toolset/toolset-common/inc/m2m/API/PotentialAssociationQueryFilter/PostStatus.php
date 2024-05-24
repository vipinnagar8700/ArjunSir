<?php

/**
 * Filter the potential posts association query by the post status.
 *
 * Each Toolset individual plugin can extend this filter to add its own API filters, using the filter_by_plugin method.
 *
 * @since 3.0.2
 * TODO create a properly namespaced alias for this
 */
class Toolset_Potential_Association_Query_Filter_Posts_Status
	implements Toolset_Potential_Association_Query_Filter_Interface {

	/**
	 * Maybe filter the list of available posts to connect to a given post by their status.
	 *
	 * Free method for individual Toolset plugins to subclass and implement.
	 *
	 * @param string|string[] $post_status
	 * @return string|string[]
	 */
	protected function filter_by_plugin( $post_status ) {
		return $post_status;
	}

	/**
	 * Maybe filter the list of available posts to connect to a given post by their status.
	 *
	 * Decides whether a filter by post status needs to be set by cascading a series of filters:
	 * - toolset_force_post_status_related_post
	 * - filters in subclasses
	 *
	 * Those filters should return either a single post status or array of statuses.
	 *
	 * Note that individual Toolset plugins can include their own filters by subclassing this one
	 * and including just a filter_by_plugin method containing their API filters chain.
	 *
	 * @param array $query_arguments The potential association query arguments.
	 * @return array
	 */
	public function filter( array $query_arguments ) {
		$post_status = 'publish';

		$post_status = $this->filter_by_plugin( $post_status );

		/**
		 * Force a post author on all Toolset interfaces to set a related post.
		 *
		 * @since m2m
		 */
		$post_status = apply_filters(
			'toolset_force_post_status_related_post',
			$post_status
		);

		if ( false === $post_status ) {
			return $query_arguments;
		}

		$query_arguments['post_status'] = $post_status;

		return $query_arguments;
	}

}
