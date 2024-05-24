<?php

namespace OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\berlindb;

/**
 * Longtext database column.
 *
 * @since 4.0
 */
class LongtextColumn extends Column {

	public function __construct( $name, $allow_null = true, $default = '' ) {
		parent::__construct(
			DataType::LONGTEXT,
			$name,
			0,
			null,
			null,
			false,
			false,
			$allow_null,
			$default
		);
	}
}
