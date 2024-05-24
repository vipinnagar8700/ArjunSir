<?php

namespace OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\berlindb;

/**
 * Represents a generic database index.
 */
class Index {

	/** @var Column[] */
	private $columns;

	/** @var string */
	private $name;

	/** @var bool */
	private $is_primary;


	/**
	 * Index constructor.
	 *
	 * @param string $name
	 * @param Column[] $columns
	 * @param bool $is_primary
	 */
	public function __construct( $name, array $columns, $is_primary = false ) {
		$this->columns = $columns;
		$this->name = $name;
		$this->is_primary = $is_primary;
	}


	/**
	 * Build the MySQL syntax that can be used in the CREATE TABLE command, for example.
	 *
	 * @return string
	 */
	public function to_string() {
		$keyword = $this->is_primary ? 'PRIMARY KEY' : 'KEY';
		return sprintf(
			"$keyword $this->name (%s)",
			implode(
				', ',
				array_map(
					function( Column $column ) { return $column->get_name(); },
					$this->columns
				)
			)
		);
	}


	/**
	 * @return bool
	 */
	public function is_primary() {
		return $this->is_primary;
	}

}
