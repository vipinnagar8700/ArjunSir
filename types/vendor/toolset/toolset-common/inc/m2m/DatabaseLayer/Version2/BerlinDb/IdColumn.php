<?php

namespace OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\berlindb;

/**
 * Column for IDs from WordPress (BIGINT(20)).
 *
 * @since 4.0
 */
class IdColumn extends Column {

	public function __construct( $name, $allow_null = false, $default = false ) {
		parent::__construct(
			DataType::BIGINT,
			$name,
			20,
			null,
			null,
			true,
			false,
			$allow_null,
			$default,
			'',
			false,
			function( $value ) { return null === $value || ( is_int( $value ) && $value > 0 ); }
		);
	}

}
