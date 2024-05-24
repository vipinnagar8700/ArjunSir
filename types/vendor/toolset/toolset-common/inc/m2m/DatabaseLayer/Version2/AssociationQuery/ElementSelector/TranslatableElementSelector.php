<?php

namespace OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\AssociationQuery\ElementSelector;

use InvalidArgumentException;
use OTGS\Toolset\Common\PostStatus;
use OTGS\Toolset\Common\Relationships\API\ElementIdentification;
use OTGS\Toolset\Common\Relationships\API\RelationshipRole;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\UniqueTableAlias;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\AssociationQuery\TableJoinManager;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\TableColumns\AssociationTable;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\TableColumns\ConnectedElementTable;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\TableColumns\IclTranslationsTable;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\TableNames;
use OTGS\Toolset\Common\WPML\WpmlService;
use Toolset_Relationship_Role;
use Toolset_Relationship_Role_Intermediary;
use Toolset_Utils;
use wpdb;

/**
 * Element selector that translates post elements and chooses the best element ID available.
 */
class TranslatableElementSelector extends AbstractSelector {


	/** @var string[] Prepared SELECT clauses to be used if needed. Indexed by role names. */
	private $select_clauses = array();


	/** @var string[] Prepared JOIN clauses to be used if needed. Indexed by role names. */
	private $join_clauses = array();


	/**
	 * @var string[] Aliases for original element IDs that will be used in the SELECT clause.
	 *     Indexed by role names.
	 */
	private $default_lang_element_id_select_aliases = array();


	/**
	 * @var string[] Unambiguous column names for original element IDs that can be used
	 *    within the rest of the MySQL query. Indexed by role names.
	 */
	private $default_lang_element_id_values = array();


	/**
	 * @var string[] Aliases for translated element IDs that will be used in the SELECT clause.
	 *     Indexed by role names.
	 */
	private $translated_element_id_select_aliases = array();


	/**
	 * @var string[] Expressions for translated element IDs that can be used
	 *    within the rest of the MySQL query. Indexed by role names.
	 */
	private $translated_element_id_values = array();


	/**
	 * @var string[] Aliases for original element IDs that will be used in the SELECT clause.
	 *     Indexed by role names.
	 */
	private $original_element_id_select_aliases = array();


	/**
	 * @var string[] Expressions for translated element IDs that can be used
	 *    within the rest of the MySQL query. Indexed by role names.
	 */
	private $original_element_id_values = array();


	/** @var string[] Role names where we don't need WPML tables because the results will not be translatable. */
	private $unnecessary_wpml_table_joins;


	/** @var RelationshipRole[] */
	private $requested_roles_in_join = array();


	/** @var bool */
	private $is_initialized = false;


	/** @var bool */
	private $include_original_language;


	/** @var bool */
	private $force_display_as_translated_mode;


	/** @var array Table and column names for element TRIDs (or null if not applicable), indexed by role names. */
	private $element_trid_values = [];

	/** @var RelationshipRole[] */
	private $roles_to_maybe_include_auto_drafts;

	/** @var string[] Post types indexed by role names. */
	private $post_type_constraints;


	// These constants are used in the element selection query.
	const DISPLAY_AS_TRANSLATED_VALUE = 1;

	const STANDARD_TRANSLATE_VALUE = 2;

	const NON_TRANSLATABLE_VALUE = 3;

	const AUTODRAFT_MODE_VALUE = 4;


	/**
	 * TranslatableElementSelector constructor.
	 *
	 * @param UniqueTableAlias $table_alias
	 * @param TableJoinManager $join_manager
	 * @param wpdb $wpdb
	 * @param WpmlService $wpml_service
	 * @param TableNames $table_names
	 * @param $unnecessary_wpml_table_joins
	 * @param bool $include_original_language
	 * @param bool $force_display_as_translated_mode
	 * @param RelationshipRole[] $roles_to_maybe_include_auto_drafts
	 * @param string[] $post_type_constraints
	 */
	public function __construct(
		UniqueTableAlias $table_alias,
		TableJoinManager $join_manager,
		wpdb $wpdb,
		WpmlService $wpml_service,
		TableNames $table_names,
		$unnecessary_wpml_table_joins,
		$include_original_language,
		$force_display_as_translated_mode,
		$roles_to_maybe_include_auto_drafts,
		$post_type_constraints
	) {
		parent::__construct( $table_alias, $join_manager, $wpdb, $wpml_service, $table_names );
		$this->unnecessary_wpml_table_joins = $unnecessary_wpml_table_joins;
		$this->include_original_language = (bool) $include_original_language;
		$this->force_display_as_translated_mode = $force_display_as_translated_mode;
		$this->roles_to_maybe_include_auto_drafts = $roles_to_maybe_include_auto_drafts;
		$this->post_type_constraints = $post_type_constraints;
	}


	public function initialize() {
		if ( $this->is_initialized ) {
			return;
		}

		parent::initialize();

		foreach ( Toolset_Relationship_Role::all() as $role ) {
			if ( $this->may_have_element_id_translated( $role ) ) {
				$this->configure_translatable_role( $role );
			} else {
				$this->configure_nontranslatable_role( $role );
			}
		}

		$this->is_initialized = true;
	}


	private function configure_nontranslatable_role( RelationshipRole $role ) {
		// Shortcuts
		//
		//
		$role_name = $role->get_name();

		$connected_elements_table = $this->table_names->get_full_table_name( TableNames::CONNECTED_ELEMENTS );
		$connected_elements_alias = $this->table_alias->generate( $connected_elements_table, true );
		$connected_elements_column_group_id = ConnectedElementTable::GROUP_ID;
		$connected_elements_column_element_id = ConnectedElementTable::ELEMENT_ID;

		$association_table_alias = TableJoinManager::ALIAS_ASSOCIATIONS;
		$association_table_role_group_id_column = AssociationTable::role_to_column( $role );

		$selected_element_id_alias = SelectedColumnAliases::role_to_name( $role );

		// Use directly the element_id from the connected elements table, since we know for sure
		// elements in this role won't be translatable.
		//
		//
		$join = ( $role_name === Toolset_Relationship_Role::INTERMEDIARY ? 'LEFT JOIN' : 'JOIN' );
		$this->join_clauses[ $role_name ] =
			"{$join} {$connected_elements_table} AS {$connected_elements_alias}
			ON ( {$connected_elements_alias}.{$connected_elements_column_group_id} = {$association_table_alias}.{$association_table_role_group_id_column} )";

		$this->select_clauses[ $role_name ] =
			"{$connected_elements_alias}.{$connected_elements_column_element_id} AS {$selected_element_id_alias}";

		$this->default_lang_element_id_select_aliases[ $role_name ] = $selected_element_id_alias;
		$this->default_lang_element_id_values[ $role_name ] = "{$connected_elements_alias}.{$connected_elements_column_element_id}";
		$this->element_trid_values[ $role_name ] = null;
	}


	private function configure_translatable_role( RelationshipRole $role ) {
		// Shortcuts (so that we can use them in double quotes later on).
		//
		//
		$role_name = $role->get_name();

		$connected_elements_table = $this->table_names->get_full_table_name( TableNames::CONNECTED_ELEMENTS );
		$icl_translations_table = $this->table_names->get_full_table_name( TableNames::ICL_TRANSLATIONS );

		$association_table_alias = TableJoinManager::ALIAS_ASSOCIATIONS;
		$association_table_role_group_id_column = AssociationTable::role_to_column( $role );

		$connected_elements_column_element_id = ConnectedElementTable::ELEMENT_ID;
		$connected_elements_column_group_id = ConnectedElementTable::GROUP_ID;
		$connected_elements_column_wpml_trid = ConnectedElementTable::WPML_TRID;

		$icl_translations_column_element_id = IclTranslationsTable::ELEMENT_ID;
		$icl_translations_column_trid = IclTranslationsTable::TRID;
		$icl_translations_column_lang_code = IclTranslationsTable::LANG_CODE;
		$icl_translations_source_lang_code = IclTranslationsTable::SOURCE_LANG_CODE;

		$subquery_alias = $role_name . '_translation';

		$selected_default_lang_id_alias = "default_lang_{$role_name}_id";
		$selected_translated_id_alias = "translated_{$role_name}_id";

		// In most cases, this will be the current language, but there are special cases,
		// like if displaying all languages - the get_translation_language() method may be overridden.
		$translation_language = esc_sql( $this->get_translation_language() );
		$default_language = esc_sql( $this->wpml_service->get_default_language() );

		$display_as_translated_value = self::DISPLAY_AS_TRANSLATED_VALUE;
		$non_translatable_value = self::NON_TRANSLATABLE_VALUE;
		$autodraft_mode_value = self::AUTODRAFT_MODE_VALUE;

		// Build the subquery. This produces a table with an element group_id (from the connected posts table)
		// and its default language and translation IDs. The translation defaults to the default language ID.
		//
		// Optionally, the original element ID is also included - it is supposed to always exist (that's a WPML
		// invariant) and that is useful in cases when we want to always have a result for each association,
		// even though there's no "right" element to display in the current context.
		//
		// Few things to consider here:
		//
		// - For each row, we need to take into account the translatability of given post type. That is what
		// "translation_mode" JOIN produced by get_translation_mode_subquery_join() is for. By default, it selects
		// any existing post from the translation group and determines the translation mode based on its post type
		// (we rely pretty strongly on the fact that all posts within one translation group have the same type).
		//
		// - We cannot use, for example, any TRIDs or their existence to determine post translatability, since
		// it is possible to have TRIDs stored while the CPT translation mode has been changed to non-translatable.
		//
		// - For non-translatable posts that still have TRIDs, we may be trying to join the icl_translations tables
		// unnecessarily, but that's probably better than building the JOIN on comparing the element_type against
		// a list of post types again (we can't use the "translation_mode" join because that creates a circular
		// dependency).
		//
		// - See the get_translation_mode_subquery_join() method for further optimization we apply here.
		//
		// - Now, when we have the translation mode, we choose:
		//     - As default language:
		//         - If the post is not translatable => element ID from the connected_elements table.
		//         - Otherwise (translatable) => default language ID (or NULL if it doesn't exist)
		//     - As translation (current language):
		//         - If the post is not translatable => element ID from the connected_elements table.
		//         - If the post is in the "display as translated mode"
		//             => current language ID if it exists, otherwise default language ID, otherwise NULL
		//         - Otherwise (standard translation mode) => current language ID (or NULL if it doesn't exist)
		//
		// - Note that the element ID from the connected elements table is used only for non-translatable posts.
		// That prevents falling back to a post ID whose language doesn't match the current context (e.g. we
		// don't want a secondary language post to show when listing associations in the default language, or
		// a different secondary language than a current one...).
		//
		// - There's also a fourth "translation mode" in our subquery for auto-draft posts. These don't have a TRID
		// assigned yet in the icl_translations table, even though they may belong to a translatable post type.
		// But if we already know to which translation group they should belong (their future TRID) and we query
		// related elements by that TRID, we need to select at least some post ID from that group. In this case,
		// we also use the element ID from the connected elements table as the last option.
		//
		// - Note that, unlike in most cases, we are joining the icl_translations tables for current and default
		// languages separately, not one based on the previous. That is necessary since either posts may or may not
		// be present independently.
		$original_language_select = '';
		$original_language_join = '';
		if ( $this->include_original_language ) {
			$original_language_join = " LEFT JOIN {$icl_translations_table} AS original ON (
				elements.{$connected_elements_column_wpml_trid} = original.{$icl_translations_column_trid}
				AND original.{$icl_translations_source_lang_code} IS NULL
			) ";

			$original_language_select = "
				IF (
					translation_mode.mode_value = {$non_translatable_value},
					elements.{$connected_elements_column_element_id},
					original.{$icl_translations_column_element_id}
				) AS original_element_id, ";
		}

		/** @noinspection SqlResolve */
		$subquery =
			" SELECT
    			IF (
    			    translation_mode.mode_value = {$non_translatable_value},
    			    elements.{$connected_elements_column_element_id},
    			    IF(
    			        translation_mode.mode_value = {$display_as_translated_value},
						COALESCE (
							translation.{$icl_translations_column_element_id},
							default_lang.{$icl_translations_column_element_id}
						),
						IF(
							translation_mode.mode_value = {$autodraft_mode_value},
							COALESCE (
								translation.{$icl_translations_column_element_id},
								default_lang.{$icl_translations_column_element_id},
								elements.{$connected_elements_column_element_id}
							),
							translation.{$icl_translations_column_element_id}
						)
    			    )
  				) AS translated_element_id,
    			IF (
    			    translation_mode.mode_value = {$non_translatable_value},
    			    elements.{$connected_elements_column_element_id},
    			    default_lang.{$icl_translations_column_element_id}
    			) AS default_lang_element_id,
     			{$original_language_select}
				elements.{$connected_elements_column_group_id} AS group_id,
 				IF (
 				    translation_mode.mode_value != {$non_translatable_value},
 				    elements.{$connected_elements_column_wpml_trid},
 				    NULL
 				) AS element_trid
			FROM {$connected_elements_table} AS elements
				LEFT JOIN {$icl_translations_table} AS default_lang
					ON (
					    elements.{$connected_elements_column_wpml_trid} = default_lang.{$icl_translations_column_trid}
					    AND default_lang.{$icl_translations_column_lang_code} = '{$default_language}'
					)
				LEFT JOIN {$icl_translations_table} AS translation
					ON (
					    elements.{$connected_elements_column_wpml_trid} = translation.{$icl_translations_column_trid}
					    AND translation.{$icl_translations_column_lang_code} = '{$translation_language}'
					)
				{$original_language_join}
			    {$this->get_translation_mode_subquery_join( $role )}
				";

		// Store all information we've just built for this role, so that it can be used when necessary.
		//
		//
		$join_statement = ( $role instanceof Toolset_Relationship_Role_Intermediary ? 'LEFT JOIN' : 'JOIN' );
		$this->join_clauses[ $role_name ] =
			"{$join_statement} ( {$subquery} ) AS {$subquery_alias}
			ON ( {$association_table_alias}.{$association_table_role_group_id_column} = {$subquery_alias}.group_id )";

		$this->select_clauses[ $role_name ] =
			"{$subquery_alias}.default_lang_element_id AS {$selected_default_lang_id_alias},
			{$subquery_alias}.translated_element_id AS {$selected_translated_id_alias}";

		$this->default_lang_element_id_select_aliases[ $role_name ] = $selected_default_lang_id_alias;
		$this->default_lang_element_id_values[ $role_name ] = "{$subquery_alias}.default_lang_element_id";

		$this->translated_element_id_select_aliases[ $role_name ] = $selected_translated_id_alias;
		$this->translated_element_id_values[ $role_name ] = "{$subquery_alias}.translated_element_id";

		$this->element_trid_values[ $role_name ] = "{$subquery_alias}.element_trid";

		if ( $this->include_original_language ) {
			$selected_original_id_alias = "original_{$role_name}_id";
			$this->original_element_id_select_aliases[ $role_name ] = $selected_original_id_alias;
			$this->original_element_id_values[ $role_name ] = "{$subquery_alias}.original_element_id";
			$this->select_clauses[ $role_name ] .= ",
				 {$subquery_alias}.original_element_id AS {$selected_original_id_alias}";
		}
	}


	/**
	 * Obtain the subquery that will give us the translation mode value for any post.
	 *
	 * Note that if we know the post type constriction in this role and/or if we don't need to include
	 * auto-draft posts here, we can optimize the subquery. When both of these facts are combined,
	 * the problem becomes trivial and we just use the translation mode of the post type.
	 *
	 * @param RelationshipRole $role
	 *
	 * @return string
	 */
	private function get_translation_mode_subquery_join( RelationshipRole $role ) {
		// Constants for a more readable query below.
		$status_autodraft = PostStatus::AUTODRAFT;

		$display_as_translated_value = self::DISPLAY_AS_TRANSLATED_VALUE;
		$standard_translate_value = self::STANDARD_TRANSLATE_VALUE;
		$non_translatable_value = self::NON_TRANSLATABLE_VALUE;
		$autodraft_mode_value = self::AUTODRAFT_MODE_VALUE;

		$connected_elements_column_element_id = ConnectedElementTable::ELEMENT_ID;
		$icl_translations_column_element_id = IclTranslationsTable::ELEMENT_ID;

		$can_skip_autodraft = ! $role->is_in_array( $this->roles_to_maybe_include_auto_drafts );

		$has_post_type_constraint = array_key_exists( $role->get_name(), $this->post_type_constraints )
			&& null !== $this->post_type_constraints[ $role->get_name() ];

		if ( $has_post_type_constraint ) {
			// So, we know the one post type we're dealing with in this role. Let's figure out its translation mode.
			$post_type = esc_sql( $this->post_type_constraints[ $role->get_name() ] );
			switch ( $this->wpml_service->get_post_type_translation_mode( $post_type ) ) {
				case WpmlService::MODE_DONT_TRANSLATE:
					$translation_mode_value = $non_translatable_value;
					break;
				case WpmlService::MODE_DISPLAY_AS_TRANSLATED:
					$translation_mode_value = $display_as_translated_value;
					break;
				case WpmlService::MODE_TRANSLATE:
				default:
					if ( $this->force_display_as_translated_mode ) {
						$translation_mode_value = $display_as_translated_value;
						break;
					}
					$translation_mode_value = $standard_translate_value;
					break;
			}

			if ( $can_skip_autodraft ) {
				// If we can ALSO skip auto-draft posts, the problem has a trivial solution, just use the
				// same translation mode everywhere.
				return "JOIN ( SELECT {$translation_mode_value} as mode_value ) AS translation_mode ON ( TRUE )";
			}

			// We know the post type but we - sadly - need to take auto-drafts into account.
			// Limit the query by post type, which will reduce the size of the JOIN when the subquery is inserted into
			// the parent query.
			return "
				LEFT JOIN (
					SELECT
						any_post.ID as post_id,
						IF(
							any_post.post_status LIKE '{$status_autodraft}',
							{$autodraft_mode_value},
							{$translation_mode_value}
						) AS mode_value
						FROM {$this->wpdb->posts} AS any_post
						WHERE any_post.post_type = '{$post_type}'
					) AS translation_mode ON (
						translation_mode.post_id = IFNULL(
							elements.{$connected_elements_column_element_id},
							IFNULL(
								translation.{$icl_translations_column_element_id},
								default_lang.{$icl_translations_column_element_id}
							)
						)
					)";
		}

		// Unknown post type - we have to determine the translation mode on-the-fly for each post.

		// Prepare a list of post types by their translation mode.
		$post_types_by_translatability = $this->wpml_service->get_translation_modes_for_all_post_types();
		$in_display_as_translated_element_types = $this->get_in_display_as_translated_element_types( $post_types_by_translatability );
		$in_standard_translate_element_types = $this->get_in_standard_translate_element_types( $post_types_by_translatability );

		if ( $can_skip_autodraft ) {
			// If we can skip auto-draft posts, the query will at least become a bit simpler.
			$mode_value = "
				IF(
					any_post.post_type IN ( {$in_display_as_translated_element_types} ),
					{$display_as_translated_value},
					IF(
						any_post.post_type IN ( {$in_standard_translate_element_types} ),
						{$standard_translate_value},
						{$non_translatable_value}
					)
				)";
		} else {
			// Worst-case scenario: Unknown post types and request for auto-draft posts.
			$mode_value = "
				IF(
					any_post.post_status LIKE '{$status_autodraft}',
					{$autodraft_mode_value},
					IF(
						any_post.post_type IN ( {$in_display_as_translated_element_types} ),
						{$display_as_translated_value},
						IF(
							any_post.post_type IN ( {$in_standard_translate_element_types} ),
							{$standard_translate_value},
							{$non_translatable_value}
						)
					)
				)";
		}

		return "
			LEFT JOIN (
				SELECT
					any_post.ID as post_id,
					{$mode_value} AS mode_value
					FROM {$this->wpdb->posts} AS any_post
				) AS translation_mode ON (
					translation_mode.post_id = IFNULL(
						elements.{$connected_elements_column_element_id},
						IFNULL(
							translation.{$icl_translations_column_element_id},
							default_lang.{$icl_translations_column_element_id}
						)
					)
				)";
	}


	/**
	 * Prepare part of the query for slugs of post types that are in the "display as translated" mode.
	 * If this mode is forced, all translatable post types will be included.
	 *
	 * @param string[] $post_types_by_translatability
	 *
	 * @return string Content of the MySQL IN ( ... ) expression.
	 */
	private function get_in_display_as_translated_element_types( $post_types_by_translatability ) {
		if ( $this->force_display_as_translated_mode ) {
			return $this->filter_and_format_post_types_by_translatability(
				$post_types_by_translatability,
				static function ( $value ) {
					return WpmlService::MODE_DONT_TRANSLATE !== $value;
				}
			);
		}

		return $this->filter_and_format_post_types_by_translatability(
			$post_types_by_translatability,
			static function ( $value ) {
				return WpmlService::MODE_DISPLAY_AS_TRANSLATED === $value;
			}
		);
	}


	/**
	 * Prepare part of the query for slugs of post types that are in the "show only translated items" mode.
	 * If the "display as translated" mode is forced, this will produce an empty result.
	 *
	 * @param string[] $post_types_by_translatability
	 *
	 * @return string Content of the MySQL IN ( ... ) expression.
	 */
	private function get_in_standard_translate_element_types( $post_types_by_translatability ) {
		if ( $this->force_display_as_translated_mode ) {
			return 'NULL';
		}

		return $this->filter_and_format_post_types_by_translatability(
			$post_types_by_translatability,
			static function ( $value ) {
				return WpmlService::MODE_TRANSLATE === $value;
			}
		);
	}


	private function filter_and_format_post_types_by_translatability(
		array $post_types_by_translatability, callable $filter
	) {
		$filtered_post_types = array_keys(
			array_filter( $post_types_by_translatability, static function ( $value ) use ( $filter ) {
				return $filter( $value );
			} )
		);

		return empty( $filtered_post_types )
			? 'NULL'
			: Toolset_Utils::prepare_mysql_in( $filtered_post_types );
	}


	/**
	 * @inheritdoc
	 *
	 * @param RelationshipRole $role
	 */
	public function request_element_in_results( RelationshipRole $role ) {
		parent::request_element_in_results( $role );

		// Make sure that requested elements in results are superset of those requested in JOINs.
		$this->request_element_in_join_only( $role );
	}


	/**
	 * @param RelationshipRole $role
	 */
	private function request_element_in_join_only( RelationshipRole $role ) {
		$this->requested_roles_in_join[ $role->get_name() ] = $role;
	}


	/**
	 * @inheritDoc
	 */
	public function may_have_element_id_translated( RelationshipRole $role ) {
		return ! in_array( $role->get_name(), $this->unnecessary_wpml_table_joins, true );
	}


	/**
	 * @inheritDoc
	 */
	public function get_element_id_value( RelationshipRole $for_role, $which_element = ElementIdentification::CURRENT_LANGUAGE_IF_POSSIBLE ) {
		$this->initialize();

		// The element value is used only within the query itself, but not within the SELECT clause.
		$this->request_element_in_join_only( $for_role );

		$which_element = $this->sanitize_and_validate_which_element( $which_element );

		if (
			ElementIdentification::DEFAULT_LANGUAGE === $which_element
			|| ! $this->may_have_element_id_translated( $for_role )
		) {
			return $this->default_lang_element_id_values[ $for_role->get_name() ];
		}

		if ( ElementIdentification::CURRENT_LANGUAGE_IF_POSSIBLE === $which_element ) {
			return $this->translated_element_id_values[ $for_role->get_name() ];
		}

		return $this->original_element_id_values[ $for_role->get_name() ];
	}


	/**
	 * @inheritDoc
	 */
	public function get_select_clauses() {
		$this->initialize();

		$requested_select_clauses = $this->maybe_get_association_and_relationship();
		foreach ( $this->requested_roles as $role ) {
			$requested_select_clauses[] = $this->select_clauses[ $role->get_name() ];
		}

		return ' ' . implode( ', ', $requested_select_clauses ) . ' ';
	}


	/**
	 * @inheritDoc
	 */
	public function get_join_clauses() {
		$this->initialize();

		$requested_join_clauses = array();
		foreach ( $this->requested_roles_in_join as $role ) {
			$requested_join_clauses[] = $this->join_clauses[ $role->get_name() ];
		}

		return ' ' . implode( ' ', $requested_join_clauses ) . ' ';
	}


	/**
	 * Get the language that will be used for the query results (besides the default language).
	 *
	 * @return string
	 */
	protected function get_translation_language() {
		return $this->wpml_service->get_current_language();
	}


	/**
	 * @inheritDoc
	 */
	public function get_element_id_alias( RelationshipRole $for_role, $which_element = ElementIdentification::CURRENT_LANGUAGE_IF_POSSIBLE ) {
		$this->initialize();
		$this->request_element_in_results( $for_role );

		$which_element = $this->sanitize_and_validate_which_element( $which_element );

		if (
			ElementIdentification::DEFAULT_LANGUAGE === $which_element
			|| ! $this->may_have_element_id_translated( $for_role )
		) {
			return $this->default_lang_element_id_select_aliases[ $for_role->get_name() ];
		}

		if ( ElementIdentification::CURRENT_LANGUAGE_IF_POSSIBLE === $which_element ) {
			return $this->translated_element_id_select_aliases[ $for_role->get_name() ];
		}

		if ( array_key_exists( $for_role->get_name(), $this->original_element_id_select_aliases ) ) {
			return $this->original_element_id_select_aliases[ $for_role->get_name() ];
		}

		return null;
	}


	/**
	 * @param bool|string $which_element
	 *
	 * @return string One of the ElementIdentification values.
	 * @throws InvalidArgumentException
	 */
	private function sanitize_and_validate_which_element( $which_element ) {
		if ( in_array( $which_element, ElementIdentification::all(), true ) ) {
			return $which_element;
		}

		if ( is_bool( $which_element ) || in_array( $which_element, [ 0, 1 ], true ) ) {
			return $which_element
				? ElementIdentification::CURRENT_LANGUAGE_IF_POSSIBLE
				: ElementIdentification::DEFAULT_LANGUAGE;
		}

		// @codeCoverageIgnoreStart
		throw new InvalidArgumentException( 'Wrong value for the which_element parameter.' );
		// @codeCoverageIgnoreEnd
	}


	/**
	 * @inheritDoc
	 */
	public function get_element_trid_value( RelationshipRole $for_role ) {
		$this->initialize();
		$this->request_element_in_join_only( $for_role );

		return $this->element_trid_values[ $for_role->get_name() ];
	}


}
