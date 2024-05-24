<?php
namespace OTGS\Toolset\Common\Relationships\API;

/**
 * Available values for the "element_status" query condition.
 *
 * @since 4.0
 */
abstract class ElementStatusCondition {

	const STATUS_AVAILABLE = 'is_available';
	const STATUS_PUBLIC = 'is_public';
	const STATUS_ANY = 'any';

	// This is a special constant for my friend Juan!
	const STATUS_ANY_BUT_AUTODRAFT = 'any_but_autodraft';
}
