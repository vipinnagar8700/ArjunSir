<?php

namespace OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\berlindb;

/**
 * Boolean database column implemented as a tinyint.
 *
 * @since 4.0
 */
class BoolColumn extends Column {

	public function __construct( $name, $allow_null = false, $default = 0 ) {
		parent::__construct(
			DataType::TINYINT,
			$name,
			1,
			null,
			null,
			false,
			false,
			$allow_null,
			$default
		);
	}
}
