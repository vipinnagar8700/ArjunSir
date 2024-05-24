<?php

namespace OTGS\Toolset\Common\CodeSnippets;


/**
 * Builder class for instantiating Snippet objects.
 *
 * This one is a bit tricky, because we have to deal with several problems when instantiating a Snippet:
 * - check that the provided path can actually represent a snippet
 * - there may be only a snippet file without any options
 * - if there are no options, we need to determine a sensible AND an unique slug for the snippet
 *
 * @since 3.0.8
 */
class SnippetBuilder {


	/** @var \Toolset_Files */
	private $files;

	/** @var Explorer */
	private $snippet_explorer;

	/** @var SnippetFactory */
	private $snippet_factory;


	/**
	 * SnippetFactory constructor.
	 *
	 * @param \Toolset_Files $files
	 * @param Explorer $snippet_explorer
	 * @param SnippetFactory $snippet_factory
	 */
	public function __construct(
		\Toolset_Files $files,
		Explorer $snippet_explorer,
		SnippetFactory $snippet_factory
	) {
		$this->files = $files;
		$this->snippet_explorer = $snippet_explorer;
		$this->snippet_factory = $snippet_factory;
	}

	/**
	 * Instantiate a snippet model.
	 *
	 * @param string $path Absolute path to the snippet file.
	 * @param array|SnippetOption $options Option with all snippet settings or a SnippetOption for a the particular snippet, if it's already known.
	 * @return Snippet
	 */
	public function create_snippet( $path, $options ) {
		if( ! $this->snippet_explorer->is_in_supported_directory( $path ) ) {
			throw new SnippetCreationException(
				sprintf(
					__( 'The snippet file "%s" is not in a supported directory "%s".', 'wpv-views' ),
					sanitize_text_field( $path ),
					$this->snippet_explorer->get_base_directory()
				)
			);
		}
		if( ! $this->files->is_file( $path ) ) {
			throw new SnippetCreationException(
				sprintf( __( 'The snippet file "%s" doesn\'t exist or isn\'t a regular file.', 'wpv-views' ), sanitize_text_field( $path ) )
			);
		}

		$subpath = untrailingslashit( $this->snippet_explorer->get_subpath( $path ) );

		if( $options instanceof SnippetOption ) {
			$snippet_options = $options;
		} else {
			// This will either get the options or create a new empty SnippetOption object.
			$snippet_options = $this->get_options_by_subpath( $subpath, $options );
		}

		/** @var Snippet $snippet */
		$snippet = $this->snippet_factory->create(
			$this->derive_slug( $subpath, $snippet_options, $options )
		);

		$snippet->set_file_path( $path, $subpath )
			->set_name( sanitize_text_field( $snippet_options->name ) )
			->set_is_active( (bool) $snippet_options->is_active )
			->set_run_mode( $snippet_options->run_mode )
			->set_run_contexts( $snippet_options->run_contexts )
			->set_last_error( $snippet_options->last_error );

		return $snippet;
	}


	/**
	 * Get options for a single snippet by a subpath of the file it represents.
	 * If such an array cannot be found, create an empty SnippetOption object.
	 *
	 * @param string $subpath Subpath relative to the base code snippet directory.
	 * @param array $options
	 *
	 * @return SnippetOption
	 */
	private function get_options_by_subpath( $subpath, $options ) {
		foreach( $this->get_all_snippet_options( $options ) as $one_snippet_options ) {
			if( toolset_getarr( $one_snippet_options, 'file_name' ) === $subpath ) {
				return SnippetOption::from_array( $one_snippet_options );
			}
		}

		return new SnippetOption();
	}



	private function get_all_snippet_options( $options ) {
		$all_snippet_options = toolset_ensarr( toolset_getarr( $options, 'snippets' ) );
		return $all_snippet_options;
	}


	/**
	 * For a given subpath and snippet options, derive a valid and unique snippet slug.
	 *
	 * @param string $current_subpath
	 * @param SnippetOption $current_snippet_options
	 * @param array $options
	 *
	 * @return bool|mixed|string
	 */
	private function derive_slug( $current_subpath, $current_snippet_options, $options ) {
		$current_slug_in_options = toolset_getarr( $current_snippet_options, 'slug' );
		if( ! empty( $current_slug_in_options ) ) {
			// The slug is already present in the stored options. That means it will be unique (unless the data is
			// completely corrupted) and we can use it directly.
			return $current_slug_in_options;
		}

		// Get the list of already used slugs.
		//
		//
		$all_snippet_options = $this->get_all_snippet_options( $options );
		$remaining_snippet_options = array_filter( $all_snippet_options, function( $single_snippet_options ) use( $current_subpath ) {
			return ( toolset_getarr( $single_snippet_options, 'file_name' ) !== $current_subpath );
		});
		$used_slugs = array_map( function( $snippet_options ) {
			return toolset_getarr( $snippet_options, 'slug' );
		}, $remaining_snippet_options );

		// Determine a base for the new slug.
		$slug_from_subpath = substr( $current_subpath, 1 );
		if( ! in_array( $slug_from_subpath, $used_slugs ) ) {
			// We can use the subpath as a slug directly.
			return $slug_from_subpath;
		}

		// Keep trying until we hit a unique value.
		$numeric_suffix = 2;
		do {
			$slug_candidate = $slug_from_subpath . '-' . $numeric_suffix;
			$numeric_suffix++;
		} while( in_array( $slug_candidate, $used_slugs ) );

		return $slug_candidate;
	}

}