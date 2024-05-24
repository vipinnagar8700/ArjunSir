<?php

namespace OTGS\Toolset\Types\Ajax\Handler\RepeatableFieldGroup;

use OTGS\Toolset\Common\Utils\RequestMode;
use OTGS\Toolset\Common\WPML\WpmlService;
use Toolset_Field_Definition_Factory_Post;
use Toolset_Field_Renderer_Purpose;

/**
 * Prepare data for the field translation preview tooltip in RFG items.
 *
 * @since 3.4
 */
class FetchFieldTranslationsPreview {

	/** @var Toolset_Field_Definition_Factory_Post */
	private $post_field_definition_factory;

	/** @var WpmlService */
	private $wpml_service;


	/**
	 * FetchFieldTranslationsPreview constructor.
	 *
	 * @param Toolset_Field_Definition_Factory_Post $post_field_definition_factory
	 * @param WpmlService $wpml_service
	 */
	public function __construct(
		Toolset_Field_Definition_Factory_Post $post_field_definition_factory,
		WpmlService $wpml_service
	) {
		$this->post_field_definition_factory = $post_field_definition_factory;
		$this->wpml_service = $wpml_service;
	}


	/**
	 * Build the translation preview and return it in a format that is accepted by
	 * Types.RepeatableGroup.TranslationPreview in rfg.js.
	 *
	 * Only translations where the field has some value are returned, and the results are sorted by language.
	 *
	 * @param int $item_id Post ID of the RFG item.
	 * @param string $field_slug Slug of the field to process.
	 *
	 * @return array|string String is returned when there are no translations, in which case it will be understood
	 *    and rendered as plain value in rfg.js.
	 */
	public function get_translation_preview( $item_id, $field_slug ) {
		$field_definition = $this->post_field_definition_factory->load_field_definition( $field_slug );

		$item_trid = $this->wpml_service->get_post_trid( $item_id );

		if ( ! $item_trid ) {
			return [];
		}

		$item_translations = $this->wpml_service->get_post_translations( $item_trid );

		$preview_data = [];
		foreach ( $item_translations as $lang_code => $translation_id ) {
			if ( $translation_id === $item_id ) {
				continue;
			}

			$field_instance = $field_definition->instantiate( $translation_id );
			$field_value_presentation = $field_instance->get_renderer(
				Toolset_Field_Renderer_Purpose::RFG_TRANSLATION_PREVIEW,
				RequestMode::ADMIN,
				[]
			)->render( false );

			if ( empty( $field_value_presentation ) ) {
				continue;
			}

			$preview_data[] = [
				'post_id' => $translation_id,
				'language_flag_url' => $this->wpml_service->get_language_flag_url( $translation_id ),
				'language_code' => $lang_code,
				'value' => $field_value_presentation,
			];
		}

		usort( $preview_data, function ( $a, $b ) {
			$lang_a = $a['language_code'];
			$lang_b = $b['language_code'];

			if ( $lang_a === $lang_b ) {
				return 0;
			}

			// Default language always goes first.
			if ( $lang_a === $this->wpml_service->get_default_language() ) {
				return - 1;
			}

			if ( $lang_b === $this->wpml_service->get_default_language() ) {
				return 1;
			}

			// Compare other language codes alphabetically.
			return strcmp( $lang_a, $lang_b );
		} );

		if ( empty( $preview_data ) ) {
			return __( 'No translation available.', 'wpcf' );
		}

		return [ 'translations' => $preview_data ];
	}


}
