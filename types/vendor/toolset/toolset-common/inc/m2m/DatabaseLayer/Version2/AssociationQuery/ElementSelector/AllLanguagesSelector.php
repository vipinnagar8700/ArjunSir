<?php

namespace OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\AssociationQuery\ElementSelector;

use InvalidArgumentException;
use OTGS\Toolset\Common\Relationships\API\RelationshipRole;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\UniqueTableAlias;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\AssociationQuery\TableJoinManager;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\TableNames;
use OTGS\Toolset\Common\WPML\WpmlService;
use wpdb;

/**
 * Element selector that translates post elements and chooses the best element ID
 * when the current language is "all" (to display all content disregarding their language).
 *
 * This selector uses a specific provided language instead, or uses the default language.
 *
 * The association query class is responsible for determining the correct language code.
 */
class AllLanguagesSelector extends TranslatableElementSelector {


	/** @var string|null Lang code or null if not set. */
	private $translation_language;


	/**
	 * AllLanguagesSelector constructor.
	 *
	 * @param UniqueTableAlias $table_alias
	 * @param TableJoinManager $join_manager
	 * @param wpdb $wpdb
	 * @param WpmlService $wpml_service
	 * @param TableNames $table_names
	 * @param $unnecessary_wpml_table_joins
	 * @param bool $include_original_language
	 * @param string $translation_language
	 * @param bool $force_display_as_translated_mode
	 * @param RelationshipRole[] $roles_to_maybe_include_auto_drafts
	 * @param $post_type_constraints
	 */
	public function __construct(
		UniqueTableAlias $table_alias,
		TableJoinManager $join_manager,
		wpdb $wpdb,
		WpmlService $wpml_service,
		TableNames $table_names,
		$unnecessary_wpml_table_joins,
		$include_original_language,
		$translation_language,
		$force_display_as_translated_mode,
		$roles_to_maybe_include_auto_drafts,
		$post_type_constraints
	) {
		parent::__construct(
			$table_alias,
			$join_manager,
			$wpdb,
			$wpml_service,
			$table_names,
			$unnecessary_wpml_table_joins,
			$include_original_language,
			$force_display_as_translated_mode,
			$roles_to_maybe_include_auto_drafts,
			$post_type_constraints
		);

		if ( ! is_string( $translation_language ) ) {
			throw new InvalidArgumentException( 'Wrong value provided for the translation language.' );
		}

		$this->translation_language = $translation_language;
	}


	protected function get_translation_language() {
		if ( null === $this->translation_language || empty( $this->translation_language ) ) {
			$this->translation_language = $this->wpml_service->get_default_language();
		}

		return $this->translation_language;
	}

}
