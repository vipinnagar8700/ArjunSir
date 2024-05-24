<?php

namespace OTGS\Toolset\Types\Field\Group\View;

use DateInterval;
use DateTime;
use Exception;
use WP_Post;

/**
 * View helper for field group
 *
 * @since 3.2
 */
class Group {

	/** @var array */
	private $group;

	/** @var WP_Post|null */
	private $group_post;


	/**
	 * Collapsed constructor.
	 *
	 * @param array $group Legacy array structure, take it with care and proof every key pre-use.
	 * @param WP_Post|null $group_post
	 */
	public function __construct( $group, WP_Post $group_post = null ) {
		$this->group = $group;
		$this->group_post = $group_post;
	}


	/**
	 * Check if group settings are collapsed
	 */
	public function are_settings_collapsed() {
		if ( ! is_array( $this->group ) ) {
			return false;
		}

		if ( ! isset( $this->group['id'] ) || empty( $this->group['id'] ) ) {
			// new group -> not collapsed
			return false;
		}

		if ( ! isset( $this->group['fields'] )
			|| ! is_array( $this->group['fields'] )
			|| empty( $this->group['fields'] ) ) {
			// no field created yet -> not collapsed
			return false;
		}

		if ( ! $this->group_post ) {
			// for further checks the group post is required, if not show the settings
			return false;
		}

		// get creation time of group to compare with current time
		try {
			$post_created = new DateTime( $this->group_post->post_date );
			$now = new DateTime( 'now' );
		} catch ( Exception $e ) {
			return false;
		}


		if ( $post_created->add( new DateInterval( 'PT10M' ) ) > $now ) {
			// group is not older than an 10 minutes -> not collapsed
			return false;
		}

		// group has fields and is older than 10 minutes
		// in this case the group should be collapsed on load
		return true;
	}
}
