<?php

namespace OTGS\Toolset\Common\CodeSnippets;


/**
 * Code snippet repository.
 *
 * Manages loading and obtaining snippets, and completely manages snippet options.
 *
 * @since 3.0.8
 */
class Repository {


	/** @var Snippet[] */
	private $snippets = array();


	private $are_all_snippets_loaded = false;


	/** @var array Cached value of the option */
	private $options;


	/** @var SnippetBuilder */
	private $snippet_builder;


	/** @var Explorer */
	private $snippet_explorer;


	/** @var bool True if there are some unsaved changes in snippet options. */
	private $needs_option_update = false;


	/** @var SnippetOptionsRecord */
	private $options_record;


	/**
	 * Repository constructor.
	 *
	 * @param SnippetBuilder $snippet_builder
	 * @param Explorer $snippet_explorer
	 * @param SnippetOptionsRecord $options_record
	 */
	public function __construct(
		SnippetBuilder $snippet_builder, Explorer $snippet_explorer, SnippetOptionsRecord $options_record
	) {
		$this->snippet_builder = $snippet_builder;
		$this->snippet_explorer = $snippet_explorer;
		$this->options_record = $options_record;
	}


	/**
	 * Get a snippet by its slug.
	 *
	 * Load all snippets if they haven't been loaded yet.
	 *
	 * @param string $snippet_slug
	 * @return null|Snippet
	 */
	public function get( $snippet_slug ) {
		if ( array_key_exists( $snippet_slug, $this->snippets ) ) {
			return $this->snippets[ $snippet_slug ];
		}

		if ( ! $this->are_all_snippets_loaded ) {
			$this->load_all();

			return $this->get( $snippet_slug );
		}

		return null;
	}


	/**
	 * Load all snippets and store them inside the repository.
	 *
	 * This loads snippets by existing files and then attaches stored options to them, if there are any.
	 * @return Snippet[]
	 */
	public function load_all() {
		$paths = $this->snippet_explorer->get_all_paths();
		foreach ( $paths as $path ) {
			try {
				$snippet = $this->snippet_builder->create_snippet( $path, $this->get_options() );
			} /** @noinspection PhpRedundantCatchClauseInspection */ catch ( SnippetCreationException $e ) {
				continue;
			}

			$this->snippets[ $snippet->get_slug() ] = $snippet;
		}

		$this->are_all_snippets_loaded = true;

		return $this->snippets;
	}


	/**
	 * Get the option with snippet settings.
	 *
	 * @return array
	 */
	private function get_options() {
		if ( null === $this->options ) {
			$this->options = toolset_ensarr( $this->options_record->getOption() );
		}

		return $this->options;
	}


	/**
	 * Get only snippets that are active.
	 *
	 * Note: This can be further optimized, so that only specific files are loaded according to the value in
	 * the option array.
	 *
	 * @return Snippet[]
	 */
	public function get_active_snippets() {
		$snippets = $this->load_all();

		return array_filter( $snippets, function ( Snippet $snippet ) {
			return $snippet->is_active();
		} );
	}


	/**
	 * Update the option with snippet settings if there is an indication that it is needed.
	 */
	public function maybe_update_option() {
		if ( ! $this->needs_option_update ) {
			return;
		}

		$snippet_options = array();
		foreach ( $this->snippets as $snippet ) {
			$snippet_options[] = (array) SnippetOption::from_snippet( $snippet );
		}
		$this->options['snippets'] = $snippet_options;

		$this->options_record->updateOption( $this->options );

		$this->needs_option_update = false;
	}


	/**
	 * Change the slug of a snippet.
	 *
	 * Doesn't update the option automatically.
	 *
	 * @param Snippet $snippet
	 * @param string $new_slug
	 *
	 * @return \Toolset_Result
	 */
	public function rename_snippet_slug( Snippet $snippet, $new_slug ) {
		if ( $new_slug === $snippet->get_slug() ) {
			return new \Toolset_Result( true );
		}

		if ( empty( $new_slug ) ) {
			return new \Toolset_Result( false, __( 'A snippet slug cannot be empty.', 'wpv-views' ) );
		}

		if ( sanitize_title( str_replace( '.', '-', $new_slug ) ) !== str_replace( '.', '-', $new_slug ) ) {
			return new \Toolset_Result( false, __( 'The new slug value is not valid. Only lowercase letters, numbers, underscores, dots and dashes are accepted.', 'wpv-views' ) );
		}

		if ( array_key_exists( $new_slug, $this->snippets ) ) {
			return new \Toolset_Result( false, sprintf( __( 'Slug "%s" is already being used. Please choose a unique value.', 'wpv-views' ), $new_slug ) );
		}

		unset( $this->snippets[ $snippet->get_slug() ] );
		$this->snippets[ $new_slug ] = $snippet;
		$snippet->set_slug( $new_slug );

		$this->needs_option_update();

		return new \Toolset_Result( true );
	}


	/**
	 * Indicate that the option with snippet settings needs updating.
	 */
	public function needs_option_update() {
		$this->needs_option_update = true;
	}


	/**
	 * @return bool
	 */
	public function is_option_update_needed() {
		return $this->needs_option_update;
	}


	/**
	 * Insert a new snippet to the repository.
	 *
	 * It will fail if the snippet's slug is already used.
	 * Doesn't update the option automatically.
	 *
	 * @param Snippet $snippet
	 *
	 * @return \Toolset_Result
	 */
	public function insert( Snippet $snippet ) {
		// There is no guarantee that all snippets are accounted for at this point, and we need them.
		$this->load_all();

		if ( array_key_exists( $snippet->get_slug(), $this->snippets ) ) {
			return new \Toolset_Result( false, sprintf( __( 'Slug "%s" is already being used. Please choose a unique value.', 'wpv-views' ), $snippet->get_slug() ) );
		}

		// If a snippet is already stored under a different slug, it is most probably because it had its options automatically
		// created, and we're overwriting it with the currently provided instance.
		/** @var Snippet[] $matching_previous_snippets */
		$matching_previous_snippets = array_filter( $this->snippets, function( Snippet $previous_snippet ) use( $snippet ) {
			return ( $previous_snippet->get_absolute_file_path() === $snippet->get_absolute_file_path() );
		});
		foreach( $matching_previous_snippets as $matching_previous_snippet ) {
			unset( $this->snippets[ $matching_previous_snippet->get_slug() ] );
		}

		$this->snippets[ $snippet->get_slug() ] = $snippet;
		$this->needs_option_update();

		return new \Toolset_Result( true, __( 'New snippet has been inserted.', 'wpv-views' ) );
	}


	/**
	 * Remove a snippet from the repository.
	 *
	 * Doesn't update the option automatically.
	 *
	 * @param Snippet $snippet
	 * @return \Toolset_Result
	 */
	public function remove( Snippet $snippet ) {
		unset( $this->snippets[ $snippet->get_slug() ] );
		$this->needs_option_update();

		return new \Toolset_Result( true );
	}
}
