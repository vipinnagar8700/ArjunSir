<?php

namespace OTGS\Toolset\Common\Relationships\DatabaseLayer\Version1;

use InvalidArgumentException;
use IToolset_Relationship_Role;
use Toolset_Element_Domain;
use Toolset_Relationship_Role_Intermediary;

/**
 * Condition to query by a set of elements in a selected role.
 *
 * If any of the provided IDs match, the row is accepted.
 *
 * @since 3.0.3
 */
class Toolset_Association_Query_Condition_Multiple_Elements extends Toolset_Association_Query_Condition {


	/** @var int[] */
	private $element_ids;


	/** @var IToolset_Relationship_Role */
	private $for_role;


	/** @var Toolset_Association_Query_Element_Selector_Provider */
	private $element_selector_provider;


	/** @var bool */
	private $translate_provided_ids;


	/** @var bool */
	private $query_original_element;


	/** @var string */
	private $domain;


	/**
	 * OTGS\Toolset\Common\Relationships\DatabaseLayer\Version1\Toolset_Association_Query_Condition_Element_Id
	 * constructor.
	 *
	 * @param int[] $element_ids
	 * @param string $domain
	 * @param \OTGS\Toolset\Common\Relationships\API\RelationshipRole $for_role
	 * @param Toolset_Association_Query_Element_Selector_Provider $element_selector_provider
	 * @param $query_original_element
	 * @param $translate_provided_ids
	 */
	public function __construct(
		$element_ids,
		$domain,
		\OTGS\Toolset\Common\Relationships\API\RelationshipRole $for_role,
		Toolset_Association_Query_Element_Selector_Provider $element_selector_provider,
		$query_original_element,
		$translate_provided_ids
	) {
		if (
			! is_array( $element_ids )
			|| ! in_array( $domain, Toolset_Element_Domain::all(), true )
		) {
			throw new InvalidArgumentException();
		}

		if (
			$for_role instanceof Toolset_Relationship_Role_Intermediary
			&& Toolset_Element_Domain::POSTS !== $domain
		) {
			throw new InvalidArgumentException( 'Querying by an intermediary post with a wrong element domain.' );
		}

		$this->element_ids = $element_ids;
		$this->domain = $domain;
		$this->for_role = $for_role;
		$this->element_selector_provider = $element_selector_provider;
		$this->translate_provided_ids = (bool) $translate_provided_ids;
		$this->query_original_element = (bool) $query_original_element;
	}


	/**
	 * Get a part of the WHERE clause that applies the condition.
	 *
	 * @return string Valid part of a MySQL query, so that it can be
	 *     used in WHERE ( $condition1 ) AND ( $condition2 ) AND ( $condition3 ) ...
	 */
	public function get_where_clause() {
		$element_ids = $this->get_element_ids();
		if ( empty( $element_ids ) ) {
			return ' 1 = 0 ';
		}

		$column_name = $this->element_selector_provider
			->get_selector()
			->get_element_id_value(
				$this->for_role, ! $this->query_original_element
			);

		return ' ' . $column_name . ' IN  ( ' . implode( ', ', $element_ids ) . ' ) ';
	}

	public function get_element_id_to_query( $element_id ) {
		if ( Toolset_Element_Domain::POSTS === $this->domain && $this->translate_provided_ids ) {
			$element_id = (int) apply_filters( 'wpml_object_id', $element_id, 'any', true );

			return $element_id;
		}

		return (int) $element_id;
	}


	private function get_element_ids() {
		return array_filter(
			array_map( array( $this, 'get_element_id_to_query' ), $this->element_ids ),
			static function( $element_id ) {
				return $element_id > 0;
			}
		);
	}

}
