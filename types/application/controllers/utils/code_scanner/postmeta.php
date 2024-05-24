<?php

namespace OTGS\Toolset\Types\Utils\CodeScanner;

use Toolset_Utils;
use wpdb;

/**
 * Postmeta scanner.
 *
 * Looks for specific search terms in given postmeta keys. Works only with StrposPattern.
 */
class PostMeta extends DomainByPostType implements Scanner {


	/** @var StrposPattern[] */
	private $patterns;


	/** @var string[] */
	private $meta_keys;


	/** @var Factory  */
	private $factory;


	/**
	 * PostMeta constructor.
	 *
	 * @param StrposPattern[] $patterns
	 * @param string[] $meta_keys
	 * @param wpdb|null $wpdb_di
	 * @param Factory|null $factory_di
	 */
	public function __construct( $patterns, $meta_keys, wpdb $wpdb_di = null, Factory $factory_di = null ) {
		parent::__construct( $wpdb_di );
		$this->patterns = $patterns;
		$this->meta_keys = $meta_keys;
		$this->factory = $factory_di ?: new Factory();
	}


	/**
	 * @return Result[]
	 */
	public function scan() {

		$search_clauses = array_map( static function( StrposPattern $pattern ) {
			return ' postmeta.meta_value LIKE \'' . esc_sql( '%' . $pattern->get_search_term() . '%' ) . '\' ' ;
		}, $this->patterns );

		$query = sprintf(
		"
			SELECT
				post.ID AS post_id,
				post.post_title AS post_title,
				post.post_type AS post_type,
				postmeta.meta_key AS meta_key,
				postmeta.meta_value AS meta_value
			FROM {$this->wpdb->posts} AS post
				JOIN {$this->wpdb->postmeta} AS postmeta
					ON (
						post.ID = postmeta.post_id
						AND postmeta.meta_key IN ( %s )
					)
			WHERE
				post.post_status NOT LIKE 'trash'
				AND post.post_type NOT IN ( 'revision' )
				AND ( %s )",
			Toolset_Utils::prepare_mysql_in( $this->meta_keys ),
			implode( ' OR ', $search_clauses )
		);

		$postmeta_rows = $this->wpdb->get_results( $query );

		$results = array();
		foreach( $postmeta_rows as $postmeta_row ) {
			// Scan each post_meta value as a string.
			$string_scanner = $this->factory->string_scanner( $this->patterns, $postmeta_row->meta_value );
			$results_for_postmeta = $string_scanner->scan();

			$that = $this;
			$results_for_postmeta = array_map( static function( Result $result ) use( $postmeta_row, $that ) {
				$result->prepend_location(
					sprintf(
						'#%d: "%s" - %s',
						$postmeta_row->post_id,
						sanitize_text_field( $postmeta_row->post_title ),
						$postmeta_row->meta_key
					)
				);
				$result->set_domain( $that->get_result_domain( $postmeta_row->post_type ) );
				return $result;
			}, $results_for_postmeta );

			$results = array_merge( $results, $results_for_postmeta );
		}

		return $results;
	}


}
