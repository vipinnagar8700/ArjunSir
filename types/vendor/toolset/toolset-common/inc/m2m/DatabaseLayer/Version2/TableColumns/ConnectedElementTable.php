<?php


namespace OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\TableColumns;


use OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\berlindb\PrimaryKeyColumn;

/**
 * Holds names of columns of the connected element table.
 *
 * This is the only place within the DatabaseLayer\Version2 namespace where these values may be hardcoded.
 *
 * @since 4.0
 */
final class ConnectedElementTable {

	const CURRENT_VERSION = 1;

	const ID = PrimaryKeyColumn::COLUMN_NAME;

	const GROUP_ID = 'group_id';

	const ELEMENT_ID = 'element_id';

	const DOMAIN = 'domain';

	const WPML_TRID = 'wpml_trid';

	const LANG_CODE = 'lang_code';
}
