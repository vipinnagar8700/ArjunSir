<?php

namespace OTGS\Toolset\Types\PostType\Part;

/**
 * Interface IPostTypePart
 *
 * Each CPT Part requires to re-apply it's modified content to the CPT Array (respecting the WP format).
 *
 * @package OTGS\Toolset\Types\PostType\Part
 *
 * @since 3.2
 */
interface IPostTypePart {

	/**
	 * The slug of the CPT the Part belongs to.
	 *
	 * @return mixed
	 */
	public function get_cpt_slug();


	/**
	 * Let the part class apply it's data to a cpt array.
	 * The manipulated cpt array will be returned.
	 *
	 * @param array $cpt_array
	 *
	 * @return array
	 */
	public function apply_to_cpt_array( $cpt_array );
}