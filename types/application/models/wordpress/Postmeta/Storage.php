<?php

namespace OTGS\Toolset\Types\Wordpress\Postmeta;

use wpdb;

/**
 * Gives some extra functioniality regarding loading/manipulating postmeta.
 *
 * @since 3.0
 */
class Storage {

	/** @var wpdb */
	private $wpdb;


	/**
	 * Storage constructor.
	 *
	 * @param wpdb $wpdb
	 */
	public function __construct( wpdb $wpdb ) {
		$this->wpdb = $wpdb;
	}


	/**
	 * Checking if a postmeta exists.
	 *
	 * @param string $meta_key supports wildcards
	 * @param int|null $post_id search for a specific post
	 *
	 * @return bool
	 */
	public function postMetaExistsByKey( $meta_key, $post_id = null ) {
		$wpdb = $this->wpdb;

		$check_for_post = $post_id
			? ' AND post_id = ' . (int) $post_id
			: '';

		$is_available = $wpdb->get_var(
			"SELECT meta_id
			FROM $wpdb->postmeta
			WHERE meta_key LIKE '$meta_key'" . $check_for_post . "
			LIMIT 1"
		);

		// return true|false
		return (bool) $is_available;
	}


	/**
	 * Deletes all empty postmeta by $metakey
	 *
	 * @param string $meta_key supports wildcards
	 * @param null|int $post_id set to delete only for a specific post
	 *
	 * @return int Count of deleted rows
	 */
	public function deleteEmptyPostMetaByKey( $meta_key, $post_id = null ) {
		$wpdb = $this->wpdb;

		$check_for_post = $post_id
			? ' AND post_id = ' . (int) $post_id
			: '';

		// delete all rows
		$meta_to_delete = $wpdb->get_results( "
			SELECT post_id, meta_key
			FROM $wpdb->postmeta
			WHERE meta_key LIKE '" . esc_sql( $meta_key ) . "'
			AND !(meta_value > '')" . $check_for_post, ARRAY_A );

		if ( ! empty( $meta_to_delete ) ) {
			foreach ( $meta_to_delete as $meta ) {
				delete_post_meta( $meta['post_id'], $meta['meta_key'] );
			}
		}

		// return count of delete rows
		return $wpdb->rows_affected;
	}


	/**
	 * Returns limited count of entries by $meta key
	 *
	 * @param string $meta_key supports wildcards
	 * @param null|int $limit Limit associations to import
	 * @param null|int $offset Offset for assoociations to import
	 *
	 * @return array [ [post_id, meta_key, meta_value ], ... ]
	 */
	public function getLimitedPostMetaByKey( $meta_key, $limit = null, $offset = null ) {
		$wpdb = $this->wpdb;

		$limit = $limit !== null
			? ' LIMIT ' . (int) $limit
			: '';

		$offset = ! empty( $limit ) && $offset !== null
			? ' OFFSET ' . (int) $offset
			: '';

		// query returns count of affected rows (in this case 1 or 0)
		$meta = $wpdb->get_results( "
			SELECT post_id, meta_key, meta_value
			FROM $wpdb->postmeta
			WHERE meta_key LIKE '" . esc_sql( $meta_key ) . "'
			ORDER BY meta_id " . $limit . $offset, ARRAY_A );

		// make sure array is returned
		return $meta ? : array();
	}


	/**
	 * Returns all entries by $meta key and optionial $post_id
	 *
	 * @param string $meta_key supports wildcards
	 * @param null|int $post_id set to delete only for a specific post
	 *
	 * @return array [ [post_id, meta_key, meta_value ], ... ]
	 */
	public function getAllPostMetaByKey( $meta_key, $post_id = null ) {
		$wpdb = $this->wpdb;

		$check_for_post = $post_id
			? ' AND post_id = ' . (int) $post_id
			: '';

		// query returns count of affected rows (in this case 1 or 0)
		$meta = $wpdb->get_results( "
			SELECT post_id, meta_key, meta_value
			FROM $wpdb->postmeta
			WHERE meta_key LIKE '" . esc_sql( $meta_key ) . "'" . $check_for_post . "
			ORDER BY meta_id ", ARRAY_A );

		// make sure array is returned
		return $meta ? : array();
	}


	/**
	 * Get Post Meta
	 * I wouldn't have created a class for the following 3 "alias" methods,
	 * but it's a nice addition for 0 required hard-coded dependencies and smooth mocking, so why not.
	 *
	 * @param int $post_id
	 * @param string $meta_key
	 *
	 * @return mixed
	 */
	public function getPostMeta( $post_id, $meta_key ) {
		return get_post_meta( $post_id, $meta_key );
	}


	/**
	 * Update post meta
	 *
	 * @param int $post_id
	 * @param string $meta_key
	 * @param mixed $meta_value
	 */
	public function updatePostMeta( $post_id, $meta_key, $meta_value ) {
		update_post_meta( $post_id, $meta_key, esc_attr( $meta_value ) );
	}


	/**
	 * Delete post meta
	 *
	 * @param int $post_id
	 * @param string $meta_key
	 */
	public function deletePostMeta( $post_id, $meta_key ) {
		delete_post_meta( $post_id, $meta_key );
	}


	/**
	 * Removes $string from the $meta_key value.
	 *
	 * Attention: This will only work for postmeta strings
	 *
	 * @param int $post_id
	 * @param string $meta_key
	 * @param string $string
	 * @param string $separator Use this if you have a simple listing as string like $value1,$value2,$value3
	 * @param bool $delete_empty If the value is empty after the $string was removed the postmeta will be deleted
	 */
	public function deleteStringFromPostMeta( $post_id, $meta_key, $string, $separator = null, $delete_empty = true ) {
		if ( ! $meta_value = get_post_meta( $post_id, $meta_key, true ) ) {
			return;
		}

		if ( ! is_string( $meta_value ) ) {
			return;
		}

		if ( is_string( $separator ) ) {
			// explode our meta_value and check for string on values
			$meta_values = explode( $separator, $meta_value );

			foreach ( $meta_values as $key => $value ) {
				if ( $value === $string ) {
					unset( $meta_values[ $key ] );
				}
			}

			$meta_value = implode( $separator, $meta_values );
		} else {
			// simple string check
			$meta_value = str_replace( $string, '', $meta_value );
		}

		if ( $delete_empty && empty( $meta_value ) ) {
			$this->deletePostMeta( $post_id, $meta_key );

			return;
		}

		$this->updatePostMeta( $post_id, $meta_key, $meta_value );
	}
}
