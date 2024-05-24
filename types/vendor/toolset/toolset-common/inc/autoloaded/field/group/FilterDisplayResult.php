<?php

namespace OTGS\Toolset\Common\Field\Group;

/**
 * Holds a result of a field group display filter.
 *
 * @see Toolset_Field_Group_Post_Factory::get_groups_for_element()
 * @since Types 3.3
 */
class FilterDisplayResult {


	// Possible output values of field group filters,
	const MATCH = 'match';

	const FAIL = 'fail';

	const INDIFFERENT = 'indifferent';


	/** @var string */
	private $value;


	/** @var bool */
	private $requires_browser_evaluation;


	/** @var bool */
	private $requires_page_refresh_after_saving;


	/**
	 * FilterDisplayResult constructor.
	 *
	 * @param string $value Filter result.
	 * @param bool $requires_browser_evaluation Indicates whether this filter needs to be further evaluated in the
	 *     browser.
	 * @param bool $requires_page_refresh_after_saving Indicates whether this filter requires a page refresh after
	 *        saving the post in order to re-evaluate the field group visibility.
	 *
	 * @throws \InvalidArgumentException
	 */
	public function __construct( $value, $requires_browser_evaluation, $requires_page_refresh_after_saving ) {
		if (
			! in_array( $value, array( self::MATCH, self::FAIL, self::INDIFFERENT ), true )
			|| ! is_bool( $requires_browser_evaluation )
			|| ! is_bool( $requires_page_refresh_after_saving )
		) {
			throw new \InvalidArgumentException();
		}

		$this->value = $value;
		$this->requires_browser_evaluation = $requires_browser_evaluation;
		$this->requires_page_refresh_after_saving = $requires_page_refresh_after_saving;
	}


	/**
	 * Filter result: MATCH, FAIL or INDIFFERENT.
	 *
	 * @return string
	 */
	public function get_value() {
		return $this->value;
	}


	/**
	 * Indicates whether this filter needs to be further evaluated in the browser.
	 *
	 * @return bool
	 */
	public function requires_browser_evaluation() {
		return $this->requires_browser_evaluation;
	}


	/**
	 * Indicates whether this filter requires a page refresh after saving the post in order to re-evaluate
	 * the field group visibility.
	 *
	 * @return bool
	 */
	public function requires_page_refresh_after_saving() {
		return $this->requires_page_refresh_after_saving;
	}
}
