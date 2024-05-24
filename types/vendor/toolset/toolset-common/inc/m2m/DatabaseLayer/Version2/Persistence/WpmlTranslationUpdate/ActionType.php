<?php

namespace OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\Persistence\WpmlTranslationUpdate;

/**
 * Pseudo-enum for possible actions of the wpml_translation_update action.
 *
 * @since 4.0
 */
abstract class ActionType {

	const DELETE = 'delete';

	const UPDATE = 'update';

	const INSERT = 'insert';

	const BEFORE_DELETE = 'before_delete';

	const BEFORE_LANGUAGE_DELETE = 'before_language_delete';

	const RESET = 'reset';

	const INITIALIZE_LANGUAGE_FOR_POST_TYPE = 'initialize_language_for_post_type';
}
