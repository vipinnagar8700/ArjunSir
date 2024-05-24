<?php


/**
 * Class Types_Media
 *
 * @since 2.3
 */
class Types_Media implements Types_Interface_Media {

	private $id;
	private $url;

	private $title;
	private $caption;
	private $alt;
	private $description;

	/**
	 * Types_Media constructor.
	 *
	 * @param array $data
	 *  'id'
	 *  'url'
	 *  'caption'
	 *  'alt'
	 *  'description'
	 */
	public function __construct( $data ) {
		if( ! isset( $data['url'] ) || ! $this->url = $this->get_url_from_string( $data['url'] ) ) {
			$this->url = '';
		}

		if( isset( $data['id'] ) ) {
			$this->set_id( $data['id'] );
		}
		if( isset( $data['title'] ) ) {
			$this->set_title( $data['title'] );
		}
		if( isset( $data['caption'] ) ) {
			$this->set_caption( $data['caption'] );
		}
		if( isset( $data['alt'] ) ) {
			$this->set_alt( $data['alt'] );
		}
		if( isset( $data['description'] ) ) {
			$this->set_description( $data['description'] );
		}
	}

	private function get_url_from_string( $url ) {
		$url_parts = parse_url( $url );
		if( isset( $url_parts['host'] ) || isset( $url_parts['path'] ) ) {
			return $url;
		}

		return false;
	}

	/**
	 * @return string
	 */
	public function get_url() {
		return $this->url;
	}

	/**
	 * @param int $id
	 */
	public function set_id( $id ) {
		$this->id = (int) $id;
	}

	/**
	 * @return int
	 */
	public function get_id() {
		return $this->id;
	}

	/**
	 * @param mixed $title
	 */
	private function set_title( $title ) {
		$this->title = $title;
	}

	/**
	 * @return string
	 */
	public function get_title() {
		return $this->title;
	}

	/**
	 * @param string $caption
	 */
	private function set_caption( $caption ) {
		$this->caption = $caption;
	}

	/**
	 * @return string
	 */
	public function get_caption() {
		return $this->caption;
	}

	/**
	 * @param mixed $alt
	 */
	private function set_alt( $alt ) {
		$this->alt = $alt;
	}

	/**
	 * @return string
	 */
	public function get_alt() {
		return $this->alt;
	}

	/**
	 * @param string $description
	 */
	private function set_description( $description ) {
		$this->description = $description;
	}

	/**
	 * @return string
	 */
	public function get_description() {
		return $this->description;
	}


}
