<?php

use OTGS\Toolset\Common\Relationships\API\Factory;
use OTGS\Toolset\Common\Result\ResultInterface;
use OTGS\Toolset\Common\Utils\RequestMode;
use OTGS\Toolset\Common\WPML\WpmlService;
use OTGS\Toolset\Types\Ajax\Handler\RepeatableFieldGroup\FetchFieldTranslationsPreview;
use OTGS\Toolset\Types\Compatibility\Yoast\Field\Repository;
use OTGS\Toolset\Types\Controller\Interop\OnDemand\WpmlTridAutodraftOverride;

/**
 * @since 3.0
 */
class Types_Ajax_Handler_Repeatable_Group extends Toolset_Ajax_Handler_Abstract {

	const NOTICE_KEY_FOR_RFG_ITEM_INTRODUCTION = 'rfg-item-title-introduction';

	/** @var Types_Field_Group_Repeatable_Service */
	private $rfg_service;

	/** @var bool */
	private $_is_default_language_active;

	/**
	 * Collection of all field conditions used on the RFG and also the nested RFGs
	 *
	 * @var array
	 */
	private $field_conditions_collection = array();

	/** @var bool True if there has been a WYSIWYG field rendered during processing of the current AJAX request. */
	private $has_wysiwyg_field = false;

	/** @var Factory */
	private $relationships_factory;

	/** @var WpmlService */
	private $wpml_service;

	/** @var Toolset_Field_Definition_Factory_Post */
	private $post_field_definition_factory;

	/** @var WpmlTridAutodraftOverride */
	private $wpml_trid_autodraft_override;


	/**
	 * Types_Ajax_Handler_Repeatable_Group constructor.
	 *
	 * @param Toolset_Ajax $ajax_manager
	 * @param Factory $relationships_factory
	 * @param WpmlService $wpml_service
	 * @param Toolset_Field_Definition_Factory_Post $post_field_definition_factory
	 * @param WpmlTridAutodraftOverride $wpml_trid_autodraft_override
	 * @param Types_Field_Group_Repeatable_Service $rfg_service
	 *
	 * @noinspection InterfacesAsConstructorDependenciesInspection
	 */
	public function __construct(
		Toolset_Ajax $ajax_manager,
		Factory $relationships_factory,
		WpmlService $wpml_service,
		Toolset_Field_Definition_Factory_Post $post_field_definition_factory,
		WpmlTridAutodraftOverride $wpml_trid_autodraft_override,
		Types_Field_Group_Repeatable_Service $rfg_service
	) {
		parent::__construct( $ajax_manager );
		$this->relationships_factory = $relationships_factory;
		$this->wpml_service = $wpml_service;
		$this->post_field_definition_factory = $post_field_definition_factory;
		$this->wpml_trid_autodraft_override = $wpml_trid_autodraft_override;
		$this->rfg_service = $rfg_service;
	}


	/**
	 * @param array $arguments Original action arguments.
	 *
	 * @return void
	 */
	public function process_call( $arguments ) {
		$this->get_ajax_manager()
			->ajax_begin(
				array(
					'nonce' => $this->get_ajax_manager()->get_action_js_name( Types_Ajax::CALLBACK_REPEATABLE_GROUP ),
					'capability_needed' => 'edit_posts',
					'is_public' => toolset_getarr( $_REQUEST, 'skip_capability_check', false ),
				)
			);

		// Read and validate input
		$action = sanitize_text_field( toolset_getpost( 'repeatable_group_action' ) );

		$this->wpml_trid_autodraft_override->initialize(
			(int) toolset_getpost( 'parent_post_id' ),
			(int) toolset_getnest( $_POST, [ 'parent_post_translation_override', 'trid' ] ),
			esc_attr( toolset_getnest( $_POST, [ 'parent_post_translation_override', 'lang_code' ] ) )
		);

		// route action
		$this->route( $action );
	}


	/**
	 * Route ajax calls
	 *
	 * @param string $action
	 *
	 * @return void
	 */
	private function route( $action ) {
		switch ( $action ) {
			case 'json_repeatable_group':
				$this->json_repeatable_group();
				return;
			case 'json_repeatable_group_add_item':
				$this->json_repeatable_group_add_item();
				return;
			case 'json_repeatable_group_remove_item':
				$this->json_repeatable_group_remove_item();
				return;
			case 'json_repeatable_group_field_original_translation':
				$this->json_repeatable_group_field_original_translation();
				return;
			case 'json_repeatable_group_item_title_introduction_dismiss':
				$this->json_repeatable_group_item_title_introduction_dismiss();
				return;
			case 'json_repeatable_group_item_title_update':
				$this->json_repeatable_group_item_title_update();
				return;
		}
	}


	/**
	 * A repeatable group can be set by post 'repeatable_group_id'.
	 * Will return Types_Field_Group_Repeatable if the given id is valid.
	 *
	 * @return false|Types_Field_Group_Repeatable
	 */
	private function get_repeatable_group_by_post_data() {
		$rfg_id = (int) toolset_getpost( 'repeatable_group_id' );
		$parent_post = get_post( (int) toolset_getpost( 'parent_post_id' ) );

		if ( ! $repeatable_group = $this->rfg_service->get_object_by_id( $rfg_id, $parent_post ) ) {
			// shouldn't happen as long as the user doesn't manipulate the DOM
			return false;
		}

		return $repeatable_group;
	}


	/**
	 * The parent post can be set by post 'parent_post_id'.
	 * Will return WP_Post if the given id is valid.
	 *
	 * @return false|WP_Post
	 */
	private function get_parent_post_by_post_data() {
		if ( ! $post = get_post( toolset_getpost( 'parent_post_id' ) ) ) {
			// shouldn't happen as long as the user doesn't manipulate the DOM
			return false;
		}

		return $post;
	}


	/**
	 * Returns a repeatable group with all it items in json format
	 *
	 * This function exits the script (ajax response).
	 *
	 * @print json
	 */
	private function json_repeatable_group() {
		$parent_post = $this->get_and_validate_parent_post_by_post_data();
		$repeatable_group = $this->get_and_validate_repeatable_group( $parent_post );

		// get Translation Mode of current post
		$wpml_is_translation_mode_supported = true;
		$parent_translation_mode = $this->wpml_service->get_post_type_translation_mode( $parent_post->post_type );
		if ( $this->relationships_factory->database_operations()->requires_default_language_post() ) {
			$parent_translation_mode = $this->wpml_service->get_post_type_translation_mode( $parent_post->post_type );
			if ( $parent_translation_mode === WpmlService::MODE_TRANSLATE ) {
				// in this mode we do not support repeatable field groups
				$wpml_is_translation_mode_supported = false;
			}
		}

		// only load items when translation mode is supported (or wpml is inactive)
		$items = $wpml_is_translation_mode_supported
			? $this->get_rfg_items( $parent_post, $repeatable_group, 1 )
			: array();

		// field conditions
		$this->add_to_field_conditions_collection_by_items( $items );

		$repeatable_group_array = [
			'id' => $repeatable_group->get_id(),
			'parent_post_id' => $parent_post->ID,
			'title' => $repeatable_group->get_display_name(),
			'headlines' => $this->get_headlines_of_group( $repeatable_group ),
			'controlsActive' => $this->should_controls_be_active( $parent_translation_mode ),
			'wpmlIsTranslationModeSupported' => $wpml_is_translation_mode_supported,
			// This is only for backward compatibility with (very) old WPML versions. Can be omitted eventually.
			'wpmlFilterExistsForOriginalData' => class_exists( 'WPML_Custom_Fields_Post_Meta_Info' ),
			'wpmlIsDefaultLanguageActive' => $this->is_default_language_active(),
			'isTranslatable' => WpmlService::MODE_DONT_TRANSLATE !== $parent_translation_mode,
			'items' => $items,
			'itemTitleIntroductionActive' => ! Toolset_Admin_Notices_Manager::is_notice_dismissed_by_notice_id(
				self::NOTICE_KEY_FOR_RFG_ITEM_INTRODUCTION
			),
			'fieldConditions' => $this->field_conditions_collection,
		];

		$response = array( 'repeatableGroup' => $repeatable_group_array );
		$response = $this->maybe_add_tinymce_settings( $response );

		$this->get_ajax_manager()->ajax_finish( $response );
	}


	/**
	 * Determine whether RFG UI should be read-only or editable.
	 *
	 * @param string $parent_translation_mode Translation mode value from WpmlService.
	 *
	 * @return bool
	 */
	private function should_controls_be_active( $parent_translation_mode ) {
		// controls are active in default language or if the post type is not translated at all
		if ( ! $this->relationships_factory->database_operations()->requires_default_language_post() ) {
			return true;
		}

		return (
			$this->is_default_language_active()
			|| WpmlService::MODE_DONT_TRANSLATE === $parent_translation_mode
		);
	}


	/**
	 * @return WP_Post
	 */
	private function get_and_validate_parent_post_by_post_data() {
		$parent_post = $this->get_parent_post_by_post_data();
		if ( ! $parent_post ) {
			// shouldn't happen as long as the user doesn't manipulate the DOM
			$this->get_ajax_manager()->ajax_finish(
				__( 'Technical issue. Please reload the page and try again.', 'wpcf' ),
				false
			);
		}

		return $parent_post;
	}


	/**
	 * Obtain the field group with items in the context of the current language (determined by the parent post).
	 *
	 * Might fail the AJAX request if the field group can't be loaded.
	 *
	 * @param WP_Post $parent_post
	 *
	 * @return Types_Field_Group_Repeatable
	 */
	private function get_and_validate_repeatable_group( WP_Post $parent_post ) {
		// Important: This needs to happen before get_repeatable_group_by_post_data() is called, otherwise we
		// won't get the correct results.
		$this->wpml_service->switch_language( $this->wpml_service->get_post_language( $parent_post->ID ) );

		$repeatable_group = $this->get_repeatable_group_by_post_data();
		if ( ! $repeatable_group ) {
			// shouldn't happen as long as the user doesn't manipulate the DOM
			$this->get_ajax_manager()->ajax_finish(
				__( 'Technical issue. Please reload the page and try again.', 'wpcf' ), false
			);
		}

		return $repeatable_group;
	}


	/**
	 * Adds an item (new post) to the rfg and sets up the association entry.
	 * The new post is returned as json
	 *
	 * This function exits the script (ajax response).
	 *
	 * @print json
	 */
	private function json_repeatable_group_add_item() {
		$parent_post = $this->get_and_validate_parent_post_by_post_data();
		$repeatable_group = $this->get_and_validate_repeatable_group( $parent_post );

		$relationship_definition = $this->get_relationship_definition(
			$parent_post->post_type,
			$repeatable_group->get_post_type_model()->get_slug()
		);

		if ( ! $relationship_definition ) {
			// error, no relationship found
			// shouldn't happen as long as the user doesn't manipulate the DOM, still:
			$this->get_ajax_manager()->ajax_finish( __( 'Technical issue. Please reload the page and try again.',
				'wpcf' ), false );
		}

		$new_post_id = wp_insert_post( array(
			'post_title' => 'RFG',
			'post_status' => 'publish',
			'post_content' => ' ',
			'post_type' => $repeatable_group->get_post_type_model()->get_slug(),
		) );

		$new_post = get_post( $new_post_id );

		if ( ! $new_post instanceof WP_Post || ! is_numeric( $new_post_id ) ) {
			$this->get_ajax_manager()->ajax_finish(
				__( 'Technical issue. Please reload the page and try again.', 'wpcf' ), false
			);
			return;
		}

		$new_post->post_title = (string) $new_post_id;

		wp_update_post( $new_post );

		/** @noinspection PhpUnhandledExceptionInspection */
		$association_result = $relationship_definition->create_association( $parent_post, $new_post );

		if ( $association_result instanceof ResultInterface && $association_result->is_error() ) {
			// the association couldn't be build, delete rfg post and throw message to save the post first
			// (currently this only happens when the post is translateable by WPML)
			wp_delete_post( $new_post_id );
			$this->get_ajax_manager()->ajax_finish( array(
				'message' => __( 'Could not create item for this unsaved post. This can happen due to other plugins interacting with the Repeatable Field Group. Please save the post and try again.', 'wpcf' ),
			),
				false
			);
		}
		/*
		 * Action 'toolset_post_update'
		 *
		 * @var WP_Post $new_post
		 *
		 * @since 3.0
		 */
		$affected_post = get_post( $new_post );
		do_action( 'toolset_post_update', $affected_post );

		// get rfg item by WP_Post
		$new_item = array( $this->get_rfg_item( $new_post, $parent_post, $repeatable_group ) );

		// field conditions
		$this->add_to_field_conditions_collection_by_items( $new_item );

		$response = array(
			'item' => $new_item[0],
			'fieldConditions' => $this->field_conditions_collection,
		);
		$response = $this->maybe_add_tinymce_settings( $response );

		$this->get_ajax_manager()->ajax_finish( $response );
	}


	/**
	 * Get the field conditions for rfg items
	 *
	 * @param array $items Note: even a single item must be passed by an array: array( $single_item )
	 *                     passed by reference to remove data which is only necessary for this function
	 *
	 */
	private function add_to_field_conditions_collection_by_items( &$items ) {
		if ( ! $parent_post = $this->get_parent_post_by_post_data() ) {
			// technical issue
			return;
		}

		$form_condition = new WPToolset_Forms_Conditional_RFG( '#post', $parent_post->post_type );

		foreach ( $items as &$item ) {
			foreach ( $item['fields'] as &$field ) {
				if ( ! isset( $field['fieldConfig'] ) ) {
					continue;
				}
				$field['fieldConfig']['id'] = $field['fieldConfig']['name'];
				$form_condition->add( $field['fieldConfig'] );
				unset( $field['fieldConfig'] );
			}
			unset( $field );
		}
		unset( $item );

		$this->field_conditions_collection =
			array_merge_recursive( $this->field_conditions_collection, $form_condition->get_conditions() );
	}


	/**
	 * Remove item
	 */
	private function json_repeatable_group_remove_item() {
		$item_to_delete = get_post( toolset_getpost( 'remove_id' ) );
		if ( ! $item_to_delete ) {
			// error, no post found
			// shouldn't happen as long as the user doesn't manipulate the DOM
			$this->get_ajax_manager()
				->ajax_finish( __( 'System Error. Item could not be deleted. Reload the page and try again. If the issue remains, contact our support please.', 'wpcf' ), false );
		}

		// translation management action
		// delete removed item from translation job by updating the parent post
		$belongs_to_post = get_post( toolset_getpost( 'belongs_to_post_id' ) );
		if ( defined( 'WPML_TM_VERSION' ) && $belongs_to_post ) {
			wp_update_post( $belongs_to_post );
		}

		if ( $this->rfg_service->delete_item( $item_to_delete ) ) {
			// all good, item deleted
			$this->get_ajax_manager()->ajax_finish( 'Item deleted.' );
		}

		// something went wrong
		$this->get_ajax_manager()
			->ajax_finish( __( 'System Error. Item could not be deleted. Reload the page and try again. If the issue remains, contact our support please.', 'wpcf' ), false );
	}


	/**
	 * Get original language of the field.
	 */
	private function json_repeatable_group_field_original_translation() {
		$item_id = (int) toolset_getpost( 'repeatable_group_id' );
		$field_slug = sanitize_text_field( toolset_getpost( 'field_slug' ) );

		if ( $this->relationships_factory->database_operations()->requires_default_language_post() ) {
			$this->json_repeatable_group_field_original_translation_by_default_language( $item_id );

			return;
		}

		$preview_fetcher = new FetchFieldTranslationsPreview(
			$this->post_field_definition_factory,
			$this->wpml_service
		);

		$preview_markup = $preview_fetcher->get_translation_preview( $item_id, $field_slug );
		$this->get_ajax_manager()->ajax_finish( $this->field_translation_preview_reply( $preview_markup ) );
	}


	/**
	 * Format the field translation preview response based on its type, so that it's properly understood by rfg.js.
	 *
	 * @param string|array $value
	 *
	 * @return array
	 */
	private function field_translation_preview_reply( $value ) {
		if ( is_string( $value ) ) {
			return [
				'type' => 'raw_html',
				'payload' => $value,
			];
		}

		return [
			'type' => 'structured',
			'payload' => $value,
		];
	}


	/**
	 * Provide the original language translation of the requested RFG item custom field.
	 *
	 * @param int $rfg_id
	 */
	private function json_repeatable_group_field_original_translation_by_default_language( $rfg_id ) {
		$meta_key = sanitize_text_field( toolset_getpost( 'field_meta_key' ) );
		$original_meta = apply_filters( 'wpml_custom_field_original_data', null, $rfg_id, $meta_key );

		if ( ! isset( $original_meta['value'] ) ) {
			$this->get_ajax_manager()->ajax_finish( $this->field_translation_preview_reply(
				__( 'Error. The original value could not be loaded.', 'wpcf' )
			) );
		}

		$field_slug = strpos( $meta_key, 'wpcf-' ) === 0
			? substr( $meta_key, strlen( 'wpcf-' ) )
			: $meta_key;

		$this->post_field_definition_factory->load_all_definitions();
		$field_definition = $this->post_field_definition_factory->load_field_definition( $field_slug );

		$value = '';

		// checkboxes
		if ( $field_definition->get_type()->get_slug() === 'checkboxes' ) {
			$field_def_array = $field_definition->get_definition_array();
			foreach ( $field_def_array['data']['options'] as $option_slug => $option_data ) {
				$value .= isset( $original_meta['value'][ $option_slug ] )
				&& ! empty( $original_meta['value'][ $option_slug ] )
					? '<i class="fa fa-check-square-o"></i><br />'
					: '<i class="fa fa-square-o"></i><br />';
			}

			$this->get_ajax_manager()->ajax_finish( $this->field_translation_preview_reply( $value ) );
		}

		// checkbox
		if ( $field_definition->get_type()->get_slug() === 'checkbox' ) {
			$value = ! empty( $original_meta['value'] )
				? '<i class="fa fa-check-square-o"></i>'
				: '<i class="fa fa-square-o"></i>';

			$this->get_ajax_manager()->ajax_finish( $this->field_translation_preview_reply( $value ) );
		}

		// radio
		if ( $field_definition->get_type()->get_slug() === 'radio' ) {
			$field_def_array = $field_definition->get_definition_array();
			foreach ( $field_def_array['data']['options'] as $option_slug => $option_data ) {
				if ( 'default' === $option_slug ) {
					continue;
				}

				/** @noinspection TypeUnsafeComparisonInspection */
				$value .= $original_meta['value'] == $option_data['value']
					? '<i class="fa fa-dot-circle-o"></i><br />'
					: '<i class="fa fa-circle-o"></i><br />';
			}

			$this->get_ajax_manager()->ajax_finish( $this->field_translation_preview_reply( $value ) );
		}

		// radio
		if ( $field_definition->get_type()->get_slug() === 'select' ) {
			$field_def_array = $field_definition->get_definition_array();
			foreach ( $field_def_array['data']['options'] as $option_slug => $option_data ) {
				if ( 'default' === $option_slug ) {
					continue;
				}

				/** @noinspection TypeUnsafeComparisonInspection */
				if ( $original_meta['value'] == $option_data['value'] ) {
					$value = $option_data['title'];
					break;
				}
			}

			$this->get_ajax_manager()->ajax_finish( $this->field_translation_preview_reply( $value ) );
		}

		// date
		if ( $field_definition->get_type()->get_slug() === 'date' ) {
			$value = is_array( $original_meta ) && ! empty( $original_meta['value'] )
				? date( get_option( 'date_format' ), $original_meta['value'] )
				: __( 'The original value is empty.', 'wpcf' );

			$this->get_ajax_manager()->ajax_finish( $this->field_translation_preview_reply( $value ) );
		}

		// all others
		$value = is_array( $original_meta ) && ! empty( $original_meta['value'] )
			? nl2br( stripslashes( $original_meta['value'] ) )
			: __( 'The original value is empty.', 'wpcf' );

		$this->get_ajax_manager()->ajax_finish( $this->field_translation_preview_reply( $value ) );
	}


	/**
	 * Detects relationship between two slugs and returns the first found relationship definition
	 *
	 * @param string $parent_slug
	 * @param string $child_slug
	 *
	 * @param string $domain
	 *
	 * @return bool|IToolset_Relationship_Definition
	 * @noinspection PhpSameParameterValueInspection
	 */
	private function get_relationship_definition( $parent_slug, $child_slug, $domain = Toolset_Element_Domain::POSTS ) {
		do_action( 'toolset_do_m2m_full_init' );

		$relationship_query = new Toolset_Relationship_Query_V2();
		$relationship_query->do_not_add_default_conditions();
		$relationship_query->add( $relationship_query->has_domain_and_type( $child_slug, $domain,
			new Toolset_Relationship_Role_Child() ) );

		$definitions = $relationship_query->get_results();

		foreach ( $definitions as $definition ) {
			if ( ! in_array( $parent_slug, $definition->get_parent_type()->get_types(), true ) ) {
				continue;
			}

			return $definition;
		}

		return false;
	}


	/**
	 * Get if current language is the default language
	 *
	 * @return bool
	 */
	private function is_default_language_active() {
		if ( $this->_is_default_language_active === null ) {
			$this->_is_default_language_active = $this->wpml_service->get_current_language()
				=== $this->wpml_service->get_default_language();
		}

		return $this->_is_default_language_active;
	}


	/**
	 * Returns items of group
	 *
	 * @param WP_Post $parent_post
	 * @param Types_Field_Group_Repeatable $repeatable_group
	 * @param int|null $depth Number of nesting levels that should be loaded, or null to load everything.
	 *
	 * @return array
	 */
	private function get_rfg_items( WP_Post $parent_post, Types_Field_Group_Repeatable $repeatable_group, $depth ) {
		$items = array();

		foreach ( (array) $repeatable_group->get_posts() as $rfg_item ) {
			$items[] = $this->get_rfg_item( $rfg_item->get_wp_post(), $parent_post, $repeatable_group, $depth );
		}

		return $items;
	}


	/**
	 * Single item by item (post) id
	 *
	 * @param WP_Post $item_post
	 * @param WP_Post $parent_post
	 * @param Types_Field_Group_Repeatable $repeatable_group
	 * @param int|null $depth Number of nesting levels that should be loaded, or null to load everything.
	 *
	 * @return array
	 */
	private function get_rfg_item(
		WP_Post $item_post,
		WP_Post $parent_post,
		Types_Field_Group_Repeatable $repeatable_group,
		$depth = null
	) {
		$item = array(
			'id' => $item_post->ID,
			'title' => ( $item_post->post_title === (string) $item_post->ID ) ? '' : $item_post->post_title,
			'fields' => array(),
		);

		$next_depth = ( null === $depth ) ? null : $depth - 1;

		foreach ( $repeatable_group->get_field_slugs() as $field_slug ) {

			if ( $nested_repeatable_group = $this->rfg_service->get_object_from_prefixed_string( $field_slug, $item_post ) ) {
				// nested group
				$item['fields'][] = $this->format_rfg_for_response(
					$nested_repeatable_group, $parent_post, $item_post, $next_depth
				);
				continue;
			}

			// field
			$field = $this->format_rfg_field_for_response( $field_slug, $item_post->ID, $repeatable_group );
			if ( null !== $field ) {
				$item['fields'][] = $field;
			}
		}

		return $item;
	}


	/**
	 * Formats a repeatable field group to match the requirements of rfg.js
	 *
	 * @param Types_Field_Group_Repeatable $rfg
	 * @param WP_Post $belongs_to_post
	 * @param WP_Post $item
	 * @param int|null $depth Number of nesting levels that should be loaded, or null to load everything.
	 *
	 * @return array
	 */
	private function format_rfg_for_response(
		Types_Field_Group_Repeatable $rfg,
		WP_Post $belongs_to_post,
		WP_Post $item,
		$depth = null
	) {
		$is_max_depth_reached = ( 0 === $depth );

		// field conditions
		if ( ! $is_max_depth_reached ) {
			$items = $this->get_rfg_items( $item, $rfg, $depth );
		} else {
			$items = array();
		}
		$this->add_to_field_conditions_collection_by_items( $items );

		$parent_translation_mode = $this->wpml_service->get_post_type_translation_mode( $belongs_to_post->post_type );

		// return formated rfg
		return array(
			'repeatableGroup' => array(
				'id' => $rfg->get_id(),
				'parent_post_id' => $belongs_to_post->ID,
				'title' => $rfg->get_display_name(),
				'headlines' => $this->get_headlines_of_group( $rfg ),
				'controlsActive' => $this->should_controls_be_active( $parent_translation_mode ),
				'items' => $items,
				'wpmlFilterExistsForOriginalData' => class_exists( 'WPML_Custom_Fields_Post_Meta_Info' ),
				'wpmlIsDefaultLanguageActive' => $this->is_default_language_active(),
				'isTranslatable' => WpmlService::MODE_DONT_TRANSLATE !== $parent_translation_mode,
				'isPopulated' => ! $is_max_depth_reached,
			),
		);
	}


	/**
	 * Formats a field to match the requirements of rfg.js
	 *
	 * @param string $field_slug
	 * @param int $belongs_to_post_id
	 * @param Types_Field_Group_Repeatable $rfg
	 *
	 * @return array|null
	 */
	private function format_rfg_field_for_response(
		$field_slug,
		$belongs_to_post_id,
		Types_Field_Group_Repeatable $rfg
	) {
		$field_definition_service = Toolset_Field_Definition_Factory_Post::get_instance();
		$field_definition = $field_definition_service->load_field_definition( $field_slug );

		if ( ! $field_definition instanceof Toolset_Field_Definition ) {
			// Field definition does not exist: consider for example that Maps got deactivated.
			return null;
		}

		$field = $field_definition->instantiate( $belongs_to_post_id );

		// this is required to make WPML "Copy" "Copy once" work. The form manipulations are done by enlimbo
		// and this way we do not need to edit any existing code in Enlimbo.
		$_REQUEST['repeatable_group_item_post'] = get_post( $belongs_to_post_id );

		$wpml_is_copied = wpcf_wpml_field_is_copied( $field_definition->get_definition_array() );

		/** @var Toolset_Field_Renderer_Toolset_Forms_Repeatable_Group $renderer */
		$renderer = $field_definition
			->get_type()
			->get_renderer(
				Toolset_Field_Renderer_Purpose::INPUT_REPEATABLE_GROUP,
				RequestMode::ADMIN, $field,
				array( 'hide_field_title' => true )
			);

		$return = array(
			'title' => $field_definition->get_display_name(),
			'metaKey' => $field_definition->get_meta_key(),
			'slug' => $field_definition->get_slug(),
			'value' => $field->get_value(),
			'wpmlIsCopied' => $wpml_is_copied,
			'htmlInput' => $renderer->render( false, $rfg->get_wp_post()->ID ),
			'fieldConfig' => $renderer->get_field_config( $rfg->get_wp_post()->ID ),
		);

		$this->has_wysiwyg_field = ( $this->has_wysiwyg_field
			|| $field_definition->get_type_slug()
			=== Toolset_Field_Type_Definition_Factory::WYSIWYG );

		if ( defined( 'TOOLSET_TYPES_YOAST' ) && TOOLSET_TYPES_YOAST ) {
			$field_repository = new Repository(
				Toolset_Field_Group_Post_Factory::get_instance(),
				new \OTGS\Toolset\Types\Compatibility\Yoast\Field\Factory()
			);
			if ( $field_yoast = $field_repository->getFieldByDefinition( $field_definition, $belongs_to_post_id ) ) {
				$return['yoast'] = $field_yoast;
			}
		}

		return $return;
	}


	/**
	 * @param Types_Field_Group_Repeatable $group
	 *
	 * @return array
	 */
	private function get_headlines_of_group( $group ) {
		$headlines = array();
		$field_definition_service = Toolset_Field_Definition_Factory_Post::get_instance();

		foreach ( $group->get_field_slugs() as $field_slug ) {
			if ( $nested_repeatable_group = $this->rfg_service->get_object_from_prefixed_string( $field_slug, null,
				0 )
			) {
				$headlines[] = array( 'title' => $nested_repeatable_group->get_display_name() );
				continue;
			}

			$field_definition = $field_definition_service->load_field_definition( $field_slug );

			if ( null === $field_definition ) {
				// Field definition does not exist: consider for example that Maps got deactivated.
				continue;
			}

			if ( $rfg_items = $group->get_posts() ) {
				/** @var Types_Field_Group_Repeatable_Item $rfg_item */
				$rfg_item = reset( $rfg_items );
				$wpml_is_copied = wpcf_wpml_field_is_copied( $field_definition->get_definition_array(), $rfg_item->get_wp_post() );
			} else {
				$wpml_is_copied = false;
			}

			$headlines[] = [
				'title' => $field_definition->get_display_name(),
				'wpmlIsCopied' => $wpml_is_copied,
			];
		}

		return $headlines;
	}


	/**
	 * Store that the user don't want to see the RFG Item introduction anymore
	 */
	private function json_repeatable_group_item_title_introduction_dismiss() {
		Toolset_Admin_Notices_Manager::dismiss_notice_by_id( self::NOTICE_KEY_FOR_RFG_ITEM_INTRODUCTION );
	}


	/**
	 * Update RFG Item title
	 */
	private function json_repeatable_group_item_title_update() {
		$item = get_post( toolset_getpost( 'item_id', null ) );
		$item_title = toolset_getpost( 'item_title', null );
		if ( ! $item || $item_title === null ) {
			$this->get_ajax_manager()
				->ajax_finish( __( 'Technical issue. Please reload the page and try again.', 'wpcf' ), false );
		}

		$result = $this->rfg_service->update_item_title( $item, $item_title );

		if ( ! $result ) {
			$this->get_ajax_manager()
				->ajax_finish( __( 'Technical issue. Please reload the page and try again.', 'wpcf' ), false );
		}

		// update translation posts (the title is only editable on the default language)
		$translated_post_ids = $this->wpml_service->get_post_translations_directly( $item->ID );

		foreach ( $translated_post_ids as $translated_post_id ) {
			if ( $translated_post_id === $item->ID ) {
				// this is the original post, no need to update it again
				continue;
			}

			if ( ! $translated_post = get_post( $translated_post_id ) ) {
				// no post for $translated_post_id found
				continue;
			}

			// update the translation
			$this->rfg_service->update_item_title( $translated_post, $item_title );
		}

		// title updated
		$this->get_ajax_manager()->ajax_finish( 'Title updated.' );
	}


	/**
	 * If there has been a WYSIWYG field added during processing this AJAX call, append settings for the TinyMCE toolbar
	 * to the response array.
	 *
	 * @param array $response Associative array with the AJAX call response.
	 *
	 * @return array Modified response.
	 * @since 3.3.1
	 */
	private function maybe_add_tinymce_settings( $response ) {
		if ( $this->has_wysiwyg_field ) {
			$tinymce_helper = new Types_Helper_TinyMCE();
			$response['tinyMCEToolbarSettings'] = $tinymce_helper->get_toolbar_settings_for_dynamic_tinymce();
		}

		return $response;
	}
}
