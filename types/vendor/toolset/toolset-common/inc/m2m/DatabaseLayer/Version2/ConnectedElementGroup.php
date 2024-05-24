<?php

namespace OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2;

/**
 * Represents a group of connected elements.
 *
 * That basically means a row in the connected elements table and all element translations it refers to.
 *
 * @since 4.0
 */
class ConnectedElementGroup {

	/** @var int */
	private $group_id;

	/** @var int[] */
	private $element_ids;

	/** @var int */
	private $directly_stored_id;

	/** @var string */
	private $domain;

	/** @var int */
	private $wpml_trid;


	/**
	 * ConnectedElementGroup constructor.
	 *
	 * @param int $group_id
	 * @param int[] $element_ids
	 * @param string $domain
	 * @param int $directly_stored_id
	 * @param int $wpml_trid
	 */
	public function __construct( $group_id, $element_ids, $domain, $directly_stored_id, $wpml_trid ) {
		$this->group_id = (int) $group_id;
		$this->element_ids = array_map( 'intval', $element_ids );
		$this->domain = $domain;
		$this->directly_stored_id = (int) $directly_stored_id;
		$this->wpml_trid = (int) $wpml_trid;
	}


	/**
	 * @return int ID of the group.
	 */
	public function get_id() {
		return $this->group_id;
	}


	/**
	 * @return string Domain of elements in the group.
	 */
	public function get_domain() {
		return $this->domain;
	}


	/**
	 * @return int[] IDs of all elements in the group.
	 */
	public function get_element_ids() {
		return $this->element_ids;
	}


	/**
	 * @return int ID of the element that is stored directly in the row of the connected elements table.
	 */
	public function get_directly_stored_id() {
		return $this->directly_stored_id;
	}


	/**
	 * @return bool True if there is exactly one element in the group.
	 */
	public function has_last_element() {
		return count( $this->element_ids ) === 1;
	}


	/**
	 * @return int TRID stored with the element group (zero if none).
	 */
	public function get_trid() {
		return $this->wpml_trid;
	}
}
