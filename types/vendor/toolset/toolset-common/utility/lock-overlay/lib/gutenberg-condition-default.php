<?php

/**
 * Class Toolset_Gutenberg_Editor_Condition_Default
 * Provides basic condition to know if you are in Gutenberg editor page
 * can be easily overriddden to add conditions while keeping its conditions as defaults with
 * public function is_met() {
 *     return parent::is_met() && more_conditions_one() && more_conditions_two();
 * }
 */
class Toolset_Gutenberg_Editor_Condition_Default extends Toolset_Condition_Plugin_Gutenberg_Active {

	protected $page = 'post.php';
	protected $query_var = 'action';
	protected $var_value = 'edit';
	protected $default_action = 'the_post';

	// Action added in Gutenberg editor page only: https://github.com/WordPress/gutenberg/issues/1316
	private $pagenow;

	public function __construct( $pagenow ) {
		$this->pagenow = $pagenow;
	}

	public function is_met() {
		if ( ! parent::is_met() ) {
			return false;
		}

		return $this->pagenow === $this->page
			&& isset( $_GET[ $this->query_var ] )
			&& $_GET[ $this->query_var ] === $this->var_value
			&& did_action( $this->default_action );
	}
}
