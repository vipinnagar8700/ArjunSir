<?php

namespace OTGS\Toolset\Common\WPML\Package;

/**
 * Interface of the relationship definition.
 *
 * @since 3.0.4
 */
interface ITranslationPackage {

	/**
	 * Gets package kind
	 *
	 * @return string
	 */
	public function get_package_kind();


	/**
	 * Gets package name
	 *
	 * @return string
	 */
	public function get_package_name();


	/**
	 * Gets package name
	 *
	 * @return string
	 */
	public function get_package_title();


	/**
	 * Gets package name
	 *
	 * @return string
	 */
	public function get_package_edit_link();
}
