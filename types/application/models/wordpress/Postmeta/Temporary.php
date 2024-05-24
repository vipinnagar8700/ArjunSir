<?php

namespace OTGS\Toolset\Types\Wordpress\Postmeta;

/**
 * Class Temporary
 *
 * Adds postmeta and allows to delete the postmeta on the same call = Temporary Postmeta.
 *
 * Example of use: On Wordpress Export we convert our associations to postmeta, the export runs, and after the export
 * file is written we need to remove the previously added postmeta.
 *
 * @since 3.0
 */
class Temporary {

	/**
	 * @var array
	 */
	private $added_data = array();

	/** @var Storage */
	private $storage;


	/**
	 * Temporary constructor.
	 *
	 * @param Storage $storage
	 */
	public function __construct( Storage $storage ) {
		$this->storage = $storage;
	}


	/**
	 * Update post meta
	 *
	 * @param int $id
	 * @param string $key
	 * @param mixed $value
	 */
	public function updatePostMeta( $id, $key, $value ) {
		$previous_value = $this->storage->getPostMeta( $id, $key );

		$this->added_data[ $id . $key ] = array(
			'id' => $id,
			'key' => $key,
		);

		if ( $previous_value !== array() && $previous_value !== '' ) {
			$this->added_data[ $id . $key ]['previous'] = $previous_value;
		}

		$this->storage->updatePostMeta( $id, $key, $value );
	}


	/**
	 * All previous added/changed post meta will be revoked
	 *
	 * @param int|null $id
	 * @param string|null $key
	 */
	public function revokePostMetaChanges( $id = null, $key = null ) {
		// revoke specific
		if ( $id && $key ) {
			$index = $id . $key;
			if ( isset( $this->added_data[ $index ] ) ) {
				unset( $this->added_data[ $index ] );
				$this->storage->deletePostMeta( $id, $key );
			}

			return;
		}

		// revoke all of $id
		if ( $id ) {
			foreach ( $this->added_data as $postmeta ) {
				if ( (int) $postmeta['id'] === (int) $id ) {
					$this->revokeSingleChange( $postmeta );
				}
			}

			return;
		}

		// revoke all
		foreach ( $this->added_data as $postmeta ) {
			$this->revokeSingleChange( $postmeta );
		}
	}


	/**
	 * @param array $postmeta
	 */
	private function revokeSingleChange( $postmeta ) {

		$array_key = $postmeta['id'] . $postmeta['key'];

		if ( ! isset( $this->added_data[ $array_key ] ) ) {
			return;
		}

		if ( isset( $postmeta['previous'] ) ) {
			// restore previous value
			$this->storage->updatePostMeta( $postmeta['id'], $postmeta['key'], $postmeta['previous'] );
		} else {
			// delete temporary post meta
			$this->storage->deletePostMeta( $postmeta['id'], $postmeta['key'] );
		}

		unset( $this->added_data[ $array_key ] );
	}
}
