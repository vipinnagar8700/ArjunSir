<?php

/**
 * @since 2.3
 */
class Types_Media_Mapper implements Types_Media_Mapper_Interface {

	/**
	 * @var Types_Wordpress_Media_Interface
	 */
	private $attachment;


	/**
	 * Types_Media_Mapper constructor.
	 *
	 * @param Types_Wordpress_Media_Interface $attachment
	 */
	public function __construct( Types_Wordpress_Media_Interface $attachment ) {
		$this->attachment = $attachment;
	}


	/**
	 * @param string $url
	 *
	 * @return false|Types_Media
	 */
	public function find_by_url( $url ) {
		if ( ! $id = $this->attachment->get_attachment_id_by_url( $url ) ) {
			return new Types_Media( array( 'url' => $url ) );
		}

		return $this->find_by_id( $id );
	}


	/**
	 * @param int $id
	 *
	 * @return false|Types_Media
	 */
	public function find_by_id( $id ) {
		if ( ! $attachment = $this->attachment->get_attachment_by_id( $id ) ) {
			return false;
		}

		if ( ! $url = wp_get_attachment_url( $id ) ) {
			return false;
		}

		return new Types_Media(
			array(
				'id' => $id,
				'url' => $url,
				'title' => $attachment['post_title'],
				'description' => $attachment['post_content'],
				'caption' => $attachment['post_excerpt'],
				'alt' => $attachment['alt'],
			)
		);
	}


	/**
	 * Not used yet.
	 *
	 * @param Types_Interface_Media $media
	 */
	public function store( Types_Interface_Media $media ) {
		// TODO implement when it's used.
	}
}
