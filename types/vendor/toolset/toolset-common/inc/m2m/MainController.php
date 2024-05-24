<?php

namespace OTGS\Toolset\Common\Relationships;

use Exception;
use IToolset_Element;
use IToolset_Relationship_Database_Issue;
use OTGS\Toolset\Common\Auryn\InjectionException;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\DatabaseLayerFactory;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\DatabaseLayerMode;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\Migration\DuringMigrationIntegrity;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\Migration\IsMigrationUnderwayOption;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\Persistence\ConnectedElementPersistence;
use OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\TableNames;
use RuntimeException;
use Toolset_Association_Cleanup_Factory;
use Toolset_Common_Autoloader;

/**
 * Main controller class for object relationships in Toolset.
 *
 * initialize() needs to be called during init on every request, and no relationship functionality can be
 * used before then.
 *
 * Always use this as a singleton in the production code.
 *
 * @since m2m
 */
class MainController {

	const IS_M2M_ENABLED_OPTION = 'toolset_is_m2m_enabled';

	const IS_M2M_ENABLED_YES_VALUE = 'yes';

	// This is not a typo. Initially, we had 'no', but then we changed the algorithm to determine the initial
	// m2m state, and we have force re-checking.
	const IS_M2M_ENABLED_NO_VALUE = 'noo';


	/**
	 * We need WPML to fire certain actions when it updates its icl_translations table.
	 */
	const MINIMAL_WPML_VERSION = '3.9.3';


	private $is_autoloader_initialized = false;

	private $is_initialized = false;


	/** @var null|bool Cache for is_m2m_enabled() */
	private $is_m2m_enabled_cache;


	/** @var null|Toolset_Association_Cleanup_Factory */
	private $_cleanup_factory;


	/** @var DatabaseLayerFactory|null */
	private $_database_layer_factory;


	/** @var MainController|null */
	private static $instance;


	/**
	 * @return MainController
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}


	/**
	 * Toolset_Relationship_Controller constructor.
	 *
	 * @param DatabaseLayerFactory|null $database_layer_factory_di
	 * @param Toolset_Association_Cleanup_Factory|null $cleanup_factory_di
	 */
	public function __construct(
		DatabaseLayerFactory $database_layer_factory_di = null,
		Toolset_Association_Cleanup_Factory $cleanup_factory_di = null
	) {
		$this->_database_layer_factory = $database_layer_factory_di;
		$this->_cleanup_factory = $cleanup_factory_di;
	}


	/**
	 * Returns the value of the m2m feature toggle.
	 *
	 * Default value depends on the presence of legacy post relationships on the site.
	 *
	 * The result is cached.
	 *
	 * @return bool
	 */
	public function is_m2m_enabled() {

		if ( null !== $this->is_m2m_enabled_cache ) {
			return $this->is_m2m_enabled_cache;
		}

		$is_enabled_option = get_option( self::IS_M2M_ENABLED_OPTION, null );

		// We'll force the check again if 'no' is stored, because the algorithm for determining
		// the initial state has changed since (now a different value for a negative result is used).
		if ( null === $is_enabled_option || 'no' === $is_enabled_option ) {
			$is_enabled = $this->set_initial_m2m_state();
		} else {
			$is_enabled = ( self::IS_M2M_ENABLED_YES_VALUE === $is_enabled_option );
		}

		/**
		 * Allows for overriding the m2m feature toggle (both ways).
		 *
		 * This filter is dangerous and should never be used in production. Also, it may disappear at any given
		 * moment. For only determining whether m2m is enabled or not, use the toolset_is_m2m_enabled filter.
		 *
		 * @since m2m
		 */
		$is_enabled = (bool) apply_filters( 'toolset_enable_m2m_manually', $is_enabled );

		$this->is_m2m_enabled_cache = $is_enabled;

		return $is_enabled;
	}


	public function reset() {
		$this->is_initialized = false;
		$this->is_m2m_enabled_cache = null;
		$this->initialize();
	}


	/**
	 * Full initialization that is needed before any relationships-related action takes place.
	 *
	 * @since m2m
	 */
	public function initialize() {
		if ( $this->is_initialized ) {
			return;
		}

		if ( ! $this->is_m2m_enabled() ) {
			return;
		}

		$this->initialize_autoloader();
		$this->add_hooks();

		$this->is_initialized = true;
	}


	/**
	 * Backward compatibility measure. This method is no longer necessary to call.
	 *
	 * @deprecated
	 * @since 4.0
	 */
	public function initialize_full() {
		$this->initialize();
	}


	public function is_fully_initialized() {
		return $this->is_initialized;
	}


	/**
	 * Register all Toolset_Relationship_* classes in the Toolset autoloader.
	 *
	 * @since m2m
	 */
	private function initialize_autoloader() {
		if ( $this->is_autoloader_initialized ) {
			return;
		}

		$autoloader = Toolset_Common_Autoloader::get_instance();

		$autoload_classmap_file = TOOLSET_COMMON_PATH . '/inc/m2m/autoload_classmap.php';
		if ( ! is_file( $autoload_classmap_file ) ) {
			// abort if file does not exist
			return;
		}

		/** @noinspection PhpIncludeInspection */
		$classmap = include( $autoload_classmap_file );
		$autoloader->register_classmap( $classmap );

		$this->is_autoloader_initialized = true;
	}


	/**
	 * Force the autoloader classmap registration when usage of m2m API classes is necessary even
	 * with m2m not enabled.
	 *
	 * @since m2m
	 */
	public function force_autoloader_initialization() {
		$this->initialize_autoloader();
	}


	/**
	 * Determine whether m2m should be enabled by default.
	 *
	 * See the InitialStateSetup class for more information.
	 *
	 * @return bool
	 * @since m2m
	 */
	private function set_initial_m2m_state() {
		$dic = toolset_dic();

		/** @var InitialStateSetup $initial_state_setup */
		try {
			$this->force_autoloader_initialization();
			$initial_state_setup = $dic->make( InitialStateSetup::class );
		} catch ( InjectionException $e ) {
			return false;
		}

		return $initial_state_setup->set_initial_state();
	}


	/**
	 * Add hooks to relevant actions and filters.
	 *
	 * All callback functions need to do initialize_full() before anything else.
	 *
	 * @since m2m
	 */
	private function add_hooks() {
		/**
		 * toolset_is_m2m_enabled
		 *
		 * @param false $default_value
		 *
		 * @return bool Is the m2m functionality enabled? If true, all legacy post relationship functionality should be
		 *     replaced by the m2m one.
		 * @since m2m
		 */
		add_filter( 'toolset_is_m2m_enabled', array( $this, 'is_m2m_enabled' ) );

		if ( $this->is_m2m_enabled() ) {
			$this->add_hooks_when_active();
		}

	}


	private function add_hooks_when_active() {
		// API hooks
		//
		//

		/**
		 * toolset_report_m2m_integrity_issue
		 *
		 * Allow for reporting that there is some sort of data corruption in the database.
		 *
		 * @param IToolset_Relationship_Database_Issue $issue
		 *
		 * @since 2.5.6
		 */
		add_action( 'toolset_report_m2m_integrity_issue', function ( $issue ) {
			$this->initialize();

			if ( $issue instanceof IToolset_Relationship_Database_Issue ) {
				$issue->handle();
			}
		} );

		/**
		 * Filter toolset_get_element_group_id.
		 *
		 * If the second version of the database layer is active, this will provide the group_id of
		 * the given element. The filter exists to prevent a hard dependency on the database layer version
		 * outside of \OTGS\Toolset\Common\Relationships namespace.
		 *
		 * Note that this is used by IToolset_Element implementations - careful about infinite recursion.
		 *
		 * @param mixed $default The value that will be returned if the group_id cannot be obtained.
		 * @param IToolset_Element $element Element whose group_id needs to be determined.
		 * @param bool $create_if_missing Assign a new group_id if the element doesn't have one yet.
		 *
		 * @return int|mixed The group_id value or $default.
		 * @since 4.0
		 */
		add_filter( 'toolset_get_element_group_id', function ( $default, $element, $create_if_missing = false ) {
			if ( ! $this->get_database_layer_factory()->database_layer_mode()->is(
				DatabaseLayerMode::VERSION_2 )
			) {
				return $default;
			}

			if ( ! $element instanceof IToolset_Element ) {
				return $default;
			}

			try {
				return $this->get_database_layer_factory()
					->connected_element_persistence()
					->obtain_element_group_id( $element, (bool) $create_if_missing );
			} catch ( RuntimeException $e ) {
				return $default;
			}
		}, 10, 3 );

		// Core relationship functionality.
		//
		//

		add_action( 'admin_init', function () {
			$this->initialize();
			$this->get_database_layer_factory()->table_existence_check()->ensure_tables_exist();
			$this->get_cleanup_factory()->troubeshooting_section()->register();
		} );

		add_filter( 'before_delete_post', function ( $post_id ) {
			// Handle events on post deletion (triggered by wp_delete_post()).
			//
			// Basically, that means checking if there are any associations with this post and delete them.
			// Note that that will also trigger deleting the intermediary post and possibly some owned elements.
			$this->initialize();

			try {
				$cleanup = $this->get_cleanup_factory()->post();
				$cleanup->cleanup_before_delete( $post_id );
			} catch ( Exception $e ) {
				// Silently do nothing and avoid disrupting the current process, whatever it is.
				// In the worst case, any potential dangling db stuff can be sorted out
				// later on the Troubleshooting page.
			}
		} );

		add_filter( 'after_delete_post', function ( $post_id ) {
			$this->get_cleanup_factory()->post()->cleanup_after_delete( $post_id );
		} );

		$this->add_action_to_wpcf_post_type_renamed();

		/**
		 * toolset_cron_cleanup_dangling_intermediary_posts
		 *
		 * A WP-Cron event hook defined as Toolset_Association_Cleanup_Cron_Event.
		 *
		 * @since 2.5.10
		 */
		add_action( 'toolset_cron_cleanup_dangling_intermediary_posts', function () {
			$this->initialize();
			$cron_handler = $this->get_cleanup_factory()->cron_handler();
			$cron_handler->handle_event();
		} );

		add_action( 'wpml_translation_update', function ( $update_description ) {
			if ( ! $this->get_database_layer_factory()->database_layer_mode()->is(
				DatabaseLayerMode::VERSION_2
			) ) {
				return;
			}

			$this->get_database_layer_factory()->wpml_translation_update_handler()
				->on_wpml_translation_update( $update_description );
		} );


		// This is a little temporary hack to get notified about all post type translatability changes,
		// not only when WPML actually adds some new TRIDs to posts.
		//
		// It addresses an edge case when:
		//
		// - making a CPT translatable
		// - but all posts of that CPT already have TRIDs assigned
		// - but some of those posts have been newly used in associations while the CPT was non-translatable
		//
		// ... in this case, WPML 4.3.17 will not fire the wpml_translation_update action because there have been
		// no new TRIDs actually assigned, but that means we end up with rows in the wp_toolset_connected_elements table
		// that should have a wpml_trid set but they don't.
		//
		// This ought to be removed (or made conditional only for the new WPML version) once the underlying issue
		// is resolved in WPML.
		add_action( 'wpml_verify_post_translations', static function ( $new_options ) {
			foreach ( $new_options as $post_type_slug => $option ) {
				// 1 or 2 => translatable CPT
				// the keys for non-translatable CPTs will not be present
				if ( ! is_numeric( $option ) ) {
					continue;
				}
				do_action( 'wpml_translation_update', [
					'type' => 'initialize_language_for_post_type',
					'post_type' => $post_type_slug,
					'context' => 'post',
				] );
			}
		}, 1000 );

		// Ensure the data integrity during migration between database layers.
		add_action( 'toolset_before_association_delete', function (
			$relationship_slug, $parent_id, $child_id
		) {
			if ( ! $this->get_database_layer_factory()->database_layer_mode()->is( DatabaseLayerMode::VERSION_1 ) ) {
				return;
			}

			$this->get_database_layer_factory()
				->during_migration_compatibility()
				->synchronize_deleted_association( $relationship_slug, $parent_id, $child_id );
		}, 10, 3 );
		add_action( 'toolset_before_associations_by_element_delete', function( $element_id, $element_domain ) {
			if ( ! $this->get_database_layer_factory()->database_layer_mode()->is( DatabaseLayerMode::VERSION_1 ) ) {
				return;
			}

			$this->get_database_layer_factory()
				->during_migration_compatibility()
				->synchronize_deleted_associations_by_element( $element_id, $element_domain );
		}, 10, 2 );
	}


	/**
	 * On change of cpt slug.
	 * Method to prevent any misconfiguration/duplicated actions by callers.
	 *
	 * @since 3.0.7 (only the function to add the action, the action itself is added since 2.5.6)
	 */
	public function add_action_to_wpcf_post_type_renamed() {
		add_action( 'wpcf_post_type_renamed', array( $this, 'on_types_cpt_rename_slug' ), 10, 2 );
	}


	/**
	 * Counter part of add_action_to_wpcf_post_type_renamed()
	 *
	 * @since 3.0.7
	 */
	public function remove_action_of_wpcf_post_type_renamed() {
		remove_action( 'wpcf_post_type_renamed', array( $this, 'on_types_cpt_rename_slug' ), 10 );
	}


	/**
	 * Hooked into the wpcf_post_type_renamed action.
	 * To update the slug in the relationship definition when the cpt slug is changed on the cpt edit page.
	 *
	 * @param $new_slug
	 * @param $old_slug
	 *
	 * @since 2.5.6
	 */
	public function on_types_cpt_rename_slug( $new_slug, $old_slug ) {
		if ( $new_slug === $old_slug ) {
			// no change
			return;
		}

		$this->initialize();

		$result = $this->get_database_layer_factory()
			->relationship_database_operations()
			->update_type_on_type_sets( $new_slug, $old_slug );

		if ( $result->is_error() ) {
			/** @noinspection ForgottenDebugOutputInspection */
			error_log( $result->get_message() );
		}
	}


	/**
	 * @return Toolset_Association_Cleanup_Factory
	 */
	private function get_cleanup_factory() {
		if ( null === $this->_cleanup_factory ) {
			$this->initialize();
			$this->_cleanup_factory = new Toolset_Association_Cleanup_Factory();
		}

		return $this->_cleanup_factory;
	}


	/**
	 * @return mixed|DatabaseLayerFactory|null
	 * @noinspection PhpDocMissingThrowsInspection
	 */
	private function get_database_layer_factory() {
		if ( null === $this->_database_layer_factory ) {
			$this->initialize();
			/** @noinspection PhpUnhandledExceptionInspection */
			$this->_database_layer_factory = toolset_dic()->make( DatabaseLayerFactory::class );
		}

		return $this->_database_layer_factory;
	}

}


// See the inc/autoloaded/legacy_aliases directory for further info.
/** @noinspection PhpIgnoredClassAliasDeclaration */
class_alias( MainController::class, 'Toolset_Relationship_Controller' );
