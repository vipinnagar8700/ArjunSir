<?php

namespace OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\AssociationQuery\ElementSelector;

use OTGS\Toolset\Common\Relationships\API\RelationshipRole;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\UniqueTableAlias;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\AssociationQuery\TableJoinManager;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\TableNames;
use OTGS\Toolset\Common\WPML\WpmlService;
use RuntimeException;
use Toolset_Condition_Plugin_Wpml_Is_Active_And_Configured;
use Toolset_Relationship_Role;
use Toolset_Relationship_Role_Intermediary;
use wpdb;

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
 * @since 4.0
 */
class ElementSelectorProvider {

	/** @var ElementSelectorInterface */
	private $selector;

	/** @var string|null */
	private $translation_language;

	/** @var bool */
	private $should_translate_elements = true;

	/** @var WpmlService */
	private $wpml_service;

	/** @var Toolset_Condition_Plugin_Wpml_Is_Active_And_Configured */
	private $is_wpml_active;

	/** @var wpdb */
	private $wpdb;

	/** @var TableNames */
	private $table_names;

	private $include_original_language = false;

	/** @var bool */
	private $force_display_as_translated_mode = false;


	/**
	 * @param Toolset_Condition_Plugin_Wpml_Is_Active_And_Configured $is_wpml_active
	 * @param WpmlService $wpml_service
	 * @param wpdb $wpdb
	 * @param TableNames $table_names
	 */
	public function __construct(
		Toolset_Condition_Plugin_Wpml_Is_Active_And_Configured $is_wpml_active,
		WpmlService $wpml_service,
		wpdb $wpdb,
		TableNames $table_names
	) {
		$this->is_wpml_active = $is_wpml_active;
		$this->wpml_service = $wpml_service;
		$this->wpdb = $wpdb;
		$this->table_names = $table_names;
	}

	/**
	 * Get the selector instance once it has been created.
	 *
	 * @return ElementSelectorInterface
	 * @throws RuntimeException
	 */
	public function get_selector() {
		if ( null === $this->selector ) {
			throw new RuntimeException( 'Element selector requested too early.' );
		}
		return $this->selector;
	}


	/**
	 * Set the translation language that may be used instead of the current language.
	 *
	 * @param string $lang_code Valid language code.
	 */
	public function set_translation_language( $lang_code ) {
		$this->translation_language = $lang_code;
	}


	/**
	 * Create an appropriate element selector.
	 *
	 * This can be called only once.
	 *
	 * @param UniqueTableAlias $table_alias
	 * @param TableJoinManager $join_manager
	 *
	 * @param array $unnecessary_wpml_table_joins
	 * @param bool $can_skip_intermediary_posts
	 * @param RelationshipRole[] $roles_to_maybe_include_auto_drafts
	 * @param string[] $post_type_constraints
	 *
	 * @return ElementSelectorInterface
	 */
	public function create_selector(
		UniqueTableAlias $table_alias,
		TableJoinManager $join_manager,
		array $unnecessary_wpml_table_joins,
		$can_skip_intermediary_posts,
		$roles_to_maybe_include_auto_drafts,
		$post_type_constraints
	) {
		if ( null !== $this->selector ) {
			throw new RuntimeException( 'Element selector for the association query has already been created.' );
		}

		$this->selector = $this->instantiate_selector(
			$table_alias, $join_manager, $unnecessary_wpml_table_joins, $can_skip_intermediary_posts,
			$roles_to_maybe_include_auto_drafts, $post_type_constraints
		);

		if ( $can_skip_intermediary_posts ) {
			$this->selector->skip_intermediary_posts();
		}

		return $this->selector;
	}


	/**
	 * @param UniqueTableAlias $table_alias
	 * @param TableJoinManager $join_manager
	 * @param RelationshipRole[] $unnecessary_wpml_table_joins
	 * @param bool $can_skip_intermediary_posts
	 * @param RelationshipRole[] $roles_to_maybe_include_auto_drafts
	 * @param string[] $post_type_constraints
	 *
	 * @return ElementSelectorInterface
	 */
	private function instantiate_selector(
		UniqueTableAlias $table_alias,
		TableJoinManager $join_manager,
		array $unnecessary_wpml_table_joins,
		$can_skip_intermediary_posts,
		$roles_to_maybe_include_auto_drafts,
		$post_type_constraints
	) {
		// Note: If the default language is selected, the TranslatableElementSelector still needs to be used,
		// since the connected elements table contains *an* element ID, not necessarily the default language
		// ID. So, as soon as we have a translatable role and an element group with a TRID assigned,
		// the element ID in that table means nothing and we do need to translate it to the default language.
		if (
			$this->should_translate_elements
			&& $this->is_wpml_active->is_met()
		) {
			$use_wpml_selector = true;

			// Retrieve names of roles where WPML tables are not important.
			if( $can_skip_intermediary_posts ) {
				$unnecessary_wpml_table_joins[] = new Toolset_Relationship_Role_Intermediary();
			}

			$unnecessary_wpml_table_joins = array_unique(
				array_map(
					static function ( RelationshipRole $role ) {
						return $role->get_name();
					},
					$unnecessary_wpml_table_joins
				)
			);

			// Don't use a WPML selector at all if all three roles certainly don't require post translations.
			if ( count( $unnecessary_wpml_table_joins ) === count( Toolset_Relationship_Role::all_role_names() ) ) {
				// We are sure that no elements in any relevant role will be translatable.
				$use_wpml_selector = false;
			}

			if ( $use_wpml_selector ) {
				// Handle the special case of lang=all, probably using the manually set/approximated
				// translation language for the results.
				if ( $this->wpml_service->is_showing_all_languages() ) {
					return new AllLanguagesSelector(
						$table_alias,
						$join_manager,
						$this->wpdb,
						$this->wpml_service,
						$this->table_names,
						$unnecessary_wpml_table_joins,
						$this->include_original_language,
						$this->translation_language,
						$this->force_display_as_translated_mode,
						$roles_to_maybe_include_auto_drafts,
						$post_type_constraints
					);
				}

				return new TranslatableElementSelector(
					$table_alias,
					$join_manager,
					$this->wpdb,
					$this->wpml_service,
					$this->table_names,
					$unnecessary_wpml_table_joins,
					$this->include_original_language,
					$this->force_display_as_translated_mode,
					$roles_to_maybe_include_auto_drafts,
					$post_type_constraints
				);
			}
		}

		return new DefaultSelector(
			$table_alias,
			$join_manager,
			$this->wpdb,
			$this->wpml_service,
			$this->table_names
		);
	}


	/**
	 * Set whether element translation should be attempted at all (by default, it is true).
	 *
	 * Setting this to false will completely ignore WPML when building the MySQL query.
	 *
	 * @param bool $should_translate
	 */
	public function attempt_translating_elements( $should_translate ) {
		$this->should_translate_elements = (bool) $should_translate;
	}


	public function include_original_language( $include = true ) {
		$this->include_original_language = (bool) $include;
	}


	/**
	 * See AssociationQuery::force_display_as_translated_mode().
	 *
	 * @param bool $do_force
	 */
	public function force_display_as_translated_mode( $do_force = true ) {
		$this->force_display_as_translated_mode = (bool) $do_force;
	}

}
