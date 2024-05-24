<?php
/**
 * Base Custom Database Table Schema Class.
 *
 * @package     Database
 * @subpackage  Schema
 * @copyright   Copyright (c) 2019
 * @license     https://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0.0
 */

namespace OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\berlindb;


/**
 * A base database table schema class, which houses the collection of columns
 * that a table is made out of.
 *
 * This class is intended to be extended for each unique database table,
 * including global tables for multisite, and users tables.
 *
 * @since 4.0
 */
class TableSchema {

	/** @var Index */
	private $primary_key = null;


	/** @var Column[] */
	private $columns = [];


	/** @var Index[] */
	private $indexes = [];


	/**
	 * Invoke new column objects based on array of column data.
	 *
	 * @param Column[] $columns
	 * @param Index[] $indexes
	 */
	public function __construct( array $columns, array $indexes ) {
		foreach( $columns as $column ) {
			if ( ! $column instanceof Column ) {
				throw new \InvalidArgumentException( 'Invalid object passed as a column definition.' );
			}
		}
		$this->columns = $columns;

		foreach( $indexes as $index ) {
			if( ! $index instanceof Index ) {
				throw new \InvalidArgumentException( 'Invalid object passed as an index definition.' );
			}
			if( $index->is_primary() ) {
				if( null !== $this->primary_key ) {
					throw new \InvalidArgumentException( 'Attempting to set multiple primary keys.' );
				}
				$this->primary_key = $index;
			}
		}
		$this->indexes = $indexes;
	}


	/**
	 * Return the schema in string form.
	 *
	 * @return string Calls get_create_string() on every column.
	 */
	public function to_string() {
		if ( empty( $this->columns ) ) {
			return '';
		}

		$column_definitions = array_map( function( Column $column ) {
			return $column->to_string();
		}, $this->columns );

		$index_definitions = array_map( function( Index $index ) {
			return $index->to_string();
		}, $this->indexes );

		$rows = array_merge( $column_definitions, $index_definitions );

		return implode( ', ' . PHP_EOL, $rows ) . PHP_EOL;
	}
}
