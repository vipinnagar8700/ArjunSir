<?php

namespace OTGS\Toolset\Common\Relationships\DatabaseLayer\Version1;

use IToolset_Relationship_Role;
use OTGS\Toolset\Common\Relationships\API\RelationshipRole;
use RuntimeException;
use Toolset_Condition_Plugin_Wpml_Is_Active_And_Configured;
use Toolset_Condition_Plugin_Wpml_Is_Current_Language_Default;
use Toolset_Relationship_Database_Unique_Table_Alias;
use Toolset_Relationship_Role;
use Toolset_WPML_Compatibility;

/**
 * Provider for the element selector.
 *
 * It creates the correct one depending on the state of WPML and the current language
 * and then keeps providing the same instance every time.
 *
 * Together with the restriction that condition classes must not use the element selector
 * in their constructor, this allows us to inject this dependency to query conditions
 * but wait until all conditions are instantiated before we decide which element selector
 * to actually use.
 *
 * @since 2.5.10
 */
class Toolset_Association_Query_Element_Selector_Provider {


	const FILTER_WPML_SELECTOR = 'toolset_association_query_use_wpml_element_selector';


	/** @var Toolset_Condition_Plugin_Wpml_Is_Active_And_Configured */
	private $is_wpml_active;


	/** @var Toolset_Condition_Plugin_Wpml_Is_Current_Language_Default */
	private $is_current_language_default;


	/** @var IToolset_Association_Query_Element_Selector|null */
	private $selector;


	/** @var bool */
	private $should_translate_elements = true;


	/** @var Toolset_WPML_Compatibility */
	private $wpml_service;


	/** @var string|null */
	private $translation_language;


	/** @var string[] Language codes indexed by element role names. */
	private $forced_languages_per_role = [];


	/**
	 * OTGS\Toolset\Common\Relationships\DatabaseLayer\Version1\Toolset_Association_Query_Element_Selector_Provider
	 * constructor.
	 *
	 * @param Toolset_Condition_Plugin_Wpml_Is_Active_And_Configured|null $is_wpml_active_di
	 * @param Toolset_Condition_Plugin_Wpml_Is_Current_Language_Default|null $is_current_language_default_di
	 * @param Toolset_WPML_Compatibility|null $wpml_service_di
	 */
	public function __construct(
		Toolset_Condition_Plugin_Wpml_Is_Active_And_Configured $is_wpml_active_di = null,
		Toolset_Condition_Plugin_Wpml_Is_Current_Language_Default $is_current_language_default_di = null,
		Toolset_WPML_Compatibility $wpml_service_di = null
	) {
		$this->is_wpml_active = ( null === $is_wpml_active_di
			? new Toolset_Condition_Plugin_Wpml_Is_Active_And_Configured() : $is_wpml_active_di );
		$this->is_current_language_default = ( null === $is_current_language_default_di
			? new Toolset_Condition_Plugin_Wpml_Is_Current_Language_Default() : $is_current_language_default_di );
		$this->wpml_service = $wpml_service_di ? : Toolset_WPML_Compatibility::get_instance();
	}


	/**
	 * Get the selector instance once it has been created.
	 *
	 * @return IToolset_Association_Query_Element_Selector|null
	 */
	public function get_selector() {
		return $this->selector;
	}


	/**
	 * Create an appropriate element selector.
	 *
	 * This can be called only once.
	 *
	 * @param Toolset_Relationship_Database_Unique_Table_Alias $table_alias
	 * @param Toolset_Association_Query_Table_Join_Manager $join_manager
	 * @param Toolset_Association_Query_V2 $query
	 * @param IToolset_Relationship_Role[] $unnecessary_wpml_table_joins
	 * @param bool $can_skip_intermediary_posts
	 *
	 * @return IToolset_Association_Query_Element_Selector
	 * @throws RuntimeException When trying to create the element selector for the second time.
	 */
	public function create_selector(
		Toolset_Relationship_Database_Unique_Table_Alias $table_alias,
		Toolset_Association_Query_Table_Join_Manager $join_manager,
		Toolset_Association_Query_V2 $query,
		array $unnecessary_wpml_table_joins,
		$can_skip_intermediary_posts
	) {
		if ( null !== $this->selector ) {
			throw new RuntimeException( 'Element selector for the association query has already been created.' );
		}

		$this->selector = $this->instantiate_selector(
			$table_alias, $join_manager, $query, $unnecessary_wpml_table_joins, $can_skip_intermediary_posts
		);

		if ( $can_skip_intermediary_posts ) {
			$this->selector->skip_intermediary_posts();
		}

		return $this->selector;
	}


	/**
	 * Set whether element translation should be attempted at all (by default, it is true).
	 *
	 * Setting this to false will completely ignore WPML when building the MySQL query.
	 *
	 * @param bool $should_translate
	 *
	 * @since 2.6.4
	 */
	public function attempt_translating_elements( $should_translate ) {
		$this->should_translate_elements = (bool) $should_translate;
	}


	/**
	 * @param Toolset_Relationship_Database_Unique_Table_Alias $table_alias
	 * @param Toolset_Association_Query_Table_Join_Manager $join_manager
	 * @param Toolset_Association_Query_V2 $query
	 * @param RelationshipRole[] $unnecessary_wpml_table_joins
	 * @param bool $can_skip_intermediary_posts
	 *
	 * @return IToolset_Association_Query_Element_Selector
	 */
	private function instantiate_selector(
		Toolset_Relationship_Database_Unique_Table_Alias $table_alias,
		Toolset_Association_Query_Table_Join_Manager $join_manager,
		Toolset_Association_Query_V2 $query,
		array $unnecessary_wpml_table_joins,
		$can_skip_intermediary_posts
	) {
		if (
			$this->should_translate_elements
			&& $this->is_wpml_active->is_met()
			&& ! $this->is_current_language_default->is_met()
		) {
			$use_wpml_selector = true;

			$unnecessary_wpml_table_joins = array_map( static function ( RelationshipRole $role ) {
				return $role->get_name();
			}, $unnecessary_wpml_table_joins );

			$roles_with_only_translated_posts = [];

			foreach ( $this->forced_languages_per_role as $role_name => $forced_language ) {
				if ( $this->wpml_service->get_default_language() === $forced_language ) {
					$unnecessary_wpml_table_joins[] = $role_name;
				} elseif ( $this->wpml_service->get_current_language() === $forced_language ) {
					// It is important that we only add roles where the current language is forced,
					// which is *different* from the default language.
					$roles_with_only_translated_posts[] = $role_name;
				}
			}
			$unnecessary_wpml_table_joins = array_unique( $unnecessary_wpml_table_joins );

			if (
				in_array( Toolset_Relationship_Role::PARENT, $unnecessary_wpml_table_joins, true )
				&& in_array( Toolset_Relationship_Role::CHILD, $unnecessary_wpml_table_joins, true )
				&& ( $can_skip_intermediary_posts
					|| in_array( Toolset_Relationship_Role::INTERMEDIARY, $unnecessary_wpml_table_joins, true ) )
			) {
				// We are sure that no elements in any relevant role will be translatable.
				$use_wpml_selector = false;
			}

			$use_wpml_selector = apply_filters( self::FILTER_WPML_SELECTOR, $use_wpml_selector, $query );

			if ( $use_wpml_selector ) {

				// Handle the special case of lang=all, probably using the manually set/approximated
				// translation language for the results.
				if ( $this->wpml_service->is_showing_all_languages() ) {
					return new Toolset_Association_Query_Element_Selector_Wpml_Lang_All(
						$table_alias, $join_manager, $this->translation_language, $unnecessary_wpml_table_joins
					);
				}

				return new Toolset_Association_Query_Element_Selector_Wpml( $table_alias, $join_manager, $unnecessary_wpml_table_joins, $roles_with_only_translated_posts );
			}
		}

		return new Toolset_Association_Query_Element_Selector_Default( $table_alias, $join_manager );
	}


	/**
	 * Set the translation language that may be used instead of the current language.
	 *
	 * @param string $lang_code Valid language code.
	 *
	 * @since 2.6.8
	 */
	public function set_translation_language( $lang_code ) {
		$this->translation_language = $lang_code;
	}


	/**
	 * Allow forcing a particular language for a given role.
	 *
	 * That means, only associations with translated posts will be used, and those without translations
	 * will be skipped from the results. Use with great caution.
	 *
	 * @param RelationshipRole $role
	 * @param string $lang_code Default language, current language or '*'.
	 */
	public function force_language_per_role( RelationshipRole $role, $lang_code ) {
		$this->forced_languages_per_role[ $role->get_name() ] = $lang_code;
	}

}
