<?php

namespace OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\berlindb;

/**
 * Represents a primary key of a table.
 *
 * @since 4.0
 */
class PrimaryKey extends SingleColumnIndex {


	/**
	 * PrimaryKey constructor.
	 *
	 * @param Column $column
	 */
	public function __construct( Column $column ) {
		parent::__construct( $column, true );
	}


}
