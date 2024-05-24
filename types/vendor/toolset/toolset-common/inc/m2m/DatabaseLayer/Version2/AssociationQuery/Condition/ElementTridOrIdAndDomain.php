<?php

namespace OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\AssociationQuery\Condition;

use InvalidArgumentException;
use OTGS\Toolset\Common\Relationships\API\AssociationQueryCondition;
use OTGS\Toolset\Common\Relationships\API\RelationshipRole;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\AssociationQuery\ConditionFactory;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\AssociationQuery\ElementSelector\ElementSelectorProvider;
use Toolset_Utils;

/**
 * Condition to query elements by TRID if possible, and otherwise use the ElementIdAndDomain condition.
 *
 * @since 4.0
 */
class ElementTridOrIdAndDomain extends AbstractCondition {

	/** @var AssociationQueryCondition */
	private $inner_condition;

	/** @var int */
	private $trid;

	/** @var ElementSelectorProvider */
	private $element_selector_provider;

	/** @var ConditionFactory */
	private $condition_factory;

	/** @var int */
	private $element_id;

	/** @var string */
	private $domain;

	/** @var RelationshipRole */
	private $for_role;

	/** @var string */
	private $element_identification_to_query_by;

	/** @var bool */
	private $translate_provided_id;


	/**
	 * ElementTridOrIdAndDomain constructor.
	 *
	 * @param int $trid
	 * @param int $element_id
	 * @param string $domain
	 * @param RelationshipRole $for_role
	 * @param ElementSelectorProvider $element_selector_provider
	 * @param string $element_identification_to_query_by
	 * @param bool $translate_provided_id
	 * @param ConditionFactory $condition_factory
	 */
	public function __construct(
		$trid,
		$element_id,
		$domain,
		RelationshipRole $for_role,
		ElementSelectorProvider $element_selector_provider,
		$element_identification_to_query_by,
		$translate_provided_id,
		ConditionFactory $condition_factory
	) {
		if ( ! Toolset_Utils::is_nonnegative_integer( $trid ) ) {
			throw new InvalidArgumentException( 'Invalid TRID.' );
		}

		$this->trid = (int) $trid;
		$this->element_id = $element_id;
		$this->domain = $domain;
		$this->for_role = $for_role;
		$this->element_selector_provider = $element_selector_provider;
		$this->element_identification_to_query_by = $element_identification_to_query_by;
		$this->translate_provided_id = $translate_provided_id;
		$this->condition_factory = $condition_factory;
	}


	/**
	 * Return the condition by element ID and domain, while creating it if necessary.
	 *
	 * @return AssociationQueryCondition
	 */
	private function get_inner_condition() {
		if ( null === $this->inner_condition ) {
			$this->inner_condition = $this->condition_factory->element_id_and_domain(
				$this->element_id,
				$this->domain,
				$this->for_role,
				$this->element_identification_to_query_by,
				$this->translate_provided_id
			);
		}

		return $this->inner_condition;
	}


	/**
	 * Decide whether querying by TRID should be used.
	 *
	 * @return bool
	 */
	private function use_trid() {
		// We need to have the TRID at hand and it needs to be supported by the element selector.
		if ( ! $this->trid ) {
			return false;
		}

		return null !== $this->element_selector_provider->get_selector()->get_element_trid_value(
			$this->for_role
		);
	}


	/**
	 * @inheritDoc
	 */
	public function get_where_clause() {
		if ( ! $this->use_trid() ) {
			return $this->get_inner_condition()->get_where_clause();
		}

		return sprintf(
			' %s = %d ', $this->element_selector_provider->get_selector()->get_element_trid_value( $this->for_role ),
			$this->trid
		);
	}


	/**
	 * @inheritDoc
	 */
	public function get_join_clause() {
		if ( ! $this->use_trid() ) {
			return $this->get_inner_condition()->get_join_clause();
		}

		return '';
	}


}
