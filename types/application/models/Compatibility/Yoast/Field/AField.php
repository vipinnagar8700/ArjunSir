<?php

namespace OTGS\Toolset\Types\Compatibility\Yoast\Field;

/**
 * Abstract class AField
 *
 * All data in the context of Yoast compatibility
 *
 * @package OTGS\Toolset\Types\Compatibility\Yoast\Field
 *
 * @since 3.1
 */
abstract class AField implements \JsonSerializable, IField {
	const OPTION_DO_NOT_USE = 'do-not-use';

	/** @var string */
	private $slug;

	/** @var string */
	private $input_name;

	/** @var string */
	protected $type;

	/** @var string */
	private $display_as;

	/**
	 * @param string $slug
	 */
	public function setSlug( $slug ) {
		if( ! is_string( $slug ) ) {
			throw new \InvalidArgumentException( '$slug must be a string.' );
		}

		$this->slug = $slug;
	}

	/**
	 * @return string
	 */
	public function getType() {
		return $this->type;
	}

	/**
	 * @param string $input_name
	 */
	public function setInputName( $input_name ) {
		if( ! is_string( $input_name ) ) {
			throw new \InvalidArgumentException( '$input_name must be a string.' );
		}

		$this->input_name = $input_name;
	}

	/**
	 * @param string $display_as
	 */
	public function setDisplayAs( $display_as ) {
		if( ! is_string( $display_as ) ) {
			throw new \InvalidArgumentException( '$display_as must be a string.' );
		}

		if(
			! array_key_exists( $display_as, $this->getDisplayAsOptions() )
			&& $display_as != self::OPTION_DO_NOT_USE
		) {
			throw new \InvalidArgumentException( '$display_as is no valid option.' );
		}

		$this->display_as = $display_as;
	}

	/**
	 * @return mixed|void
	 */
	public function jsonSerialize() {
		return array(
			'slug' => $this->slug,
			'inputName' => $this->input_name,
			'type' => $this->type,
			'displayAs' => $this->display_as
		);
	}
}