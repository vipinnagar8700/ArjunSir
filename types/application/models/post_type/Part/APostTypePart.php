<?php

namespace OTGS\Toolset\Types\PostType\Part;

use InvalidArgumentException;

/**
 * @since 3.2
 */
abstract class APostTypePart implements IPostTypePart {

	/**
	 * Unique slug of the CPT
	 * @var string
	 */
	private $slug;

	/**
	 * @param string|int|mixed $slug
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( $slug ) {
		if( ! is_string( $slug ) && ! is_int( $slug ) ) {
			throw new InvalidArgumentException( 'slug must be a string or integer' );
		}

		$this->slug = $slug;
	}

	/**
	 * The slug of the CPT the Part belongs to.
	 *
	 * @return string
	 */
	public function get_cpt_slug() {
		return $this->slug;
	}
}
