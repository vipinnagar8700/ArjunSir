<?php

namespace OTGS\Toolset\Common\CodeSnippets;


use OTGS\Toolset\Common\Utils\RequestMode;

/**
 * Represents options for a single snippet, as they are stored in the database.
 *
 * This is a mere data object with minimal logic, not really a model with some responsibilities.
 *
 * @since 3.0.8
 */
class SnippetOption implements \ArrayAccess {


	// Run mode values.
	const RUN_ALWAYS = 'always';

	const RUN_ONCE = 'once';

	const RUN_ON_DEMAND = 'ondemand';

	/** @var string */
	public $slug;

	/** @var string */
	public $name;

	/** @var string */
	public $file_name;

	/** @var bool */
	public $is_active;

	/** @var string */
	public $run_mode;

	/** @var array|null */
	public $run_contexts;

	/** @var string */
	public $last_error;


	/**
	 * SnippetOption constructor.
	 *
	 * @param string $slug
	 * @param string $name
	 * @param string $file_name
	 * @param bool $is_active
	 * @param string $run_mode
	 * @param null|string[] $run_contexts
	 * @param string $last_error
	 */
	public function __construct(
		$slug = '', $name = '', $file_name = '', $is_active = false, $run_mode = self::RUN_ALWAYS, $run_contexts = null,
		$last_error = ''
	) {
		$this->slug = $slug;
		$this->name = $name;
		$this->file_name = $file_name;
		$this->is_active = $is_active;
		$this->run_mode = $run_mode;
		$this->run_contexts = ( null === $run_contexts ) ? RequestMode::all() : $run_contexts;
		$this->last_error = $last_error;
	}


	/**
	 * @param Snippet $snippet
	 *
	 * @return SnippetOption
	 */
	public static function from_snippet( Snippet $snippet ) {
		return new self(
			$snippet->get_slug(), $snippet->get_name(), $snippet->get_file_subpath(), $snippet->is_active(),
			$snippet->get_run_mode(), $snippet->get_run_contexts(), $snippet->get_last_error()
		);
	}


	/**
	 * @param $options
	 *
	 * @return SnippetOption
	 */
	public static function from_array( $options ) {
		return new self(
			toolset_getarr( $options, 'slug' ),
			toolset_getarr( $options, 'name' ),
			toolset_getarr( $options, 'file_name' ),
			toolset_getarr( $options, 'is_active' ),
			toolset_getarr( $options, 'run_mode' ),
			toolset_getarr( $options, 'run_contexts' ),
			toolset_getarr( $options, 'last_error' )
		);
	}

	/**
	 * Whether a offset exists
	 *
	 * @link http://php.net/manual/en/arrayaccess.offsetexists.php
	 *
	 * @param mixed $offset <p>
	 * An offset to check for.
	 * </p>
	 *
	 * @return boolean true on success or false on failure.
	 * </p>
	 * <p>
	 * The return value will be casted to boolean if non-boolean was returned.
	 * @since 5.0.0
	 */
	public function offsetExists( $offset ) {
		return property_exists( $this, $offset );
	}

	/**
	 * Offset to retrieve
	 *
	 * @link http://php.net/manual/en/arrayaccess.offsetget.php
	 *
	 * @param mixed $offset <p>
	 * The offset to retrieve.
	 * </p>
	 *
	 * @return mixed Can return all value types.
	 * @since 5.0.0
	 */
	public function offsetGet( $offset ) {
		return $this->$offset;
	}

	/**
	 * Offset to set
	 *
	 * @link http://php.net/manual/en/arrayaccess.offsetset.php
	 *
	 * @param mixed $offset <p>
	 * The offset to assign the value to.
	 * </p>
	 * @param mixed $value <p>
	 * The value to set.
	 * </p>
	 *
	 * @return void
	 * @since 5.0.0
	 */
	public function offsetSet( $offset, $value ) {
		$this->$offset = $value;
	}

	/**
	 * Offset to unset
	 *
	 * @link http://php.net/manual/en/arrayaccess.offsetunset.php
	 *
	 * @param mixed $offset <p>
	 * The offset to unset.
	 * </p>
	 *
	 * @return void
	 * @since 5.0.0
	 */
	public function offsetUnset( $offset ) {
		$this->$offset = null;
	}

}