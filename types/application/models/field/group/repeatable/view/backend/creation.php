<?php

/**
 * Class Types_Field_Group_Repeatable_View_Backend_Creation
 *
 * @since 2.3
 */
class Types_Field_Group_Repeatable_View_Backend_Creation {

	/**
	 * @var Types_Field_Group_Repeatable
	 */
	private $group;

	/**
	 * @var Types_Interface_Template
	 */
	private $template;

	/**
	 * @var string
	 */
	private $fields_rendered = '';

	/**
	 * Types_Field_View constructor.
	 *
	 * @param Types_Field_Group_Repeatable $group
	 * @param Types_Interface_Template $template
	 * @param string $fields_rendered
	 */
	public function __construct( Types_Field_Group_Repeatable $group, Types_Interface_Template $template, $fields_rendered = '' ) {
		$this->group           = $group;
		$this->template        = $template;
		$this->fields_rendered = $fields_rendered;
	}

	/**
	 * Returns a rendered field by using $template_path as template file
	 *
	 * @param string $template_path
	 *
	 * @return string The rendered templated
	 */
	public function render( $template_path = '/field/group/repeatable/backend/creation.twig' ) {
		return $this->template->render(
			$template_path,
			array(
				'group' => $this->group,
				'fields_rendered' => $this->fields_rendered
			)
		);
	}
}
