<?php

namespace OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\TableColumns;

/**
 * Holds column names of the icl_translations table defined in WPML.
 *
 * These values are expected to never change but we keep them as constant to allow for navigating
 * the codebase by semantics rather than by hardcoded values (like element_id) which can be
 * interpreted in numerous ways.
 *
 * @since 4.0
 */
final class IclTranslationsTable {

	const TRANSLATION_ID = 'translation_id';
	const ELEMENT_ID = 'element_id';
	const LANG_CODE = 'language_code';
	const TRID = 'trid';
	const ELEMENT_TYPE = 'element_type';
	const SOURCE_LANG_CODE = 'source_language_code';

	/** @var string The prefix for posts in the element_type column of the icl_translations table. */
	const POST_ELEMENT_TYPE_PREFIX = 'post_';

}
