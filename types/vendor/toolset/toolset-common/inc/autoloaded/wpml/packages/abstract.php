<?php

namespace OTGS\Toolset\Common\WPML\Package;

/**
 * WPML strings have to be registered using packages. This class allows to define packages for different Toolset sections.
 *
 * @see
 * @since 3.0.4
 */
abstract class TranslationPackage implements ITranslationPackage {

	/**
	 * WPML Package kind for Toolset
	 */
	const PACKAGE_KIND = 'Toolset';


	/**
	 * Returns package definition
	 *
	 * @see wpml_register_string
	 * @link https://wpml.org/documentation/support/string-package-translation/#recommended-workflow-for-registering-your-strings
	 */
	public function get_package_definition() {
		return array(
			'kind' => $this->get_package_kind(),
			'name' => $this->get_package_name(),
			'title' => $this->get_package_title(),
			'edit_link' => $this->get_package_edit_link(),
		);
	}

}
