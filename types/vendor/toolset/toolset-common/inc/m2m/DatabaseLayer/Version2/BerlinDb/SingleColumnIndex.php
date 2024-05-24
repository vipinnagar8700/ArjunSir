<?php

namespace OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\berlindb;

/**
 * Represents a table index which is based on a single column.
 *
 * @since 4.0
 */
class SingleColumnIndex extends Index {

	/**
	 * SingleColumnIndex constructor.
	 *
	 * @param Column $column
	 * @param bool $is_primary
	 */
	public function __construct( Column $column, $is_primary = false ) {
		parent::__construct( $column->get_name(), [ $column ], $is_primary );
	}

}
