<?php
namespace OTGS\Toolset\Common\Field\Group;

/**
 * Holds results of display filters for a single post field group.
 *
 * Intended only for storing groups whose filters pass (since this logic cannot be evaluated here).
 *
 * @since Types 3.3
 */
class GroupDisplayResult {


	/** @var \Toolset_Field_Group */
	private $group;


	/** @var FilterDisplayResult[] */
	private $filter_results = array();


	/** @var bool */
	private $requires_browser_evaluation = false;


	/** @var bool */
	private $requires_page_refresh_after_saving = false;


	/** @var bool|null Note that the default value is important for correct evaluation of display filters. */
	private $is_selected = null;


	/**
	 * GroupDisplayResult constructor.
	 *
	 * @param \Toolset_Field_Group $group
	 */
	public function __construct( \Toolset_Field_Group $group ) {
		$this->group = $group;
	}


	/**
	 * @return \Toolset_Field_Group
	 */
	public function get_group() {
		return $this->group;
	}


	/**
	 * Add another filter result for this group.
	 *
	 * @param FilterDisplayResult $filter_result
	 */
	public function add_filter_result( FilterDisplayResult $filter_result ) {
		$this->filter_results[] = $filter_result;
		$this->requires_browser_evaluation = $this->requires_browser_evaluation || $filter_result->requires_browser_evaluation();
		$this->requires_page_refresh_after_saving = $this->requires_page_refresh_after_saving || $filter_result->requires_page_refresh_after_saving();
	}


	/**
	 * Determine if any of the filters applied on this group requires browser evaluation.
	 *
	 * @return bool
	 */
	public function requires_browser_evaluation() {
		return $this->requires_browser_evaluation;
	}


	/**
	 * Indicates whether any of the filters applied on this group require a page refresh after saving the post
	 * in order to re-evaluate the group visibility.
	 *
	 * @return bool
	 */
	public function requires_page_refresh_after_saving() {
		return $this->requires_page_refresh_after_saving;
	}


	/**
	 * A flag that indicates whether the field group is actually selected to be rendered on the front-end.
	 *
	 * @param null|bool $new_value
	 *
	 * @return null|bool
	 */
	public function is_selected( $new_value = null ) {
		if( null !== $new_value ) {
			$this->is_selected = (bool) $new_value;
		}

		return $this->is_selected;
	}

}
