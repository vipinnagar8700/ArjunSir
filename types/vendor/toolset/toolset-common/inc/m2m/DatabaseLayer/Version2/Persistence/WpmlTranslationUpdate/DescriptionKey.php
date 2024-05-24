<?php

namespace OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\Persistence\WpmlTranslationUpdate;

/**
 * Pseudo-enum for keys of the wpml_translation_update action arguments.
 *
 * @since 4.0
 */
class DescriptionKey {

	/**
	 * ID of the translation row in the icl_translations table.
	 */
	const TRANSLATION_ID = 'translation_id';

	/**
	 * ID of the affected element.
	 */
	const ELEMENT_ID = 'element_id';

	const ACTION_TYPE = 'type';

	/**
	 * May be 'post' or something else.
	 */
	const CONTEXT = 'context';

	/**
	 * Affected TRID. If the event is about a TRID change, this will hold the new TRID value.
	 */
	const TRID = 'trid';

	/**
	 * Element type from the icl_translations table.
	 */
	const ELEMENT_TYPE = 'element_type';

	/**
	 * Affected post type (post type slug, no post_ prefix).
	 */
	const POST_TYPE = 'post_type';
}
