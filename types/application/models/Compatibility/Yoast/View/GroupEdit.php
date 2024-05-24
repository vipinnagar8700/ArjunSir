<?php

namespace OTGS\Toolset\Types\Compatibility\Yoast\View;

use Exception;
use OTGS\Toolset\Types\Compatibility\Yoast\Field\AField;
use OTGS\Toolset\Types\Compatibility\Yoast\Field\Factory;
use OTGS\Toolset\Types\Compatibility\Yoast\Field\IField;

/**
 * @since 3.1
 */
class GroupEdit {

	const INPUT_NAME_YOAST_DISPLAY_AS = 'extra-yoast-display-as';

	/** @var IField[] */
	private $field_types = array();

	/** @var Factory */
	private $field_factory;


	/**
	 * @param Factory $factory
	 */
	public function __construct( Factory $factory ) {
		$this->field_factory = $factory;
	}


	/**
	 * Yoast Settings to Field GUI
	 *
	 * @filter wpcf_form_field
	 *
	 * @param array $form
	 * @param mixed $data
	 * @param string $field_type
	 *
	 * @return array
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function addYoastSettingsToFieldGUI( $form, $data, $field_type = '' ) {
		try {
			$field = $this->getFieldType( $field_type );

			$form[ self::INPUT_NAME_YOAST_DISPLAY_AS ] = $this->enlimboYoastDisplayAs( $field );
		} catch ( Exception $e ) {
			// do nothing on exception
		}

		return $form;
	}


	/**
	 * Store Field Type
	 *
	 * @param string $field_type
	 *
	 * @return IField
	 */
	private function getFieldType( $field_type ) {
		if ( ! isset( $this->field_types[ $field_type ] ) ) {
			$this->field_types[ $field_type ] = $this->field_factory->createField( $field_type );
		}

		return $this->field_types[ $field_type ];
	}


	/**
	 * @param IField $field
	 *
	 * @return array
	 */
	private function enlimboYoastDisplayAs( IField $field ) {
		$options = array(
			array(
				'#name' => 'Do not use for YOAST Analysis',
				'#value' => AField::OPTION_DO_NOT_USE,
				'#title' => 'Do not use for YOAST Analysis',
			),
		);

		foreach ( $field->getDisplayAsOptions() as $name => $display ) {
			$options[] = array(
				'#name' => $display,
				'#value' => $name,
				'#title' => $display,
			);
		}

		$tooltip = __( 'How should YOAST Analysis treat the field input?', 'wpcf' );

		return array(
			'#type' => 'select',
			'#name' => self::INPUT_NAME_YOAST_DISPLAY_AS,
			'#inline' => true,
			'#label' => 'YOAST Analysis',
			'#description' => '',
			'#options' => $options,
			'#attributes' => array(
				'tooltip' => $tooltip,
			),
			'#default_value' => $field->getDefaultDisplayAs(),
			'#pattern' => '<tr class="wpcf-border-top"><td><LABEL></td>' .
				'<td><ERROR><BEFORE><ELEMENT><AFTER></td></tr>',
		);
	}
}
