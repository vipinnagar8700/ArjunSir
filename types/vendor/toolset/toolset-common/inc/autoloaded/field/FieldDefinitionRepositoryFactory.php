<?php

namespace OTGS\Toolset\Common\Field;

use Toolset_Field_Definition_Factory;

/**
 * Factory for providing instances of Toolset_Field_Definition_Factory implementations (which are,
 * actually, repositories of custom field definitions).
 *
 * @since Types 3.4.9
 * @codeCoverageIgnore
 */
class FieldDefinitionRepositoryFactory {

	/**
	 * @param string $domain
	 *
	 * @return Toolset_Field_Definition_Factory
	 */
	public function get_repository( $domain ) {
		/** @noinspection PhpDeprecationInspection */
		return Toolset_Field_Definition_Factory::get_factory_by_domain( $domain );
	}

}
