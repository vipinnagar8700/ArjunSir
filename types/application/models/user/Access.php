<?php

namespace OTGS\Toolset\Types\User;

use InvalidArgumentException;
use WP_User;

/**
 * Class Access
 *
 * Should be used instead of WP_User::has_cap() as it respects Toolset Access settings.
 *
 * @package OTGS\Toolset\Types\User
 */
class Access {

	/** @var WP_User */
	private $user;

	/** @var bool[] */
	private $can_publish = array();

	/** @var bool[] */
	private $can_edit_any = array();

	/** @var bool[] */
	private $can_delete_any = array();

	/** @var bool[] */
	private $can_edit_own = array();

	/** @var bool[] */
	private $can_delete_own = array();


	/**
	 * Access constructor.
	 *
	 * @param WP_User $user
	 */
	public function __construct( WP_User $user ) {
		$this->user = $user;
	}


	/**
	 * @return WP_User
	 */
	public function getUser() {
		return $this->user;
	}


	/**
	 * Can the user publish posts (of the given post type)
	 *
	 * @param string|null $post_type_string
	 *
	 * @return bool
	 */
	public function canPublish( $post_type_string = null ) {
		if ( $post_type_string === null ) {
			// no specific post type requested, check WP cap
			return $this->user->has_cap( 'publish_posts' );
		}

		$post_type_string = $this->validatePostTypeString( $post_type_string );

		if ( ! isset( $this->can_publish[ $post_type_string ] ) ) {
			// respect Access settings
			$this->can_publish[ $post_type_string ] = $this->getCapRespectingToolsetAccessSettings(
				'publish_posts',
				'publish',
				$post_type_string
			);
		}

		return $this->can_publish[ $post_type_string ];
	}


	/**
	 * Can the user edit any posts (of the given post type)
	 *
	 * @param string|null $post_type_string
	 *
	 * @return bool
	 */
	public function canEditAny( $post_type_string = null ) {
		if ( $post_type_string === null ) {
			// no specific post type requested, check WP cap
			return $this->user->has_cap( 'edit_others_posts' );
		}

		$post_type_string = $this->validatePostTypeString( $post_type_string );

		if ( ! isset( $this->can_edit_any[ $post_type_string ] ) ) {
			$this->can_edit_any[ $post_type_string ] = $this->getCapRespectingToolsetAccessSettings(
				'edit_others_posts',
				'edit_any',
				$post_type_string
			);
		}

		return $this->can_edit_any[ $post_type_string ];
	}


	/**
	 * Can the user delete any posts (of the given post type)
	 *
	 * @param string|null $post_type_string
	 *
	 * @return bool
	 */
	public function canDeleteAny( $post_type_string = null ) {
		if ( $post_type_string === null ) {
			// no specific post type requested, check WP cap
			return $this->user->has_cap( 'delete_others_posts' );
		}

		$post_type_string = $this->validatePostTypeString( $post_type_string );

		if ( ! isset( $this->can_delete_any[ $post_type_string ] ) ) {
			$this->can_delete_any[ $post_type_string ] = $this->getCapRespectingToolsetAccessSettings(
				'delete_others_posts',
				'delete_any',
				$post_type_string
			);
		}

		return $this->can_delete_any[ $post_type_string ];
	}


	/**
	 * Can the user edit own posts (of the given post type)
	 *
	 * @param string|null $post_type_string
	 *
	 * @return bool
	 */
	public function canEditOwn( $post_type_string = null ) {
		if ( $post_type_string === null ) {
			// no specific post type requested, check WP cap
			return $this->user->has_cap( 'edit_posts' );
		}

		$post_type_string = $this->validatePostTypeString( $post_type_string );

		if ( ! isset( $this->can_edit_own[ $post_type_string ] ) ) {
			$this->can_edit_own[ $post_type_string ] = $this->getCapRespectingToolsetAccessSettings(
				'edit_posts',
				'edit_own',
				$post_type_string
			);
		}

		return $this->can_edit_own[ $post_type_string ];
	}


	/**
	 * Can the user delete own posts (of the given post type)
	 *
	 * @param string|null $post_type_string
	 *
	 * @return bool
	 */
	public function canDeleteOwn( $post_type_string = null ) {
		if ( $post_type_string === null ) {
			// no specific post type requested, check WP cap
			return $this->user->has_cap( 'delete_posts' );
		}

		$post_type_string = $this->validatePostTypeString( $post_type_string );

		if ( ! isset( $this->can_delete_own[ $post_type_string ] ) ) {
			$this->can_delete_own[ $post_type_string ] = $this->getCapRespectingToolsetAccessSettings(
				'delete_posts',
				'delete_own',
				$post_type_string
			);
		}

		return $this->can_delete_own[ $post_type_string ];
	}


	public function getArrayOfCapsForPostType( $post_type_string ) {
		$post_type_string = $this->validatePostTypeString( $post_type_string );

		return array(
			'publish_posts' => $this->canPublish( $post_type_string ),
			'edit_others_posts' => $this->canEditAny( $post_type_string ),
			'delete_others_posts' => $this->canDeleteAny( $post_type_string ),
			'edit_posts' => $this->canEditOwn( $post_type_string ),
			'delete_posts' => $this->canDeleteOwn( $post_type_string ),
		);
	}


	/**
	 * Check if user can edit a group
	 *
	 * @param string $group_slug
	 * @param string|null $post_type_string
	 *
	 * @return bool
	 */
	public function canEditGroup( $group_slug, $post_type_string = null ) {
		if ( $this->canEditAny() ) {
			return true;
		}

		if ( $post_type_string && $this->canEditOwn( $post_type_string ) ) {
			return true;
		}

		if ( defined( 'TACCESS_VERSION' ) ) {
			return current_user_can( 'modify_fields_in_edit_page_' . $group_slug );
		}

		return false;
	}


	/**
	 * Validate Post Type String
	 *
	 * @param string $string
	 *
	 * @return string
	 */
	private function validatePostTypeString( $string ) {
		if ( ! is_string( $string ) ) {
			throw new InvalidArgumentException( '$post_type_string must be a string.' );
		}

		return $string;
	}


	/**
	 * Get cap of user by using access filter
	 *
	 * @param string $wp_cap
	 * @param string $access_cap
	 * @param string $post_type
	 *
	 * @return mixed
	 */
	private function getCapRespectingToolsetAccessSettings( $wp_cap, $access_cap, $post_type ) {
		return apply_filters(
			'toolset_access_api_get_post_type_permissions',
			$this->user->has_cap( $wp_cap ),
			$post_type,
			$access_cap
		);
	}
}
