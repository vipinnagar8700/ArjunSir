<?php

namespace OTGS\Toolset\Common\Admin;

use OTGS\Toolset\Common\Relationships\API\Factory;

/**
 * Register sections for the Troubleshooting page.
 *
 * @since Types 3.3.8
 */
class TroubleshootingSections {


	private $ajax_manager;


	public function __construct( \Toolset_Ajax $ajax_manager ) {
		$this->ajax_manager = $ajax_manager;
	}

	/**
	 * Initialization.
	 */
	public function initialize() {
		add_filter( 'toolset_get_troubleshooting_sections', [ $this, 'add_sections' ] );
	}


	/**
	 * Add the troubleshooting sections.
	 *
	 * See Toolset_Page_Troubleshooting::get_sections() for details.
	 *
	 * @param array $sections
	 * @return array
	 */
	public function add_sections( $sections ) {
		$sections = toolset_ensarr( $sections );

		$this->add_clear_post_guid_id_cache( $sections );
		$this->maybe_add_relationship_postmigration_sections( $sections );

		return $sections;
	}


	private function add_clear_post_guid_id_cache( &$sections ) {
		$sections['clear_post_guid_id_cache'] = [
			'title' => __( 'Clear the GUID to attachment ID cache', 'wpv-views' ),
			'description' => __(
				'Toolset maintains a dedicated database table to speed up the translation of media files. This is necessary, for example, when displaying an image on the front-end and using %%TITLE%% and %%ALT%% placeholders for its attributes. In very rare cases, it is possible for this table to become corrupted or obsolete. Here, you can clear it. That may lead to a temporary decline of performance the first time each image is loaded.',
				'wpv-views'
			),
			'button_label' => __( 'Clear the toolset_post_guid_id table', 'wpv-views' ),
			'is_dangerous' => false,
			'action_name' => $this->ajax_manager->get_action_js_name( \Toolset_Ajax::CALLBACK_CLEAR_POST_GUID_ID_CACHE ),
			'nonce' => wp_create_nonce( \Toolset_Ajax::CALLBACK_CLEAR_POST_GUID_ID_CACHE ),
		];
	}


	private function maybe_add_relationship_postmigration_sections( &$sections ) {
		if( ! apply_filters( 'toolset_is_m2m_enabled', false ) ) {
			return;
		}

		$relationships_factory = new Factory();

		if( $relationships_factory->low_level_gateway()->can_do_after_migration_cleanup() ) {
			$sections['cleanup_after_relationship_migration'] = [
				'title' => __( 'Clean-up after the migration of the relationship data structures', 'wpv-views' ),
				'description' => __( 'There is a backup database table that holds associations between elements as they were before the migration to the most up-to-date database structure. You can remove this table now but doing so will make the migration rollback impossible.', 'wpv-views' ),
				'button_label' => __( 'Remove the backup toolset_associations_old table', 'wpv-views' ),
				'is_dangerous' => false,
				'action_name' => $this->ajax_manager->get_action_js_name( \Toolset_Ajax::CALLBACK_CLEANUP_AFTER_RELATIONSHIP_MIGRATION ),
				'nonce' => wp_create_nonce( \Toolset_Ajax::CALLBACK_CLEANUP_AFTER_RELATIONSHIP_MIGRATION ),
			];
		}

		if( $relationships_factory->low_level_gateway()->can_do_after_migration_rollback() ) {
			$sections['rollback_after_relationship_migration'] = [
				'title' => __( 'Rollback after the migration of the relationship data structures', 'wpv-views' ),
				'description' => __( 'There is a backup database table that holds associations between elements as they were before the migration to the most up-to-date database structure. You can revert the associations to this state and return to using the previous version of the database layer. However, any changes made to associations since then will be lost. Also, there might be some minor inconsistencies if posts involved in any associations have been deleted in the meantime.', 'wpv-views' ),
				'button_label' => __( 'Perform the rollback', 'wpv-views' ),
				'is_dangerous' => true,
				'action_name' => $this->ajax_manager->get_action_js_name( \Toolset_Ajax::CALLBACK_ROLLBACK_AFTER_RELATIONSHIP_MIGRATION ),
				'nonce' => wp_create_nonce( \Toolset_Ajax::CALLBACK_ROLLBACK_AFTER_RELATIONSHIP_MIGRATION ),
			];
		}

		$sections['clear_orphan_ipt_flags'] = [
			'title' => __( 'Fix orphaned intermediary post types', 'wpv-views' ),
			'description' => __(
				'In rare cases, it may happen that a post type is marked as an intermediary post type of a relationship, while that relationship no longer exists. Here, you can scan for such post types and turn them into standard ones. Note that this cannot be undone.',
				'wpv-views'
			),
			'button_label' => __( 'Fix orphaned intermediary post types', 'wpv-views' ),
			'is_dangerous' => true,
			'action_name' => \Toolset_Ajax::get_instance()->get_action_js_name( \Toolset_Ajax::CALLBACK_FIX_ORPHAN_INTERMEDIARY_POST_TYPES ),
			'nonce' => wp_create_nonce( \Toolset_Ajax::CALLBACK_FIX_ORPHAN_INTERMEDIARY_POST_TYPES ),
		];

		$sections['clear_orphan_ipt_flags'] = [
			'title' => __( 'Fix orphaned intermediary post types', 'wpv-views' ),
			'description' => __(
				'In rare cases, it may happen that a post type is marked as an intermediary post type of a relationship, while that relationship no longer exists. Here, you can scan for such post types and turn them into standard ones. Note that this cannot be undone.',
				'wpv-views'
			),
			'button_label' => __( 'Fix orphaned intermediary post types', 'wpv-views' ),
			'is_dangerous' => true,
			'action_name' => \Toolset_Ajax::get_instance()->get_action_js_name( \Toolset_Ajax::CALLBACK_FIX_ORPHAN_INTERMEDIARY_POST_TYPES ),
			'nonce' => wp_create_nonce( \Toolset_Ajax::CALLBACK_FIX_ORPHAN_INTERMEDIARY_POST_TYPES ),
		];

		return $sections;
	}

}
