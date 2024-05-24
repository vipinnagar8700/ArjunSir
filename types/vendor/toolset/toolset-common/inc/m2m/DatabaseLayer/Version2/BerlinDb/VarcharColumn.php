<?php

namespace OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\berlindb;

/**
 * Column of a varchar type.
 *
 * @since 4.0
 */
class VarcharColumn extends Column {


	/**
	 * VarcharColumn constructor.
	 *
	 * @param string $name
	 * @param int $length
	 * @param bool $allow_null
	 * @param string $default
	 */
	public function __construct( $name, $length, $allow_null = false, $default = '' ) {
		parent::__construct(
			DataType::VARCHAR,
			$name,
			$length,
			null,
			null,
			false,
			false,
			$allow_null,
			$default
		);
	}

}
