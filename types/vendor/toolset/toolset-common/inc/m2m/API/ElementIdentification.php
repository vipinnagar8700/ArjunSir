<?php


namespace OTGS\Toolset\Common\Relationships\API;

/**
 * Enum for identifying an element in association query result by its role in the translation group.
 *
 * @since 4.0
 */
final class ElementIdentification {

	/** @var string Default language element, if it exists. */
	const DEFAULT_LANGUAGE = 'default_language';

	/** @var string Current language or default, if it exists. */
	const CURRENT_LANGUAGE_IF_POSSIBLE = 'current_language';

	/** @var string Original language element (it is supposed to always exist). */
	const ORIGINAL_LANGUAGE = 'original_language';


	/**
	 * All acceptable values.
	 *
	 * @return string[]
	 */
	public static function all() {
		return [
			self::DEFAULT_LANGUAGE,
			self::CURRENT_LANGUAGE_IF_POSSIBLE,
			self::ORIGINAL_LANGUAGE,
		];
	}


	/**
	 * Interpret previously used values as this enum.
	 *
	 * True or 1 corresponds to "translate if possible" and false or 0 means "don't translate".
	 *
	 * @param bool|int|string $value
	 *
	 * @return string Valid enum value.
	 */
	public static function parse( $value ) {
		if ( is_bool( $value ) || in_array( (int) $value, [ 0, 1 ], true ) ) {
			return $value ? self::CURRENT_LANGUAGE_IF_POSSIBLE : self::DEFAULT_LANGUAGE;
		}

		if ( ! in_array( $value, self::all(), true ) ) {
			return self::CURRENT_LANGUAGE_IF_POSSIBLE;
		}

		return $value;
	}

}
