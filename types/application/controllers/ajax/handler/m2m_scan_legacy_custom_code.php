<?php

use OTGS\Toolset\Types\Utils\CodeScanner as scanner;

/**
 * Scan for legacy custom code involving post relationships.
 *
 * The scanning happens in batches, checking theme files and then content of posts.
 *
 * This is used in the m2m migration dialog.
 *
 * @since 2.3-b5
 */
class Types_Ajax_Handler_M2M_Scan_Legacy_Custom_Code extends Toolset_Ajax_Handler_Abstract {


	const POSTS_PER_REQUEST = 100;


	/** @var scanner\Factory */
	private $code_scanner_factory;


	/**
	 * Types_Ajax_Handler_M2M_Scan_Legacy_Custom_Code constructor.
	 *
	 * @param Types_Ajax $ajax_manager
	 * @param scanner\Factory|null $code_scanner_factory_di
	 */
	public function __construct( $ajax_manager, scanner\Factory $code_scanner_factory_di = null ) {
		parent::__construct( $ajax_manager );

		$this->code_scanner_factory = $code_scanner_factory_di ?: new scanner\Factory();
	}


	/**
	 * Processes the Ajax call
	 *
	 * @param array $arguments Original action arguments.
	 *
	 * @return void
	 */
	public function process_call( $arguments ) {
		$this->get_ajax_manager()->ajax_begin(
			array( 'nonce' => Types_Ajax::CALLBACK_M2M_SCAN_LEGACY_CUSTOM_CODE )
		);

		/**
		 * types_skip_m2m_migration_legacy_code_scan
		 *
		 * Optionally allow skipping the legacy relationship code scan before running the m2m migration.
		 * Use at your own risk.
		 *
		 * @since 3.0
		 */
		if( apply_filters( 'types_skip_m2m_migration_legacy_code_scan', false ) ) {
			$this->get_ajax_manager()->ajax_finish(
				array(
					'results' => array(),
					'next_step' => 0
				),
				true
			);
		}

		$scan_step = (int) toolset_getpost( 'scan_step' );

		if( 0 === $scan_step ) {
			$scanner = $this->code_scanner_factory->theme_files( $this->get_patterns_for_theme_files() );
		} elseif( 1 === $scan_step ) {
			$scanner = $this->code_scanner_factory->post_meta(
				$this->get_patterns_for_post_content(),
				array( 'layout_meta_html', '_wpv_layout_settings', '_wpv_settings' )
			);
		} else {
			$page = $scan_step - 2;
			$offset = $page * self::POSTS_PER_REQUEST;
			$scanner = $this->code_scanner_factory->post_content( $this->get_patterns_for_post_content(), self::POSTS_PER_REQUEST, $offset );
		}

		$results = $scanner->scan();

		$results_for_json = array_map( static function( scanner\Result $result ) {
			return $result->to_array();
		}, $results );

		$next_step = ( 0 === $scan_step || 1 === $scan_step || $scanner->has_more_posts() ? $scan_step + 1 : 0 );

		$this->get_ajax_manager()->ajax_finish(
			array(
				'results' => $results_for_json,
				'next_step' => $next_step
			),
			true
		);
	}


	private function get_patterns_for_theme_files() {
		return array(
			// postmeta
			$this->code_scanner_factory->strpos_pattern( '_wpcf_belongs_' ),
			// legacy functions involving post relationships
			$this->code_scanner_factory->strpos_pattern( 'wpcf_pr_' ),
			$this->code_scanner_factory->strpos_pattern( 'wpcf_relationship_' ),
			// option where legacy relationships are defined
			$this->code_scanner_factory->strpos_pattern( 'wpcf_post_relationship' ),
		);
	}


	private function get_patterns_for_post_content() {
		return array(
			$this->code_scanner_factory->strpos_pattern( '_wpcf_belongs_' ),
		);
	}

}
