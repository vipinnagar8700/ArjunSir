<?php

namespace OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\berlindb;

/**
 * Column set up to be used as a primary key.
 *
 * @since 4.0
 */
class PrimaryKeyColumn extends Column {

	const COLUMN_NAME = 'id';

	public function __construct() {
		parent::__construct(
			DataType::BIGINT,
			self::COLUMN_NAME,
			20,
			null,
			null,
			true,
			true,
			false,
			false,
			'AUTO_INCREMENT',
			null,
			function ( $value ) {
				return is_int( $value ) && $value > 0;
			}
		);
	}

}
