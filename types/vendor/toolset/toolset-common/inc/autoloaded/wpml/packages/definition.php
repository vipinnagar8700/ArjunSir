<?php

namespace OTGS\Toolset\Common\WPML\Package;

/**
 * WPML strings have to be registered using packages. This class defines package for Toolset Definit.
 *
 * @see
 * @since 3.0.4
 */
class RelationshipDefinitionTranslationPackage extends TranslationPackage {


	/**
	 * Related Definition
	 *
	 * @var \Toolset_Relationship_Definition
	 * @since 3.0.4
	 */
	private $definition;

	/**
	 * Admin menu
	 *
	 * @var \Types_Admin_Menu
	 * @since 3.0.4
	 */
	private $admin_menu;

	/**
	 * Constants
	 *
	 * @var \Toolset_Constants
	 * @since 3.0.4
	 */
	private $constants;


	/**
	 * Constructor
	 *
	 * @param \Toolset_Relationship_Definition $definition
	 * @since 3.0.4
	 */
	public function __construct( \Toolset_Relationship_Definition $definition, \Types_Admin_Menu $admin_menu_di = null, \Toolset_Constants $constants_di = null ) {
		$this->definition = $definition;
		$this->admin_menu = $admin_menu_di;
		$this->constants = $constants_di ? : new \Toolset_Constants();
	}


	/**
	 * Gets package kind
	 *
	 * @return string
	 */
	public function get_package_kind() {
		return __( 'Toolset Types', 'wpv-views' );
	}


	/**
	 * Gets package name
	 *
	 * @return string
	 */
	public function get_package_name() {
		return $this->definition->get_slug();
	}


	/**
	 * Gets package name
	 *
	 * @return string
	 */
	public function get_package_title() {
		return sprintf( __( 'Relationship %s', 'wpv-views' ), $this->definition->get_display_name( false ) );
	}


	/**
	 * Gets package name
	 *
	 * @return string
	 */
	public function get_package_edit_link() {
		$admin_menu = $this->get_admin_menu();
		if ( ! $admin_menu ) {
			return '';
		}
		$relationships_page_url = $admin_menu->get_page_url( $this->constants->constant( '\Types_Admin_Menu::PAGE_NAME_RELATIONSHIPS' ) );
		return add_query_arg(
			array(
				'action' => 'edit',
				'slug' => $this->definition->get_slug(),
			),
			$relationships_page_url
		);
	}

	/**
	 * Gets admin menu
	 */
	private function get_admin_menu() {
		if ( ! $this->admin_menu && ! defined( 'TYPES_VERSION' ) ) {
			return null;
		}
		if ( ! $this->admin_menu ) {
			$this->admin_menu = \Types_Admin_Menu::get_instance();
		}
		return $this->admin_menu;
	}
}
