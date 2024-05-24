<?php

/**
 * Class Types_Taxonomy
 *
 * FIXME please document this!
 */
class Types_Taxonomy {

	protected $wp_taxonomy;

	protected $name;

	protected $constants;

	public function __construct( $taxonomy, Toolset_Constants $constants = null ) {
		if( is_object( $taxonomy ) && isset( $taxonomy->name ) ) {
			$this->wp_taxonomy = $taxonomy;
			$this->name        = $taxonomy->name;
		} else {
			$this->name = $taxonomy;
			$registered = get_post_type_object( $taxonomy );

			if( $registered )
				$this->wp_taxonomy = $registered;
		}

		$this->constants = $constants ?: new Toolset_Constants();
	}

	public function __isset( $property ) {
		if( $this->wp_taxonomy === null )
			return false;

		if( ! property_exists( $this->wp_taxonomy, 'labels' ) )
			return false;

		if( ! property_exists( $this->wp_taxonomy->labels, $property ) )
			return false;

		return true;
	}

	public function __get( $property ) {
		if( ! $this->__isset( $property ) )
			return false;

		return $this->wp_taxonomy->labels->$property;
	}

	public function get_name() {
		return $this->name;
	}

	/**
	 * Get the backend edit link.
	 *
	 * @return string
	 * @since 2.1
	 */
	public function get_edit_link() {
		return admin_url() . 'admin.php?page=wpcf-edit-tax&wpcf-tax=' . $this->get_name();
	}

	/** @noinspection PhpUnused because this is used in Twig (tbody-row.twig) */
	/**
	 * Check if the taxonomy comes from a third-party source (it's neither built in nor from Types).
	 *
	 * @return bool
	 * @since 3.3.7
	 */
	public function is_third_party() {
		$result = (
			! $this->wp_taxonomy->_builtin
			&& ! in_array( $this->get_name(), array_keys( get_option( $this->constants->constant( 'WPCF_OPTION_NAME_CUSTOM_TAXONOMIES' ) ) ) )
		);

		return $result;
	}


}
