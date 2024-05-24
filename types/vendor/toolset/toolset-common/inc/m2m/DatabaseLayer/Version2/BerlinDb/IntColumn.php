<?php

namespace OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\berlindb;

/**
 * Integer database column.
 *
 * @since 4.0
 */
class IntColumn extends Column {

	public function __construct( $name, $is_unsigned = false, $length = 10, $allow_null = false, $default = 0 ) {
		parent::__construct(
			DataType::INT,
			$name,
			$length,
			null,
			null,
			$is_unsigned,
			false,
			$allow_null,
			$default
		);
	}
}
