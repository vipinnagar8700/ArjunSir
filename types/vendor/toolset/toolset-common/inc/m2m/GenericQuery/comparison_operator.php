<?php

/**
 * Pseudo-enum that holds possible comparison operators.
 *
 * Used, for example, on the meta() query condition.
 * Can be further extended.
 *
 * @since 2.6.1
 */
class Toolset_Query_Comparison_Operator {


	// These need to be valid MySQL operators.
	const EQUALS = '=';
	const LIKE = 'LIKE';
	const LTE = '<=';
	const LT = '<';
	const GTE = '>=';
	const GT = '>';


	/**
	 * All accepted values.
	 *
	 * @return string[]
	 */
	public static function all() {
		return array( self::EQUALS, self::LIKE, self::LTE, self::LT, self::GTE, self::GT );
	}

}
