<?php

namespace OTGS\Toolset\Common\Relationships\DatabaseLayer;

/**
 * Generates unique alias values for a given table name.
 *
 * Works under the assumption that there are no tables with similar names different only by a numeric suffix "_$n". :)
 * The generated values are unique within one class instance.
 *
 * @since 2.5.4
 */
class UniqueTableAlias {

	/**
	 * @var string[] Maps a table name to last used numeric suffix.
	 */
	private $last_ids = array();


	/**
	 * Generate a new unique value
	 *
	 * @param string $table_name
	 * @param bool $always_suffix Add a suffix even if using the table alias for the first time.
	 * @param string $additional_suffix Suffix that will be always added at the very end of the alias.
	 *     Doesn't guarantee uniqueness, it just can be used to describe the alias semantically.
	 *
	 * @return string
	 */
	public function generate( $table_name, $always_suffix = false, $additional_suffix = '' ) {

		if( ! array_key_exists( $table_name, $this->last_ids ) ) {
			$this->last_ids[ $table_name ] = 1;
		}

		$last_id = $this->last_ids[ $table_name ];
		$this->last_ids[ $table_name ]++;

		if( $last_id === 0 && ! $always_suffix ) {
			return $table_name;
		}

		if( ! empty( $additional_suffix ) ) {
			$additional_suffix = '_' . $additional_suffix;
		}

		return "{$table_name}_{$last_id}{$additional_suffix}";
	}

}


// See the inc/autoloaded/legacy_aliases directory for further info.
/** @noinspection PhpIgnoredClassAliasDeclaration */
class_alias( UniqueTableAlias::class, 'Toolset_Relationship_Database_Unique_Table_Alias' );
