<?php

/**
 * @since 2.3
 */
class Types_Site_Domain {

	/**
	 * @var string
	 */
	private $host;

	/**
	 * @var array
	 */
	private $contains = array();


	/**
	 * Types_Site_Domain constructor.
	 *
	 * @param string $domain
	 */
	public function __construct( $domain ) {
		$host = $this->get_host_of_string( $domain );
		if ( ! $host ) {
			throw new InvalidArgumentException( '$domain "' . $domain . '" does not contain a valid domain.' );
		}

		$this->host = $host;
	}


	/**
	 * @param string $domain
	 *
	 * @return bool|string
	 */
	private function get_host_of_string( $domain ) {
		if ( preg_match( '/\/([a-z0-9äöü][a-z0-9äöü\-.]{0,252})/i', $domain, $matches ) ) {
			// this is not for verifaction, this is just for getting any kind of host part of an url.
			// to also support any local urls like "http://localhost/anything" or "https://127.0.0.1/anything"
			return $matches[1];
		}

		return false;
	}


	/**
	 * @param Types_Interface_Url $resource
	 *
	 * @return bool
	 */
	public function contains( Types_Interface_Url $resource ) {
		$ressource_url = $resource->get_url();

		if ( array_key_exists( $ressource_url, $this->contains ) ) {
			return $this->contains[ $ressource_url ];
		}

		$ressource_url_parts = parse_url( $ressource_url );

		if ( ! array_key_exists( 'host', $ressource_url_parts ) ) {
			// relative path (domain independent)
			return $this->contains[ $ressource_url ] = true;
		}

		if ( $ressource_url_parts['host'] === $this->host ) {
			return $this->contains[ $ressource_url ] = true;
		}

		// not the same domain
		return $this->contains[ $ressource_url ] = false;
	}
}
