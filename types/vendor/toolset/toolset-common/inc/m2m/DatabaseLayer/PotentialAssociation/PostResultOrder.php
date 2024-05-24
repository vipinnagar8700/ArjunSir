<?php

namespace OTGS\Toolset\Common\Relationships\DatabaseLayer\PotentialAssociation;

/**
 * A WP_Query adjustment class that make sure that if a post search string is an exact match for particular posts,
 * these posts will be ordered as first in query results.
 *
 * @since Types 3.1.3
 */
class PostResultOrder extends WpQueryAdjustment {

	/**
	 * Determine whether the WP_Query should be augmented.
	 *
	 * @return bool
	 */
	protected function is_actionable() {
		return true;
	}


	/**
	 * Prepend an orderby clause that gives absolute priority to exact matches of post_title and the search string.
	 *
	 * @param $orderby
	 * @param \WP_Query $wp_query
	 *
	 * @return string
	 */
	public function add_orderby_clauses( $orderby, \WP_Query $wp_query ) {
		$search_string = $wp_query->get( 's', '' );
		if ( ! empty( $search_string ) ) {
			$orderby = $this->wpdb->prepare(
				"
				CASE
					WHEN ( {$this->wpdb->posts}.post_title = %s ) THEN 0
					ELSE 1
				END ASC, ",
				$search_string
			) . $orderby;
		}

		return $orderby;
	}


}
