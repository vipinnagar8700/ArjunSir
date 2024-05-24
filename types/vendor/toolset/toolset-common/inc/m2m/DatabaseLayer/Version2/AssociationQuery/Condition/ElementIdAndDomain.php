<?php

namespace OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\AssociationQuery\Condition;

use InvalidArgumentException;
use OTGS\Toolset\Common\Relationships\API\ElementIdentification;
use OTGS\Toolset\Common\Relationships\API\RelationshipRole;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\AssociationQuery\ElementSelector\ElementSelectorProvider;
use OTGS\Toolset\Common\WPML\WpmlService;
use Toolset_Element_Domain;
use Toolset_Relationship_Role_Intermediary;
use Toolset_Utils;

/**
 * Condition to query associations by a particular element involved in a particular role.
 */
class ElementIdAndDomain extends AbstractCondition {


	/** @var int */
	private $element_id;


	/** @var RelationshipRole */
	private $for_role;


	/** @var ElementSelectorProvider */
	private $element_selector_provider;


	/** @var bool */
	private $translate_provided_id;


	/** @var string */
	private $element_identification_to_query_by;


	/** @var string */
	private $domain;


	/** @var WpmlService */
	private $wpml_service;


	/**
	 * @param int $element_id
	 * @param string $domain
	 * @param RelationshipRole $for_role
	 * @param ElementSelectorProvider $element_selector_provider
	 * @param string $element_identification_to_query_by
	 * @param bool $translate_provided_id
	 * @param WpmlService $wpml_service
	 */
	public function __construct(
		$element_id,
		$domain,
		RelationshipRole $for_role,
		ElementSelectorProvider $element_selector_provider,
		$element_identification_to_query_by,
		$translate_provided_id,
		WpmlService $wpml_service
	) {
		if (
			! Toolset_Utils::is_nonnegative_integer( $element_id )
			|| ! in_array( $domain, Toolset_Element_Domain::all(), true )
		) {
			throw new InvalidArgumentException( 'Invalid element ID or domain.' );
		}

		if (
			$for_role instanceof Toolset_Relationship_Role_Intermediary
			&& Toolset_Element_Domain::POSTS !== $domain
		) {
			throw new InvalidArgumentException( 'Querying by an intermediary post with a wrong element domain.' );
		}

		if ( ! in_array( $element_identification_to_query_by, ElementIdentification::all(), true ) ) {
			throw new InvalidArgumentException( 'Invalid element identification.' );
		}

		$this->element_id = (int) $element_id;
		$this->domain = $domain;
		$this->for_role = $for_role;
		$this->element_selector_provider = $element_selector_provider;
		$this->translate_provided_id = (bool) $translate_provided_id;
		$this->element_identification_to_query_by = $element_identification_to_query_by;
		$this->wpml_service = $wpml_service;
	}


	/**
	 * Get a part of the WHERE clause that applies the condition.
	 *
	 * @return string Valid part of a MySQL query, so that it can be
	 *     used in WHERE ( $condition1 ) AND ( $condition2 ) AND ( $condition3 ) ...
	 */
	public function get_where_clause() {
		$column_name = $this->element_selector_provider
			->get_selector()
			->get_element_id_value(
				$this->for_role, $this->element_identification_to_query_by
			);

		return sprintf(
			' %s %s %d ', $column_name, $this->get_operator(), $this->get_element_id_to_query()
		);
	}


	private function get_element_id_to_query() {
		if (
			Toolset_Element_Domain::POSTS === $this->domain
			&& $this->translate_provided_id
			&& $this->wpml_service->is_wpml_active_and_configured()
		) {
			if ( ElementIdentification::ORIGINAL_LANGUAGE === $this->element_identification_to_query_by ) {
				$original_id = $this->wpml_service->get_original_post_id( $this->element_id );
				// Fall back to default in case the element is not translatable or WPML doesn't provide the original.
				// ID for whatever reason.
				return $original_id ? : $this->element_id;
			}

			return apply_filters( 'wpml_object_id', $this->element_id, 'any', true );
		}

		return $this->element_id;
	}


	protected function get_operator() {
		return '=';
	}


	/**
	 * @return int ID of the element to query as provided in the constructor. It may be different from the element
	 *     to actually query by (e.g. due to translation).
	 */
	public function get_element_id() {
		return $this->element_id;
	}


	/**
	 * @return string Element domain to query by.
	 */
	public function get_domain() {
		return $this->domain;
	}


	/**
	 * @return RelationshipRole Role to query by.
	 */
	public function get_role() {
		return $this->for_role;
	}

}
