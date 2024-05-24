<?php

namespace OTGS\Toolset\Types\Controller\Cache\ShortcodeGenerator;

use OTGS\Toolset\Types\Model\Wordpress\Transient;
use RuntimeException;
use Toolset_Element_Domain;
use Toolset_Field_Definition;
use Toolset_Field_Definition_Factory;
use Toolset_Field_Definition_Factory_Post;
use Toolset_Field_Definition_Factory_Term;
use Toolset_Field_Definition_Factory_User;
use Toolset_Field_Group;
use Toolset_Field_Group_Factory;
use Toolset_Field_Group_Post;
use Types_Field_Group_Repeatable;
use Types_Field_Group_Repeatable_Service;

/**
 * Cache manager.
 *
 * @since 3.3.6
 */
abstract class ManagerBase {

	/**
	 * @var Transient
	 */
	protected $transient_manager;

	/**
	 * @var Types_Field_Group_Repeatable_Service
	 */
	protected $repeatable_group_service;

	/**
	 * Array to store the cache as we populate it, when a regeneration is needed.
	 *
	 * @var array
	 */
	protected $cache = array();


	/**
	 * Constructor
	 *
	 * @param Transient $transient_manager
	 * @param Types_Field_Group_Repeatable_Service $repeatable_group_service
	 */
	public function __construct(
		Transient $transient_manager,
		Types_Field_Group_Repeatable_Service $repeatable_group_service
	) {
		$this->transient_manager = $transient_manager;
		$this->repeatable_group_service = $repeatable_group_service;
	}


	/**
	 * Initialize this controller.
	 *
	 * @since 3.3.6
	 */
	public function initialize() {
		$this->add_hooks();
	}


	/**
	 * Register API hooks to get or delete the cache.
	 *
	 * @since 3.3.6
	 */
	protected function add_hooks() {
		add_filter( 'types_get_sg_' . $this->get_domain() . '_meta_cache', array(
			$this,
			'get_or_generate_cache',
		), 10, 2 );
		add_action( 'types_delete_sg_' . $this->get_domain() . '_meta_cache', array( $this, 'delete_cache' ) );
	}


	/**
	 * Get domain.
	 *
	 * @return string
	 * @since 3.3.6
	 */
	abstract protected function get_domain();


	/**
	 * Get the key for the transient cache.
	 *
	 * @return string
	 * @since 3.3.6
	 */
	abstract protected function get_transient_key();


	/**
	 * Get the attribute that identifies the meta key in the to-be-generated shortcode.
	 *
	 * @return string
	 * @since 3.3.6
	 */
	abstract protected function get_shortcode_meta_attribute();


	/**
	 * Get the cache.
	 *
	 * @return mixed|array
	 * @since 3.3.6
	 */
	public function get_cache() {
		return $this->transient_manager->get_transient( $this->get_transient_key() );
	}


	/**
	 * Get the existing or generate a new cache.
	 *
	 * @param array $dummy
	 *
	 * @return array
	 * @since 3.3.6
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function get_or_generate_cache( $dummy = array() ) {
		$cache = $this->get_cache();

		if ( false !== $cache ) {
			return $cache;
		}

		return $this->generate_cache();
	}


	/**
	 * Get the target of the fields to be registered in the shortcodes GUI API:
	 * - post fields for post targets.
	 * - term fields on term targets, and WPAs editors.
	 * - user fields on post and user targets.
	 *
	 * @return array
	 * @since 3.3.6
	 */
	protected function get_target() {
		$target = array( 'posts' );
		$domain = $this->get_domain();

		switch ( $domain ) {
			case Toolset_Element_Domain::POSTS:
				$target = array( 'posts' );
				break;
			case Toolset_Element_Domain::TERMS:
				$target = array( 'taxonomy' );
				global $pagenow;
				if (
					'admin.php' === $pagenow
					&& 'view-archives-editor' === toolset_getget( 'page' )
				) {
					// On WPA edit pages, we can insert also termmeta fields even if the target is posts,
					// because [types] shortcodes do support termmeta fields output on term archives.
					$target = array( 'posts' );
				}
				break;
			case Toolset_Element_Domain::USERS:
				$target = array( 'posts', 'users' );
				break;
		}

		return $target;
	}


	/**
	 * Get Types meta groups.
	 *
	 * @return Toolset_Field_Group[]
	 * @since 3.3.6
	 */
	protected function get_meta_groups() {
		$meta_groups = array();
		$domain = $this->get_domain();

		$group_factory = Toolset_Field_Group_Factory::get_factory_by_domain( $domain );

		switch ( $domain ) {
			case Toolset_Element_Domain::POSTS:
				$meta_groups = $group_factory->query_groups();
				$meta_groups_for_intermediary = $group_factory->query_groups( array( 'purpose' => Toolset_Field_Group_Post::PURPOSE_FOR_INTERMEDIARY_POSTS ) );
				$meta_groups = array_merge( $meta_groups, $meta_groups_for_intermediary );
				break;
			case Toolset_Element_Domain::USERS:
			case Toolset_Element_Domain::TERMS:
				$meta_groups = $group_factory->query_groups();
				break;
		}

		return $meta_groups;
	}


	/**
	 * Get the fields definition factory.
	 *
	 * @return Toolset_Field_Definition_Factory
	 * @since 3.3.6
	 */
	protected function get_field_definition_factory() {
		$domain = $this->get_domain();

		switch ( $domain ) {
			case Toolset_Element_Domain::POSTS:
				return Toolset_Field_Definition_Factory_Post::get_instance();
			case Toolset_Element_Domain::TERMS:
				return Toolset_Field_Definition_Factory_Term::get_instance();
			case Toolset_Element_Domain::USERS:
				return Toolset_Field_Definition_Factory_User::get_instance();
		}

		throw new RuntimeException( 'unexpected domain' );
	}


	/**
	 * Get all fields in a given group.
	 *
	 * @param Toolset_Field_Group $meta_group
	 *
	 * @return array
	 *     fields: \Toolset_Field_Definition[]
	 *     repeating_groups: \Types_Field_Group_Repeatable[]
	 * @since 3.3.6
	 */
	protected function get_fields_in_group( Toolset_Field_Group $meta_group ) {
		$meta = array(
			'fields' => array(),
			'repeating_groups' => array(),
		);

		$slugs = $meta_group->get_field_slugs();
		$factory = $this->get_field_definition_factory();

		foreach ( $slugs as $slug ) {
			$field_definition = $factory->load_field_definition( $slug );
			if (
				null !== $field_definition
				&& $field_definition->is_managed_by_types()
			) {
				$meta['fields'][] = $field_definition;
			} elseif (
			$repeatable_group = $this->repeatable_group_service->get_object_from_prefixed_string( $slug )
			) {
				$repeatable_group_field_slugs = $repeatable_group->get_field_slugs();
				if ( ! empty( $repeatable_group_field_slugs ) ) {
					$meta['repeating_groups'][] = $repeatable_group;
				}
			}
		}

		return $meta;
	}


	/**
	 * Get the parameters added to normal fields shortcodes.
	 *
	 * @param Toolset_Field_Definition $field
	 *
	 * @return array
	 * @since 3.3.6
	 */
	protected function get_shortcode_default_parameters( Toolset_Field_Definition $field ) {
		$parameter_key = $this->get_shortcode_meta_attribute();

		$parameters = array(
			$parameter_key => $field->get_slug(),
			'metaType' => $field->get_type()->get_slug(),
			'metaNature' => $field->is_repeatable() ? 'multiple' : 'single',
			'metaDomain' => $this->get_domain(),
		);

		switch ( $field->get_type()->get_slug() ) {
			case 'radio':
				$meta_options = array();
				foreach ( $field->get_field_options() as $option_key => $option ) {
					// Skip default value record
					if ( 'default' === $option_key ) {
						continue;
					}
					$meta_options[ $option_key ] = array(
						'title' => $option->get_display_value(), // This needs to be WPML corrected on render time
					);
				}
				$parameters['metaOptions'] = $meta_options;
				break;
			case 'checkboxes':
				$meta_options = array();
				foreach ( $field->get_field_options() as $option_key => $option ) {
					$meta_options[ $option_key ] = array(
						'title' => $option->get_label(), // This needs to be WPML corrected on render time
					);
				}
				$parameters['metaOptions'] = $meta_options;
				break;
		}

		return $parameters;
	}


	/**
	 * Get the parameters added to RFG fields shortcodes.
	 *
	 * @param Types_Field_Group_Repeatable $repeatable_field_group
	 *
	 * @return array
	 * @since 3.3.6
	 */
	protected function get_repeatable_field_group_default_parameters(
		Types_Field_Group_Repeatable $repeatable_field_group
	) {
		$parameter_key = $this->get_shortcode_meta_attribute();

		return array(
			$parameter_key => $repeatable_field_group->get_slug(),
			'metaType' => 'repeatable_field_group',
			'metaNature' => 'multiple',
			'metaDomain' => $this->get_domain(),
		);
	}


	/**
	 * Compose the cache for fields and RFGs in a field group.
	 *
	 * Note that the shortcodes GUI API wll:
	 * - adjust the WPML labels for checkboxes and radio fields, in the parameters entry.
	 * - include a JS callback to execute when clicking each item button.
	 *
	 * @param Toolset_Field_Group $meta_group
	 *
	 * @return array
	 * @since 3.3.6
	 */
	protected function compose_group_field_cache( Toolset_Field_Group $meta_group ) {
		$fields_cache = array();

		$meta = $this->get_fields_in_group( $meta_group );

		foreach ( $meta['fields'] as $meta_field ) {
			$parameters = $this->get_shortcode_default_parameters( $meta_field );
			$field_slug = $meta_field->get_slug();
			$fields_cache[ $field_slug ] = array(
				'name' => stripslashes( $meta_field->get_name() ),
				'handle' => 'types',
				'shortcode' => '[types '
					. $this->get_shortcode_meta_attribute()
					. '="'
					. esc_js( $field_slug )
					. '"][/types]',
				'parameters' => $parameters,
			);
		}

		foreach ( $meta['repeating_groups'] as $repeatable_field_group ) {
			$parameters = $this->get_repeatable_field_group_default_parameters(
				$repeatable_field_group
			);
			$fields_cache[ $repeatable_field_group->get_slug() ] = array(
				'name' => sprintf(
				/* translators: Title of the repeatable field groups as offered when generating shortcodes */
					__( '%1$s (repeatable field group)', 'wpcf' ),
					stripslashes( $repeatable_field_group->get_name() )
				),
				'handle' => 'types',
				'shortcode' => '',
				'parameters' => $parameters,
			);

			// Register fields inside this RFG as a separated group of items
			$this->compose_group_cache( $repeatable_field_group, true );
		}

		return $fields_cache;
	}


	/**
	 * Generate the cache for a given group.
	 *
	 * @param Toolset_Field_Group $meta_group
	 * @param bool $is_repeatable_group
	 *
	 * @since 3.3.6
	 */
	protected function compose_group_cache( Toolset_Field_Group $meta_group, $is_repeatable_group = false ) {
		$domain = $this->get_domain();
		$group_id = 'types-' . $domain . '-' . $meta_group->get_slug();

		// Separate the cache entry from populating its fields,
		// to keep the order in case there are inner RFGs.
		$this->cache[ $group_id ] = array(
			'id' => $group_id,
			'name' => ( $is_repeatable_group
				? sprintf(
				/* translators: Title of the repeatable field groups as offered when generating shortcodes */
					__( '%1$s (repeatable field group)', 'wpcf' ),
					stripslashes( $meta_group->get_name() )
				)
				: $meta_group->get_name()
			),
			'target' => $this->get_target(),
		);
		$this->cache[ $group_id ]['fields'] = $this->compose_group_field_cache( $meta_group );
	}


	/**
	 * Compose the cache.
	 *
	 * @since 3.3.6
	 */
	protected function compose_cache() {
		foreach ( $this->get_meta_groups() as $meta_group ) {
			$this->compose_group_cache( $meta_group );
		}
	}


	/**
	 * Generate a new cache for visible meta fields,
	 * or generate a just-in-time query for non standards query limits.
	 *
	 * @return array
	 * @since 3.3.6
	 */
	public function generate_cache() {
		$this->compose_cache();
		$this->set_cache();

		return $this->cache;
	}


	/**
	 * Set the cache for visible fields.
	 *
	 * @return bool
	 * @since 3.3.6
	 */
	public function set_cache() {
		return $this->transient_manager->set_transient( $this->get_transient_key(), $this->cache );
	}


	/**
	 * Delete the cache for visible fields.
	 *
	 * @return bool
	 * @since 3.3.6
	 */
	public function delete_cache() {
		return $this->transient_manager->delete_transient( $this->get_transient_key() );
	}

}
