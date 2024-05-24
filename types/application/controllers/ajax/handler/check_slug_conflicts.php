<?php

/**
 * Handle checking for slug conflicts among different domains.
 *
 * Used by the slug_conflict_checker.js script. Compares given value with all values within specified domains
 * and reports if a conflict is found.
 *
 * Currently supported domains are:
 * - Post type rewrite slugs (value of the slug used for permalink rewriting or the post type slug if rewriting is
 *   not enabled for that post type)
 * - Taxonomy rewrite slugs (analogous to post types)
 */
class Types_Ajax_Handler_Check_Slug_Conflicts extends Toolset_Ajax_Handler_Abstract {

	// Definition of supported domains
	const DOMAIN_POST_TYPE_REWRITE_SLUGS = 'post_type_rewrite_slugs';
	const DOMAIN_TAXONOMY_REWRITE_SLUGS = 'taxonomy_rewrite_slugs';
	const DOMAIN_RELATIONSHIP_REWRITE_SLUGS = 'relationships_rewrite_slugs';
	const DOMAIN_TAXONOMY_SLUGS = Toolset_Naming_Helper::DOMAIN_TAXONOMY_SLUGS;

	/** @var string[] Keywords of supported domains for slug checking. */
	private static $supported_domains = array(
		self::DOMAIN_POST_TYPE_REWRITE_SLUGS,
		self::DOMAIN_TAXONOMY_REWRITE_SLUGS,
		self::DOMAIN_RELATIONSHIP_REWRITE_SLUGS,
		self::DOMAIN_TAXONOMY_SLUGS,
	);


	/**
	 * @param array $arguments Original action arguments.
	 *
	 * @return void
	 */
	public function process_call( $arguments ) {

		$this->ajax_begin(
			array( 'nonce' => Types_Ajax::CALLBACK_CHECK_SLUG_CONFLICTS )
		);

		// Read and validate input
		$domains = toolset_getpost( 'domains' );
		$value = toolset_getpost( 'value' );
		$exclude = toolset_getpost( 'exclude' );
		$exclude_id = toolset_getarr( $exclude, 'id', 0 );
		$exclude_domain = toolset_getarr( $exclude, 'domain' );
		$diff_domains = array_diff( $domains, self::$supported_domains );

		if (
			! is_array( $domains )
			|| ! empty( $diff_domains )
			|| ! is_string( $value )
			|| ! is_array( $exclude )
			|| 0 === $exclude_id
			|| ! in_array( $exclude_domain, self::$supported_domains, true )
		) {
			$this->ajax_finish( array(), false );
		}

		$conflict = $this->check_slug_conflicts( $value, $domains, $exclude_domain, $exclude_id );

		// Parse output (report a conflict if there is any)
		if ( false === $conflict ) {
			$this->ajax_finish( array( 'isConflict' => false ), true );
		} else {

			$message = sprintf(
				'<strong>%s</strong>: %s',
				__( 'Warning', 'wpcf' ),
				toolset_getarr( $conflict, 'message' )
			);

			$this->ajax_finish(
				array(
					'isConflict' => true,
					'displayMessage' => $message,
				),
				true
			);
		}

	}


	/**
	 * Check given slug for conflicts across defined domains.
	 *
	 * @param string $value Value to check.
	 * @param string[] $domains Array of valid domains.
	 * @param string $exclude_domain Domain of the excluded object.
	 * @param string|int|null $exclude_id Id of the excluded object.
	 *
	 * @return array|bool Conflict information (an associative array with conflicting_id, message) or false when
	 *     there's no conflict.
	 *
	 * @since 2.1
	 */
	private function check_slug_conflicts( $value, $domains, $exclude_domain, $exclude_id ) {
		foreach ( $domains as $domain ) {
			$conflict = $this->check_slug_conflicts_in_domain(
				$value, $domain, ( $domain === $exclude_domain ) ? $exclude_id : null
			);
			if ( false !== $conflict ) {
				return $conflict;
			}
		}

		// No conflicts found
		return false;
	}


	/**
	 * Check given slug for conflicts in one domain.
	 *
	 * @param string $value Value to check.
	 * @param string $domain Domain name.
	 * @param string|int|null $exclude_id ID of an object to exclude within this domain, or null if there is none.
	 *
	 * @return array|bool Conflict information (an associative array with conflicting_id, message) or false when
	 *     there's no conflict.
	 *
	 * @since 2.1
	 */
	private function check_slug_conflicts_in_domain( $value, $domain, $exclude_id = null ) {
		$naming_helper = Toolset_Naming_Helper::get_instance();
		switch ( $domain ) {
			case self::DOMAIN_POST_TYPE_REWRITE_SLUGS:
				return $naming_helper->check_slug_conflicts_in_post_type_rewrite_rules( $value, $exclude_id );
			case self::DOMAIN_TAXONOMY_REWRITE_SLUGS:
				return $naming_helper->check_slug_conflicts_in_taxonomy_rewrite_rules( $value, $exclude_id );
			case self::DOMAIN_RELATIONSHIP_REWRITE_SLUGS:
				return $naming_helper->check_relationship_slug_conflicts( $value, $exclude_id );
			case self::DOMAIN_TAXONOMY_SLUGS:
				return $naming_helper->check_taxonomy_slug_conflicts( $value, $exclude_id );
			default:
				return false;
		}
	}

}
