<?php

namespace OTGS\Toolset\Types\Ajax\Handler;

use OTGS\Toolset\Common\Field\Group\GroupDisplayResult;
use Toolset_Ajax;
use Toolset_Ajax_Handler_Abstract;
use Toolset_Element_Exception_Element_Doesnt_Exist;
use Toolset_Element_Factory;
use Toolset_Field_Group_Post_Factory;
use Types_Ajax;

/**
 * Handler for the types_reevaluate_displayed_field_groups action.
 *
 * Compares currently displayed field groups on an Edit Post page with the list of groups that should be actually
 * displayed (rendered) based on current post's data and sends the result.
 *
 * This is used in the block editor which doesn't reload a page after saving a post, but we have some field group
 * display conditions (based on terms or used template) that are evaluated only when rendering the page.
 *
 * @see AddOrEditPost.js
 * @since 3.3.7
 */
class ReevaluateDisplayedFieldGroups extends Toolset_Ajax_Handler_Abstract {

	/** @var Toolset_Element_Factory */
	private $element_factory;

	/** @var Toolset_Field_Group_Post_Factory */
	private $post_field_group_factory;


	/**
	 * ReevaluateDisplayedFieldGroups constructor.
	 *
	 * @param Toolset_Ajax $ajax_manager
	 * @param Toolset_Element_Factory $element_factory
	 * @param Toolset_Field_Group_Post_Factory $post_field_group_factory
	 */
	public function __construct(
		Toolset_Ajax $ajax_manager, Toolset_Element_Factory $element_factory, Toolset_Field_Group_Post_Factory $post_field_group_factory
	) {
		parent::__construct( $ajax_manager );
		$this->element_factory = $element_factory;
		$this->post_field_group_factory = $post_field_group_factory;
	}


	/**
	 * Processes the Ajax call.
	 *
	 * @param array $arguments Original action arguments.
	 *
	 * @return void
	 */
	public function process_call( $arguments ) {
		$this->ajax_begin( [
			'nonce' => Types_Ajax::CALLBACK_REEVALUATE_DISPLAYED_FIELD_GROUPS,
		] );

		/** @var string[] $previously_displayed_field_groups */
		$previously_displayed_field_groups = array_filter(
			toolset_ensarr( toolset_getpost( 'displayedFieldGroups' ) ),
			static function ( $slug ) {
				return is_string( $slug );
			}
		);

		$post_id = (int) toolset_getpost( 'postId' );
		try {
			$post = $this->element_factory->get_post_untranslated( $post_id );
		} catch ( Toolset_Element_Exception_Element_Doesnt_Exist $e ) {
			$this->ajax_finish( [], false );

			return;
		}

		$group_display_results = $this->post_field_group_factory
			->get_groups_for_element( $post, true );
		/** @var string[] $newly_selected_group_slugs */
		$newly_selected_group_slugs = array_map(
			static function ( GroupDisplayResult $display_result ) {
				return $display_result->get_group()->get_slug();
			},
			array_filter(
				$group_display_results,
				static function ( GroupDisplayResult $display_result ) {
					return $display_result->is_selected();
				}
			)
		);

		$same_groups_selected = (
			count( $previously_displayed_field_groups ) === count( $newly_selected_group_slugs )
			&& (
				array_diff( $previously_displayed_field_groups, $newly_selected_group_slugs )
				=== array_diff( $newly_selected_group_slugs, $previously_displayed_field_groups )
			)
		);

		$this->ajax_finish( [ 'fieldGroupsChanged' => ! $same_groups_selected ] );
	}
}

/** @noinspection PhpIgnoredClassAliasDeclaration */
class_alias( ReevaluateDisplayedFieldGroups::class, 'Types_Ajax_Handler_Reevaluate_Displayed_Field_Groups' );
