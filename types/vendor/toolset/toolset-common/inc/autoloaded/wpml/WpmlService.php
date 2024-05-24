<?php

namespace OTGS\Toolset\Common\WPML;

use InvalidArgumentException;
use IToolset_Post;
use RuntimeException;
use Toolset_Constants;
use Toolset_Post_Type_Repository;
use Toolset_Wpdb_User;
use wpdb;

/**
 * Handle the basic interactions between WPML and Toolset plugins.
 *
 * This used to be the \Toolset_WPML_Compatability class (and an alias still exists and can be used).
 *
 * @since unknown
 */
class WpmlService extends Toolset_Wpdb_User {

	// Possible WPML translation modes. Use these constants instead of hardcoded strings as they might
	// change without warning.
	const MODE_DONT_TRANSLATE = 'dont_translate';

	const MODE_TRANSLATE = 'translate';

	const MODE_DISPLAY_AS_TRANSLATED = 'display_as_translated';

	/** @var WpmlService */
	private static $instance;

	private $constants;

	/** @var null|string Cache for the current language code. */
	private $current_language;

	/** @var null|string Cache for the default language code. */
	private $default_language;

	/** @var array */
	private $active_languages = array();

	/** @var string[] */
	private $previous_languages = array();

	/** @var null|array  */
	private $site_domains;

	/** @var int[] */
	private $post_trid_cache = [];

	/** @var int[] */
	private $post_trid_overrides = [];

	private $is_wpml_active_and_configured_cache;

	/**
	 * @return WpmlService
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}


	/**
	 * @return void
	 */
	public static function initialize() {
		self::get_instance();
	}


	/**
	 * WpmlService constructor.
	 *
	 * @param wpdb|null $wpdb_di
	 * @param Toolset_Constants|null $constants_di
	 */
	public function __construct( wpdb $wpdb_di = null, Toolset_Constants $constants_di = null ) {
		parent::__construct( $wpdb_di );
		$this->constants = $constants_di ? : new Toolset_Constants();

		add_action( 'init', [ $this, 'maybe_add_wpml_string_stub_shortcode' ], 100 );
		add_action( 'init', [ $this, 'register_active_languages' ], 100 );

		/**
		 * toolset_is_wpml_active_and_configured
		 *
		 * Check whether WPML core is active and configured properly.
		 *
		 * Note: Beware when calling this early, especially before 'init'. The behaviour depends
		 * on WPML and hasn't been tested.
		 *
		 * @since 2.3
		 */
		add_filter( 'toolset_is_wpml_active_and_configured', [ $this, 'filter_is_wpml_active_and_configured' ] );

		// Make sure that we clear our TRID cache right after any translation update has happened.
		// Priority is 11 because we want this to run after the action handler in the relationships module.
		add_action( 'wpml_translation_update', function () {
			$this->clear_post_trid_cache();
		}, 11, 0 );
	}


	/**
	 * In case WPML ST isn't active, add a stub "wpml-string" shortcode that will only
	 * return its content.
	 *
	 * This is to avoid printing of the unprocessed shortcode.
	 *
	 * @since unknown
	 */
	public function maybe_add_wpml_string_stub_shortcode() {
		if ( ! $this->is_wpml_st_active() ) {
			add_shortcode( 'wpml-string', array( $this, 'stub_wpml_string_shortcode' ) );
		}
	}

	public function register_active_languages() {
		if ( $this->is_wpml_active_and_configured() ) {
			$this->active_languages = apply_filters( 'wpml_active_languages', array() );
		}
	}


	/**
	 * Stub for the wpml-string shortcode.
	 *
	 * Make it as if the shortcode wasn't there.
	 *
	 * @param $atts
	 * @param string $value
	 *
	 * @return string
	 * @since unknown
	 */
	public function stub_wpml_string_shortcode(
		/** @noinspection PhpUnusedParameterInspection */
		$atts, $value
	) {
		return do_shortcode( $value );
	}


	/**
	 * Check whether WPML core is active and configured.
	 *
	 * The result is cached for better performance.
	 *
	 * @param bool $use_cache
	 *
	 * @return bool
	 * @since 2.3
	 */
	public function is_wpml_active_and_configured( $use_cache = true ) {
		if ( null === $this->is_wpml_active_and_configured_cache || ! $use_cache ) {
			global $sitepress;
			$is_wpml_active = (
				$this->constants->defined( 'ICL_SITEPRESS_VERSION' )
				&& ! $this->constants->constant( 'ICL_PLUGIN_INACTIVE' )
				&& ! is_null( $sitepress )
				&& class_exists( 'SitePress' )
			);

			if ( ! $is_wpml_active ) {
				$this->is_wpml_active_and_configured_cache = false;
			} else {
				$is_wpml_configured = apply_filters( 'wpml_setting', false, 'setup_complete' );
				$this->is_wpml_active_and_configured_cache = ( $is_wpml_active && $is_wpml_configured );
			}
		}

		return $this->is_wpml_active_and_configured_cache;
	}


	/**
	 * Check whether WPML core is active.
	 *
	 * @return bool
	 */
	public function is_wpml_active() {
		return did_action( 'wpml_loaded' ) > 0;
	}


	/**
	 * Callback for toolset_is_wpml_active_and_configured.
	 *
	 * Instead of calling this directly, use is_wpml_configured_and_active().
	 *
	 * @param mixed $default_value Ignored.
	 *
	 * @return bool
	 * @since 2.3
	 */
	public function filter_is_wpml_active_and_configured(
		/** @noinspection PhpUnusedParameterInspection */
		$default_value
	) {
		return $this->is_wpml_active_and_configured();
	}


	/**
	 * Check whether WPML ST is active.
	 *
	 * This will return false when WPML is not configured.
	 *
	 * @return bool
	 * @since 2.3
	 */
	public function is_wpml_st_active() {

		if ( ! $this->is_wpml_active_and_configured() ) {
			return false;
		}

		return ( defined( 'WPML_ST_VERSION' ) );
	}


	/**
	 * Check whether WPML TM is active.
	 *
	 * This will return false when WPML is not configured.
	 *
	 * @return bool
	 * @since 2.5
	 */
	public function is_wpml_tm_active() {

		if ( ! $this->is_wpml_active_and_configured() ) {
			return false;
		}

		return ( defined( 'WPML_TM_VERSION' ) );
	}


	/**
	 * Get the version of WPML core, if it's defined.
	 *
	 * @return null|string
	 * @since 2.3
	 */
	public function get_wpml_version() {
		return ( defined( 'ICL_SITEPRESS_VERSION' ) ? ICL_SITEPRESS_VERSION : null );
	}


	/**
	 * Check if a post type is translatable.
	 *
	 * @param string $post_type_slug
	 *
	 * @return bool
	 * @since m2m
	 */
	public function is_post_type_translatable( $post_type_slug ) {
		if ( ! $this->is_wpml_active_and_configured() ) {
			// Sometimes, the filter below starts working before the activeness check,
			// creating a little mess - this happens on WPML (re)activation.
			return false;
		}

		return (bool) apply_filters( 'wpml_is_translated_post_type', false, $post_type_slug );
	}


	/**
	 * Check if a post type is translatable and in the "display as translated" mode.
	 *
	 * @param string $post_type_slug
	 *
	 * @return bool
	 * @since 2.5.10
	 */
	public function is_post_type_display_as_translated( $post_type_slug ) {
		if ( ! $this->is_post_type_translatable( $post_type_slug ) ) {
			return false;
		}

		return (bool) apply_filters( 'wpml_is_display_as_translated_post_type', false, $post_type_slug );
	}


	/**
	 * Return translation modes for each existing post type.
	 *
	 * Note: May be somewhat performance-intensive.
	 *
	 * @return string[]|bool[] Array of values (one of the WpmlService::MODE_* constants) indexed by post type slugs.
	 *
	 * @since 4.0
	 */
	public function get_translation_modes_for_all_post_types() {
		return $this->map_post_type_settings( function ( $post_type, $is_translatable ) {
			if ( $is_translatable ) {
				return $this->is_post_type_display_as_translated( $post_type->get_slug() )
					? self::MODE_DISPLAY_AS_TRANSLATED
					: self::MODE_TRANSLATE;
			}

			return self::MODE_DONT_TRANSLATE;
		} );
	}


	/**
	 * Return translatability flag for each existing post type.
	 *
	 * @return bool[] Indexes are post type slugs, values indicate whether the post type is translatable (in any mode)
	 *    or not.
	 *
	 * @noinspection PhpUnusedParameterInspection
	 * @since 4.0
	 */
	public function get_translatability_for_all_post_types() {
		return $this->map_post_type_settings( function ( $post_type, $is_translatable ) {
			return $is_translatable;
		} );
	}


	/**
	 * For each post type, obtain a i18n-related setting from a callable.
	 *
	 * @param callable $process_post_type A callable that accepts two parameters:
	 *     1. string Post type slug.
	 *     2. bool $is_translatable
	 *     and returns the setting value for the given post type
	 *
	 * @return array Settings for every post type, indexed by post type slugs.
	 * @since 4.0
	 */
	private function map_post_type_settings( callable $process_post_type ) {
		$post_types = Toolset_Post_Type_Repository::get_instance()->get_all();

		if ( $this->is_wpml_active_and_configured() ) {
			$translatable_post_types = apply_filters( 'wpml_translatable_documents', [] );
		} else {
			$translatable_post_types = [];
		}

		$results = [];
		foreach ( $post_types as $post_type ) {
			$is_translatable = array_key_exists( $post_type->get_slug(), $translatable_post_types );
			$results[ $post_type->get_slug() ] = $process_post_type( $post_type, $is_translatable );
		}

		return $results;
	}


	/**
	 * Get the current language.
	 *
	 * Cached.
	 *
	 * @return string
	 * @since m2m
	 */
	public function get_current_language() {
		if ( null === $this->current_language && $this->is_wpml_active_and_configured() ) {
			$this->current_language = apply_filters( 'wpml_current_language', null );
		}

		return $this->current_language;
	}


	/**
	 * Get the default site language.
	 *
	 * Cached.
	 *
	 * @return string
	 * @since m2m
	 */
	public function get_default_language() {
		if ( null === $this->default_language && $this->is_wpml_active_and_configured() ) {
			$this->default_language = apply_filters( 'wpml_default_language', null );
		}

		return $this->default_language;
	}


	/**
	 * @return bool True if the site is currently in the default language.
	 * @since 2.5.10
	 */
	public function is_current_language_default() {
		return (
			! $this->is_wpml_active_and_configured()
			|| $this->get_default_language() === $this->get_current_language()
		);
	}


	/**
	 * Get the language of a provided post.
	 *
	 * @param int $post_id
	 *
	 * @return string Language code or an empty string if none is defined or WPML is not active.
	 */
	public function get_post_language( $post_id ) {
		$post_language_details = apply_filters( 'wpml_post_language_details', null, $post_id );
		if ( ! is_array( $post_language_details ) ) {
			// We may be dealing with a WP_Error here, for example when the post doesn't exist.
			return '';
		}
		$lang = toolset_getarr( $post_language_details, 'language_code', '' );

		if ( ! is_string( $lang ) ) {
			$lang = '';
		}

		return $lang;
	}


	/**
	 * Determine whether the current language is "All languages".
	 *
	 * @return bool
	 * @since  2.6.8
	 */
	public function is_showing_all_languages() {
		return $this->get_current_language() === 'all';
	}


	/**
	 * Get an array of post translation IDs from the icl_translations table, indexed by language codes.
	 *
	 * todo consider using WPML hooks if they're available
	 *
	 * @param int $post_id
	 *
	 * @return int[]
	 * @since 2.5.10
	 */
	public function get_post_translations_directly( $post_id ) {
		if ( ! $this->is_wpml_active_and_configured() ) {
			return array();
		}

		$icl_translations_table = $this->icl_translations_table_name();
		$trid = $this->get_post_trid( $post_id );

		if ( null === $trid ) {
			return array();
		}

		$query = $this->wpdb->prepare(
			"SELECT
					element_id AS post_id,
					language_code AS language_code
				FROM
					$icl_translations_table
				WHERE
					element_type LIKE %s
					AND trid = %d",
			'post_%',
			$trid
		);

		$db_results = $this->wpdb->get_results( $query );

		// Return an associative array of post IDs.
		$results = array();
		foreach ( $db_results as $row ) {
			$results[ $row->language_code ] = (int) $row->post_id;
		}

		return $results;
	}


	/**
	 * Check whether a default language of a post exists.
	 *
	 * Returns true if WPML is not active, as in that case, all posts are considered
	 * to be in the "default" language.
	 *
	 * Also returns true if the provided post itself is in the default language.
	 *
	 * @param int $post_id ID of the post
	 *
	 * @return boolean
	 * @since 4.0
	 */
	public function has_default_language_translation( $post_id ) {
		if ( ! $this->is_wpml_active_and_configured() ) {
			return true;
		}
		$translated_post_id = apply_filters( 'wpml_object_id', (int) $post_id, 'any', false, $this->get_default_language() );

		return ( null !== $translated_post_id );
	}


	/**
	 * Retrieve the translation group ID for a post.
	 *
	 * @param int $post_id
	 * @param bool $use_cache
	 * @param bool $save_to_cache Whether to store the retrieved ID to cache. Independent of $use_cache.
	 *
	 * @return int "trid" value or zero.
	 * @since m2m
	 * @since 4.0 Added simple in-memory caching.
	 */
	public function get_post_trid( $post_id, $use_cache = true, $save_to_cache = true ) {
		if ( ! $this->is_wpml_active_and_configured() ) {
			return 0;
		}

		if ( array_key_exists( $post_id, $this->post_trid_overrides ) ) {
			return $this->post_trid_overrides[ $post_id ];
		}

		if ( $use_cache && array_key_exists( $post_id, $this->post_trid_cache ) ) {
			return $this->post_trid_cache[ $post_id ];
		}

		$lang_details = (array) apply_filters( 'wpml_element_language_details', [], [
			'element_id' => $post_id,
			'element_type' => get_post_type( $post_id ),
		] );

		$trid = (int) toolset_getarr( $lang_details, 'trid', null );

		if ( $save_to_cache ) {
			$this->post_trid_cache[ $post_id ] = $trid;
		}

		return $trid;
	}


	/**
	 * Override the results of get_post_trid() for a specific post.
	 *
	 * @param int $post_id
	 * @param int|null $trid If null is provided, any existing override for the given post ID will be removed.
	 */
	public function override_post_trid( $post_id, $trid ) {
		if ( null === $trid ) {
			unset( $this->post_trid_overrides[ (int) $post_id ] );

			return;
		}
		$this->post_trid_overrides[ (int) $post_id ] = (int) $trid;
	}


	/**
	 * For a given post ID, return its original.
	 *
	 * Note that original element isn't the same as default language version. WPML guarantees one always exists
	 * for each translatable element.
	 *
	 * @param int $post_id ID of the post to translate.
	 * @param bool $use_cache Use the in-memory cache for obtaining post's TRID.
	 *
	 * @return int Original post ID. Zero is returned if WPML is not active, the post is not translatable or
	 *     it doesn't have a TRID assigned for any reason.
	 */
	public function get_original_post_id( $post_id, $use_cache = true ) {
		if ( ! $this->is_wpml_active_and_configured() ) {
			return 0;
		}

		$trid = $this->get_post_trid( $post_id, $use_cache );
		if ( ! $trid ) {
			return 0;
		}

		$translations = toolset_ensarr(
			apply_filters( 'wpml_get_element_translations', [], $trid, 'any' )
		);

		$results = array_filter( $translations, static function ( $translation ) {
			return null === $translation->source_language_code;
		} );

		if ( empty( $results ) ) {
			return 0;
		}

		$source_language_translation = array_shift( $results );

		return (int) $source_language_translation->element_id;
	}


	/**
	 * Clear the cache of post TRIDs.
	 *
	 * @param int|null $post_id ID of a post whose record should be removed from the cache, NULL to clear the whole
	 *     cache.
	 *
	 * @since 4.0
	 */
	public function clear_post_trid_cache( $post_id = null ) {
		if ( null === $post_id ) {
			$this->post_trid_cache = [];

			return;
		}

		unset( $this->post_trid_cache[ $post_id ] );
	}


	/**
	 * @return string icl_translations table name.
	 */
	public function icl_translations_table_name() {
		return $this->wpdb->prefix . 'icl_translations';
	}


	/**
	 * Get the translation mode value for a given post type.
	 *
	 * If WPML is not active or the post type doesn't exist, self::MODE_DONT_TRANSLATE will be returned.
	 *
	 * @param string $post_type_slug
	 *
	 * @return string
	 * @since 2.5.11
	 */
	public function get_post_type_translation_mode( $post_type_slug ) {
		if (
			! $this->is_wpml_active_and_configured()
			|| ! $this->is_post_type_translatable( $post_type_slug )
		) {
			return self::MODE_DONT_TRANSLATE;
		}

		if ( $this->is_post_type_display_as_translated( $post_type_slug ) ) {
			return self::MODE_DISPLAY_AS_TRANSLATED;
		}

		return self::MODE_TRANSLATE;
	}


	/**
	 * Set the translation mode of given post type.
	 *
	 * @param string $post_type_slug
	 * @param string $translation_mode One of the MODE_ constants defined on this class.
	 *
	 * @return void
	 * @throws InvalidArgumentException if WPML is not active or an invalid translation mode is provided.
	 * @since 2.5.11
	 */
	public function set_post_type_translation_mode( $post_type_slug, $translation_mode ) {
		if ( ! $this->is_wpml_active_and_configured() ) {
			throw new InvalidArgumentException( 'Trying to set a post translation mode while WPML is not active.' );
		}

		$allowed_modes = array( self::MODE_TRANSLATE, self::MODE_DONT_TRANSLATE, self::MODE_DISPLAY_AS_TRANSLATED );

		if ( ! in_array( $translation_mode, $allowed_modes ) ) {
			throw new InvalidArgumentException( 'Trying to set an invalid translation mode for a post type' );
		}

		do_action( 'wpml_set_translation_mode_for_post_type', $post_type_slug, $translation_mode );
	}


	/**
	 * Set a post as a translation of another post (original).
	 *
	 * @param IToolset_Post $original_post
	 * @param int $translation_post_id ID of the translated post.
	 * @param string $lang_code Language of the translated post.
	 *
	 * @return void
	 * @throws InvalidArgumentException If called when WPML inactive.
	 */
	public function add_post_translation( IToolset_Post $original_post, $translation_post_id, $lang_code ) {
		if ( ! $this->is_wpml_active_and_configured() ) {
			throw new InvalidArgumentException( 'Cannot add a post translation if WPML is not active and configured.' );
		}
		$element_type = apply_filters( 'wpml_element_type', $original_post->get_type() );

		$set_language_args = array(
			'element_id' => $translation_post_id,
			'element_type' => $element_type,
			'trid' => $original_post->get_trid(),
			'language_code' => $lang_code,
			'source_language_code' => $original_post->get_language(),
		);

		do_action( 'wpml_set_element_language_details', $set_language_args );
	}


	/**
	 * Create a duplicate of the given post (using standard WPML mechanism to copy the content).
	 *
	 * Optionally, it is possible to _not_ mark it as an duplicate, but as a regular translation instead.
	 *
	 * @param IToolset_Post $original_post
	 * @param string $lang_code Language of the duplicated post.
	 * @param bool $mark_as_duplicate
	 *
	 * @return int ID of the duplicated post.
	 * @throws InvalidArgumentException If called when WPML inactive.
	 * @throws RuntimeException If it is not possible to perform the call to WPML.
	 */
	public function create_post_duplicate( IToolset_Post $original_post, $lang_code, $mark_as_duplicate = true ) {
		if ( ! $this->is_wpml_active_and_configured() ) {
			throw new InvalidArgumentException( 'Cannot add a post translation if WPML is not active and configured.' );
		}

		$copied_post_id = apply_filters( 'wpml_copy_post_to_language', $original_post->get_id(), $lang_code, $mark_as_duplicate );

		return (int) $copied_post_id;
	}


	/**
	 * Switch the current language.
	 *
	 * Warning: You *MUST* revert this by calling switch_language_back() in all cases.
	 *
	 * It is possible to nest these calls, but switch_language() and switch_language_back() must always
	 * come in pairs. Keep in mind that until we switch all the way back to the original state,
	 * the language of the GUI will be affected as well (see types-2697 as an example of what this might cause).
	 *
	 * @param string $lang_code
	 *
	 * @since 2.5.10
	 */
	public function switch_language( $lang_code ) {
		$this->previous_languages[] = $this->get_current_language();
		do_action( 'wpml_switch_language', $lang_code );
		$this->current_language = null; // Clear cache.
	}


	/**
	 * Switch the current language back to the previous value after switch_language().
	 *
	 * @since 2.5.10
	 */
	public function switch_language_back() {
		$lang_code = array_pop( $this->previous_languages );
		if ( empty( $this->previous_languages ) ) {
			// We're switching to the original state (how it was at the beginning of the current request).
			// Using null will ensure that the language of the GUI, which depends on the user's profile settings,
			// is also set back to its original value (this is the only way we can control it from here).
			$lang_code = null;
		}
		do_action( 'wpml_switch_language', $lang_code );
		$this->current_language = null; // Clear cache.
	}


	/**
	 * Get the URL for the WPML setting of post type translation modes.
	 *
	 * Note: This works since WPML 3.9.2.
	 *
	 * @return string Escaped URL.
	 * @throws RuntimeException If WPML is not active and configured.
	 * @since 3.8
	 */
	public function get_post_type_translation_settings_url() {
		if ( ! $this->is_wpml_active_and_configured() ) {
			throw new RuntimeException( 'Cannot get the translation options URL until WPML is active and configured.' );
		}

		$url = esc_url_raw( apply_filters( 'wpml_get_post_translation_settings_link', '' ) );
		if ( ! is_string( $url ) ) {
			// Something bad happened, but not our fault.
			return '';
		}

		return $url;
	}


	/**
	 * Register a string for translation
	 *
	 * @param string $value Value.
	 * @param string $name Name.
	 * @param array $package Package.
	 * @param string $title Title.
	 * @param string $type Type.
	 *
	 * @see wpml_register_string
	 * @link https://wpml.org/documentation/support/string-package-translation/#recommended-workflow-for-registering-your-strings
	 */
	public function register_string( $value, $name, $package, $title, $type = 'LINE' ) {
		do_action( 'wpml_register_string', $value, $name, $package, $title, $type );
	}


	/**
	 * Translate a string
	 *
	 * @param string $value Value.
	 * @param string $name Name.
	 * @param array $package Package.
	 *
	 * @return mixed|void
	 * @link https://wpml.org/documentation/support/string-package-translation/#recommended-workflow-for-registering-your-strings
	 * @see wpml_register_string
	 */
	public function translate_string( $value, $name, $package ) {
		return apply_filters( 'wpml_translate_string', $value, $name, $package );
	}


	/**
	 * @param IToolset_Post|int $post_source
	 *
	 * @return string|null
	 * @since 4.0
	 */
	public function get_language_flag_url( $post_source ) {
		if ( ! $this->is_wpml_active_and_configured() ) {
			return null;
		}
		if ( $post_source instanceof IToolset_Post ) {
			if ( ! $post_source->is_translatable() ) {
				return null;
			}

			$post_id = $post_source->get_id();
		} else {
			if ( ! \Toolset_Utils::is_natural_numeric( $post_source ) ) {
				throw new InvalidArgumentException( 'Invalid post source.' );
			}

			$post_id = (int) $post_source;

			if ( ! $this->is_post_type_translatable( get_post_type( $post_id ) ) ) {
				return null;
			}
		}

		$flag_url = apply_filters( 'wpml_post_language_flag_url', null, $post_id );

		if ( empty( $flag_url ) ) {
			return null;
		}

		return $flag_url;
	}


	public function get_element_translations( $trid, $wpml_element_type ) {
		$translations = toolset_ensarr(
			apply_filters( 'wpml_get_element_translations', [], $trid, $wpml_element_type )
		);

		$results = [];
		foreach (
			array_map(
				static function ( $translation_data ) {
					return (array) $translation_data;
				},
				$translations
			) as $lang_code => $translation_data
		) {
			$results[ $lang_code ] = (int) toolset_getarr( $translation_data, 'element_id' );
		}

		return $results;
	}


	public function get_post_translations( $trid ) {
		// 'any' counts as "any post type" in WPML.
		return $this->get_element_translations( $trid, 'any' );
	}


	public function translate_element( $element_id, $element_type = 'post', $return_original_if_missing = true, $lang_code = null ) {
		return apply_filters( 'wpml_object_id', $element_id, $element_type, $return_original_if_missing, $lang_code );
	}


	public function get_site_domains() {
		if (
			null !== $this->site_domains
			&& is_array( $this->site_domains )
		) {
			return $this->site_domains;
		}
		// Current host.
		$this_host = str_ireplace( 'www.', '', wp_parse_url( home_url(), PHP_URL_HOST ) );

		// Alternative hosts when the site uses different domains per language.
		$language_domains = apply_filters( 'wpml_setting', array(), 'language_domains' );
		$accepted_lang_hosts = array_map( function( $language_host ) {
			return str_ireplace( 'www.', '', wp_parse_url( $language_host, PHP_URL_HOST ) );
		}, $language_domains );

		// Complete the list with hosts from active languages.
		$active_language_hosts = array_map( function( $language_data ) {
			return str_ireplace( 'www.', '', wp_parse_url( $language_data['url'], PHP_URL_HOST ) );
		}, $this->active_languages );

		// Define the valid site domains.
		$this->site_domains = array_values( array_unique(
			array_merge(
				[ $this_host ],
				array_values( $accepted_lang_hosts ),
				array_values( $active_language_hosts )
			)
		) );

		return $this->site_domains;
	}

}

// See the inc/autoloaded/legacy_aliases directory for further info.
/** @noinspection PhpIgnoredClassAliasDeclaration */
class_alias( WpmlService::class, '\Toolset_WPML_Compatibility' );
