<?php


namespace OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\TableColumns;


use OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\berlindb\PrimaryKeyColumn;

/**
 * Holds names of columns of the type set table.
 *
 * This is the only place within the DatabaseLayer\Version2 namespace where these values may be hardcoded.
 *
 * @since 4.0
 */
final class TypeSetTable {

	const CURRENT_VERSION = 1;

	const ID = PrimaryKeyColumn::COLUMN_NAME;

	const SET_ID = 'set_id';

	const TYPE = 'type';
}
