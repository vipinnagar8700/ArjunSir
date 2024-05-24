<?php

use OTGS\Toolset\Common\Result\ResultInterface;

/**
 * Represents a result of an operation on a relationship definition.
 *
 * Used by Types_Ajax_Handler_Relationships_Action.
 *
 * @since m2m
 */
class Types_Relationship_Operation_Result {


	/** @var ResultInterface */
	private $result;

	/** @var null|Types_Viewmodel_Relationship_Definition */
	private $viewmodel;

	/** @var bool */
	private $is_deleted;

	/** @var null|array */
	private $custom_return_data;


	/**
	 * Types_Relationship_Operation_Result constructor.
	 *
	 * @param Toolset_Result $result The actual result of the operation.
	 * @param Types_Viewmodel_Relationship_Definition|null $updated_definition_viewmodel If a relationship definition was
	 *    updated during the operation, the updated viewmodel should be passed. Null means that no change was done.
	 * @param bool $is_deleted Denotes that the relationship was deleted during the operation.
	 * @param null|array $custom_return_data Custom data array to be returned by the AJAX call instead of the standard
	 *    results per relationship definition. It will work properly only if a single relationship definition is
	 *    processed during the call.
	 */
	public function __construct(
		ResultInterface $result,
		Types_Viewmodel_Relationship_Definition $updated_definition_viewmodel = null,
		$is_deleted = false,
		$custom_return_data = null
	) {
		$this->result = $result;
		$this->viewmodel = $updated_definition_viewmodel;
		$this->is_deleted = (bool) $is_deleted;
		$this->custom_return_data = $custom_return_data;
	}


	/**
	 * @return ResultInterface
	 */
	public function get_result() {
		return $this->result;
	}


	/**
	 * @return bool
	 */
	public function has_updated_definition() {
		return ( null !== $this->viewmodel );
	}


	/**
	 * Was the relationship definition deleted during the operation?
	 *
	 * @return bool
	 */
	public function is_deleted_definition() {
		return $this->is_deleted;
	}


	/**
	 * If the operation has returned an updated viewmodel of the relationship definition,
	 * it can be obtained here. Otherwise, the method will return null.
	 *
	 * @return Types_Viewmodel_Relationship_Definition|null
	 * @since m2m
	 */
	public function get_definition_viewmodel() {
		return $this->viewmodel;
	}


	public function has_custom_return_data() {
		return ( null !== $this->custom_return_data );
	}


	public function get_custom_return_data() {
		return $this->custom_return_data;
	}
}
