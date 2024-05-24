<?php

namespace OTGS\Toolset\Common\CodeSnippets;


use OTGS\Toolset\Common\Utils\RequestMode;

/**
 * Snippet model.
 *
 * @since 3.0.8
 */
class Snippet {


	/** @var string Display name of the snippet */
	private $name;


	/** @var string Information about the snippet. */
	private $description;


	/** @var string Absolute path to the file with the snippet. */
	private $file_path;


	/** @var string Unique slug of the snippet. */
	private $slug;


	/** @var \Toolset_Files */
	private $files;


	/** @var bool */
	private $is_active = false;


	/** @var string */
	private $subpath;


	/** @var string|null */
	private $code;


	/** @var \Toolset_Constants */
	private $constants;


	/** @var string One of the SnippetOption::RUN_ constants. */
	private $run_mode;


	/** @var string[] One or more values from RequestMode::all() */
	private $run_contexts = array();


	/** @var string|null */
	private $last_error;


	/** @var RequestMode */
	private $request_mode;


	/** @var bool|null */
	private $has_security_check;


	/**
	 * Snippet constructor.
	 *
	 * @param string $slug
	 * @param \Toolset_Files $files
	 * @param \Toolset_Constants $constants
	 * @param RequestMode $request_mode
	 */
	public function __construct( $slug, \Toolset_Files $files, \Toolset_Constants $constants, RequestMode $request_mode ) {
		$this->slug = $slug;
		$this->files = $files;
		$this->constants = $constants;
		$this->request_mode = $request_mode;
	}


	/**
	 * Display name of the slug.
	 *
	 * @return string
	 */
	public function get_name() {
		if( null === $this->name || empty( $this->name ) ) {
			return $this->get_slug();
		}
		return $this->name;
	}


	/**
	 * @param string $name
	 *
	 * @return Snippet
	 */
	public function set_name( $name ) {
		if( ! is_string( $name ) ) {
			throw new \InvalidArgumentException();
		}
		$this->name = $name;

		return $this;
	}


	/**
	 * Slug description, if the snippet has been run through CodeAccess::decorate_snippet().
	 *
	 * @return string
	 */
	public function get_description() {
		if( ! is_string( $this->description ) ) {
			return '';
		}
		return $this->description;
	}


	/**
	 * @param string $description
	 *
	 * @return Snippet
	 */
	public function set_description( $description ) {
		if( ! is_string( $description ) ) {
			throw new \InvalidArgumentException();
		}
		$this->description = $description;

		return $this;
	}


	/**
	 * @return string
	 */
	public function get_absolute_file_path() {
		return $this->file_path;
	}


	/**
	 * Subpath to the snippet file relative to the base code snippet directory.
	 *
	 * @return string
	 */
	public function get_file_subpath() {
		return $this->subpath;
	}


	/**
	 * @param string $file_path
	 * @param string $subpath
	 * @return Snippet
	 */
	public function set_file_path( $file_path, $subpath ) {
		$this->file_path = $file_path;
		$this->subpath = $subpath;

		return $this;
	}


	/**
	 * Check whether it is allowed and possible to edit the snippet's file inside WordPress.
	 *
	 * @return bool
	 */
	public function is_editable() {
		if( $this->constants->defined( 'DISALLOW_FILE_EDIT' ) && $this->constants->constant( 'DISALLOW_FILE_EDIT' ) ) {
			return false;
		}

		if( $this->constants->defined( 'DISALLOW_FILE_MODS' ) && $this->constants->constant( 'DISALLOW_FILE_MODS' ) ) {
			return false;
		}

		if( ! $this->files->is_writable( $this->get_absolute_file_path() ) ) {
			return false;
		}

		// Make sure, so that there are no unpleasant surprises when the client actually tries to edit the file.
		$handle = $this->files->fopen( $this->get_absolute_file_path(), 'a+' );
		if( false === $handle ) {
			return false;
		}
		$this->files->fclose( $handle );

		return true;
	}


	/**
	 * Run the snippet, collecting all possible output (errors) from it.
	 *
	 * @return \Toolset_Result
	 */
	public function run() {
		$path = $this->get_absolute_file_path();
		if( ! $this->files->is_file( $path ) ) {
			return new \Toolset_Result( false, sprintf(
				__( 'The code snippet file "%s" was not found or is not a regular file.', 'wpv-views' ),
				$path
			) );
		}

		if( ! $this->files->is_readable( $path ) ) {
			return new \Toolset_Result( false, sprintf(
				__( 'The code snippet file "%s" is not readable.', 'wpv-views' ),
				$path
			) );
		}

		// Make sure the snippet will pass through toolset_snippet_security_check().
		add_filter( 'toolset_is_snippet_being_executed', '__return_true' );

		// Take no chances here.
		$results = new \Toolset_Result_Set();
		$include_result = false;
		$include_output = '';

		set_error_handler(function( $errno, $errstr, $errfile, $errline ) use( $results ) {
			$errno_to_error_name = array(
				E_ERROR => 'E_ERROR',
				E_WARNING => 'E_WARNING',
				E_PARSE => 'E_PARSE',
				E_NOTICE => 'E_NOTICE',
				E_CORE_ERROR => 'E_CORE_ERROR',
				E_CORE_WARNING => 'E_CORE_WARNING',
				E_COMPILE_ERROR => 'E_COMPILE_ERROR',
				E_COMPILE_WARNING => 'E_COMPILE_WARNING',
				E_USER_ERROR => 'E_USER_ERROR',
				E_USER_WARNING => 'E_USER_ERROR',
				E_USER_NOTICE => 'E_USER_ERROR',
				E_STRICT => 'E_STRICT',
				E_RECOVERABLE_ERROR => 'E_RECOVERABLE_ERROR',
				E_DEPRECATED => 'E_DEPRECATED',
				E_USER_DEPRECATED => 'E_USER_DEPRECATED',
			);
			$error_name = toolset_getarr( $errno_to_error_name, $errno, 'error type ' . $errno );

			$results->add( false, sprintf( '%s: %s in file %s on line %s', $error_name, $errstr, $errfile, $errline ) );

			// Do not propagate the error any further.
			return true;
		});


		ob_start();

		// Catching both Throwables and Exceptions in a way that's compatible with PHP 5.x and 7.x.
		// http://php.net/manual/en/language.errors.php7.php#119652
		try {
			$include_result = $this->files->include_file( $path );
			$include_output = ob_get_contents();
		} catch( \Throwable $t ) {
			$results->add( $t );
		} /** @noinspection PhpRedundantCatchClauseInspection */ /** @noinspection PhpWrongCatchClausesOrderInspection */
		catch( \Exception $e ) {
			$results->add( $e );
		}

		ob_end_clean();

		restore_error_handler();

		remove_filter( 'toolset_is_snippet_being_executed', '__return_true' );

		if( ! $include_result ) {
			$results->add( false, sprintf(
				__( 'A problem occurred when executing snippet "%s". The result of include_once is: "%s"'),
				$this->get_slug(),
				sanitize_text_field( print_r( $include_output, true ) )
			) );
		}

		if( ! empty( $include_output ) ) {
			$results->add( false, sprintf(
				__( 'Unexpected output of the code snippet: %s', 'wpv-views' ),
				sanitize_text_field( $include_output )
			) );
		}

		// If there are no problems, is_success() or is_error() would return false because there are no results at all.
		if( $results->has_errors() ) {
			return $results->aggregate( "\n\n");
		}

		return new \Toolset_Result( true, sprintf( __( 'Executed code snippet "%s".', 'wpv-views'), $this->get_slug() ) );
	}


	/**
	 * @param bool $is_active
	 * @return $this
	 */
	public function set_is_active( $is_active ) {
		if( ! is_bool( $is_active ) ) {
			throw new \InvalidArgumentException();
		}

		$this->is_active = $is_active;

		return $this;
	}


	/**
	 * @return bool
	 */
	public function is_active() {
		return $this->is_active;
	}


	/**
	 * @return string
	 */
	public function get_slug() {
		return $this->slug;
	}


	/**
	 * @param string $slug
	 * @return $this
	 */
	public function set_slug( $slug ) {
		$this->slug = $slug;
		return $this;
	}


	/**
	 * @param string $code
	 * @return $this
	 */
	public function set_code( $code ) {
		$this->code = $code;
		return $this;
	}


	/**
	 * @return null|string
	 */
	public function get_code() {
		return $this->code;
	}


	/**
	 * @return string
	 */
	public function get_run_mode() {
		return $this->run_mode;
	}


	/**
	 * @param string $run_mode
	 * @return $this
	 */
	public function set_run_mode( $run_mode ) {
		if( ! in_array( $run_mode, array( SnippetOption::RUN_ALWAYS, SnippetOption::RUN_ON_DEMAND, SnippetOption::RUN_ONCE ) ) ) {
			$run_mode = SnippetOption::RUN_ALWAYS;
		}
		$this->run_mode = $run_mode;
		return $this;
	}


	/**
	 * @return string[]
	 */
	public function get_run_contexts() {
		return $this->run_contexts;
	}


	/**
	 * @param string[] $run_contexts
	 * @return $this
	 */
	public function set_run_contexts( $run_contexts ) {
		$request_mode = $this->request_mode;
		$run_contexts = array_filter( $run_contexts, function( $context ) use( $request_mode ) {
			return $request_mode->is_valid( $context );
		} );

		$this->run_contexts = $run_contexts;
		return $this;
	}


	/**
	 * @return null|string
	 */
	public function get_last_error() {
		return $this->last_error;
	}


	/**
	 * @param string $last_error
	 * @return $this
	 */
	public function set_last_error( $last_error ) {
		if( ! is_string( $last_error ) ) {
			$last_error = '';
		}
		$this->last_error = $last_error;

		return $this;
	}


	/**
	 * @return bool
	 */
	public function has_last_error() {
		$last_error = $this->get_last_error();
		return ! empty( $last_error );
	}


	/**
	 * @return bool
	 * @since Types 3.1.2
	 */
	public function has_security_check() {
		return (bool) $this->has_security_check;
	}


	/**
	 * @param bool $value
	 * @since Types 3.1.2
	 */
	public function set_has_security_check( $value ) {
		$this->has_security_check = (bool) $value;
	}
}