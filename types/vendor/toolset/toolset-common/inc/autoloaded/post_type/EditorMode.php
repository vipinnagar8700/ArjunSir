<?php

namespace OTGS\Toolset\Common\PostType;

/**
 * Pseudo-enum class for the editor mode of a post type or a particular post.
 *
 * @since Types 3.2.2
 */
class EditorMode {

	const CLASSIC = 'classic';
	const BLOCK = 'block';

	// Decide per post. This is obviously not valid value for a single post.
	const PER_POST = 'per_post';


	/**
	 * Check that a value belongs to the enum.
	 *
	 * @param string $value
	 * @return bool
	 */
	public static function is_valid( $value ) {
		return in_array( $value, array( self::CLASSIC, self::BLOCK, self::PER_POST ) );
	}
}
