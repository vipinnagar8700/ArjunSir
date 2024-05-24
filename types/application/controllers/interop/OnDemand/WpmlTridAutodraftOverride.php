<?php

namespace OTGS\Toolset\Types\Controller\Interop\OnDemand;

use OTGS\Toolset\Common\PostStatus;
use OTGS\Toolset\Common\WPML\WpmlService;
use stdClass;

/**
 * Override WPML filters important for retrieving language information for a specific auto-draft post.
 *
 * This is required so that we can load correct related posts and RFG items when the user creates a
 * new translation of a post using the post editor (not WPML's advanced or classic translation editor).
 * Such a translation is first created as an auto-draft which has its own separate TRID, and the correct TRID
 * is passed via GET parameters and stored for the auto-draft once it becomes a proper draft. The reason for
 * such behavior is in some WPML complexities and not relevant in this context.
 *
 * The point is that we need to query the right content from the very beginning, otherwise associations created
 * before reloading the block editor won't be preserved correctly (which is especially bad for RFG items, as they
 * will just disappear to the user).
 *
 * As a workaround, we detect this scenario during relevant AJAX calls and override WPML API filters only for this
 * particular auto-draft post (and we bail if anything is suspicious). We override both its TRID and language.
 *
 * Note that the association query can't use conditions by element ID and domain in such cases because in the database,
 * the TRID still doesn't match. But querying for the (overridden) TRID yields the expected results (via using either
 * the element_trid_or_id_and_domain() condition or querying by original element ID).
 *
 * @since 3.4
 */
class WpmlTridAutodraftOverride {

	/** @var PostStatus */
	private $post_status;

	/** @var WpmlService */
	private $wpml_service;


	/**
	 * WpmlTridAutodraftOverride constructor.
	 *
	 * @param PostStatus $post_status
	 * @param WpmlService $wpml_service
	 */
	public function __construct( PostStatus $post_status, WpmlService $wpml_service ) {
		$this->post_status = $post_status;
		$this->wpml_service = $wpml_service;
	}


	/**
	 * Override the TRID of the specified auto-draft post.
	 *
	 * @param int $post_id Post ID.
	 * @param int $new_trid Target TRID of the newly created post.
	 * @param string $new_lang Target language of the newly created post.
	 */
	public function initialize( $post_id, $new_trid, $new_lang ) {
		if ( ! $post_id || ! $new_trid || empty( $new_lang ) ) {
			return;
		}

		if ( PostStatus::AUTODRAFT !== $this->post_status->get_post_status( $post_id ) ) {
			return;
		}

		$this->wpml_service->override_post_trid( $post_id, $new_trid );

		add_filter(
			'wpml_element_language_details',
			static function ( $element_object, $args ) use ( $post_id, $new_trid, $new_lang ) {
				if (
					is_array( $args )
					&& array_key_exists( 'element_id', $args )
					&& array_key_exists( 'element_type', $args )
					&& (int) $args['element_id'] === $post_id
					&& get_post_type( $post_id ) === $args['element_type']
				) {
					if ( ! is_object( $element_object ) ) {
						$element_object = new stdClass();
					}
					$element_object->element_id = $args['element_id'];
					$element_object->element_type = $args['element_type'];
					$element_object->trid = $new_trid;
					$element_object->language_code = $new_lang;
				}

				return $element_object;
			},
			100, // Has to be after WPML's filter (priority 10).
			2
		);

		add_filter(
			'wpml_post_language_details',
			static function ( $post_language_details, $filtered_post_id ) use ( $post_id, $new_lang ) {
				if (
					(int) $filtered_post_id === $post_id
					&& is_array( $post_language_details )
					&& array_key_exists( 'language_code', $post_language_details )
				) {
					$post_language_details['language_code'] = $new_lang;
				}

				return $post_language_details;
			},
			100, // Has to be after WPML's filter (priority 10).
			2
		);
	}

}
