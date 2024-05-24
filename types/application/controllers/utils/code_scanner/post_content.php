<?php

namespace OTGS\Toolset\Types\Utils\CodeScanner;

/**
 * Search content of a batch of posts for a provided set of patterns.
 *
 * @package OTGS\Toolset\Types\Utils\CodeScanner
 * @since 2.3-b5
 */
class PostContent extends DomainByPostType implements Scanner {


	/** @var Pattern[] */
	private $patterns;


	/** @var int */
	private $limit;


	/** @var int */
	private $offset;


	/** @var int */
	private $found_rows;


	/** @var Factory */
	private $factory;


	/**
	 * Scanner constructor.
	 *
	 * @param Pattern[] $patterns
	 * @param int $limit
	 * @param int $offset
	 * @param \wpdb|null $wpdb_di
	 * @param Factory|null $factory_di
	 */
	public function __construct(
		$patterns, $limit, $offset, \wpdb $wpdb_di = null, Factory $factory_di = null ) {
		parent::__construct( $wpdb_di );
		$this->patterns = $patterns;
		$this->limit = (int) $limit;
		$this->offset = (int) $offset;
		$this->factory = $factory_di ?: new Factory();
	}


	/**
	 * @return Result[]
	 */
	public function scan() {
		// Better to include all posts (including ones that are usually excluded) to make sure
		// nothing is missed. As a consequence, this also scans WPAs, Views, CRED forms, and whatnot.
		$posts = $this->wpdb->get_results(
			$this->wpdb->prepare(
				"SELECT SQL_CALC_FOUND_ROWS ID, post_title, post_content, post_type 
				FROM {$this->wpdb->posts}
				WHERE 
					post_status NOT LIKE 'trash'
					AND post_type NOT IN ( 'revision' )
				LIMIT %d OFFSET %d",
				$this->limit,
				$this->offset
			)
		);

		$this->found_rows = (int) $this->wpdb->get_var( 'SELECT FOUND_ROWS()' );

		$results = array();
		foreach( $posts as $post ) {
			// Scan each post's body as a string.
			$string_scanner = $this->factory->string_scanner( $this->patterns, $post->post_content );
			$results_for_post = $string_scanner->scan();

			$that = $this;
			$results_for_post = array_map( function( Result $result ) use( $post, $that ) {
				$result->prepend_location(
					sprintf( '#%d: "%s"', $post->ID, sanitize_text_field( $post->post_title ) )
				);
				$result->set_domain( $that->get_result_domain( $post->post_type ) );
				return $result;
			}, $results_for_post );

			$results = array_merge( $results, $results_for_post );
		}

		return $results;
	}


	/**
	 * After running scan(), this method determine whether a next batch of post scanning is needed.
	 *
	 * @return bool
	 */
	public function has_more_posts() {
		$page_number = ( ( $this->offset / $this->limit ) + 1 );
		return ( $this->found_rows > ( $page_number * $this->limit ) );
	}

}