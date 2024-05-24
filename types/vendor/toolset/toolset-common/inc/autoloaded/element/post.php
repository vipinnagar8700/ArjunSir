<?php

use OTGS\Toolset\Common\PostType\EditorMode;
use OTGS\Toolset\Common\WPML\WpmlService;

/**
 * Model of a WordPress post.
 *
 * Simplifies the access to field instances and associations.
 *
 * Always use Toolset_Element_Factory to instantiate this class.
 *
 * @since m2m
 */
class Toolset_Post extends Toolset_Element implements IToolset_Post {


	/** @var string Meta key for storing the manual sort order of items within a RFG */
	const SORTORDER_META_KEY = 'toolset-post-sortorder';

	const NATIVE_PAGE_TEMPLATE_META_KEY = '_wp_page_template';

	/** @deprecated CTs are applied now with a decision tree */
	const CONTENT_TEMPLATE_META_KEY = '_views_template';

	// Meta key where the EditorMode value is stored.
	const EDITOR_MODE_META_KEY = 'toolset_post_editor_mode';

	// Meta key where we store a base64-encoded content of the post content before switching to the Gutenberg/block editor.
	const POST_CONTENT_BEFORE_GUTENBERG_META_KEY = 'toolset_post_content_before_gutenberg';



	/** @var WP_Post */
	private $post;


	/** @var string Language code of the current post or an empty string if unknown or not applicable. */
	private $language_code = null;


	private $_post_type_repository;

	private $element_factory;


	/** @var WpmlService */
	private $wpml_service;


	/**
	 * Toolset_Element constructor.
	 *
	 * @param mixed|numeric $object_source The underlying object or its ID.
	 * @param string|null $language_code Post's language. An empty string will be interpreted as
	 *     "this post has no language", while null can be passed if this unknown (and it will be
	 *     determined first time it's needed).
	 * @param null|Toolset_Field_Group_Post_Factory $group_post_factory DI for phpunit
	 * @param Toolset_Post_Type_Repository|null $post_type_repository_di
	 * @param Toolset_Element_Factory|null $element_factory_di
	 * @param WpmlService $wpml_service_di
	 *
	 * @throws Toolset_Element_Exception_Element_Doesnt_Exist
	 * @since m2m
	 */
	public function __construct(
		$object_source,
		$language_code = null,
		$group_post_factory = null,
		Toolset_Post_Type_Repository $post_type_repository_di = null,
		Toolset_Element_Factory $element_factory_di = null,
		WpmlService $wpml_service_di = null
	) {
		$this->_post_type_repository = $post_type_repository_di;

		if( Toolset_Utils::is_natural_numeric( $object_source ) ) {
			$post = WP_Post::get_instance( $object_source );
		} else {
			$post = $object_source;
		}

		if( ! $post instanceof WP_Post ) {
			throw new Toolset_Element_Exception_Element_Doesnt_Exist(
				Toolset_Element_Domain::POSTS,
				$object_source
			);
		}

		if( ! is_string( $language_code ) && null !== $language_code ) {
			throw new InvalidArgumentException( 'Invalid language code provided.' );
		}

		parent::__construct( $post, $group_post_factory );

		$this->post = $post;
		$this->language_code = $language_code;
		$this->element_factory = ( null === $element_factory_di ? new Toolset_Element_Factory() : $element_factory_di );
		$this->wpml_service = ( null === $wpml_service_di ? WpmlService::get_instance() : $wpml_service_di );
	}


	/**
	 * Instantiate the post.
	 *
	 * To be used only within m2m API. For instantiating Toolset elements, you should
	 * always use Toolset_Element::get_instance().
	 *
	 * @param numeric|WP_Post $object_source
	 * @param string|null $language_code
	 *
	 * @deprecated Use Toolset_Element_Factory::get_post() instead.
	 *
	 * @return Toolset_Post
	 * @throws Toolset_Element_Exception_Element_Doesnt_Exist
	 */
	public static function get_instance( $object_source, $language_code = null ) {
		$element_factory = new Toolset_Element_Factory();
		return $element_factory->get_post_untranslated( $object_source, $language_code );
	}


	/**
	 * @return string One of the Toolset_Field_Utils::get_domains() values.
	 */
	public function get_domain() { return Toolset_Element_Domain::POSTS; }


	/**
	 * @return int Post ID.
	 */
	public function get_id() { return $this->post->ID; }


	/**
	 * @return string Post title.
	 */
	public function get_title() { return $this->post->post_title; }


	/**
	 * @inheritdoc
	 * @return Toolset_Field_Group_Post[]
	 * @since m2m
	 */
	protected function get_relevant_field_groups() {
		return $this->group_post_factory->get_groups_by_post_type( $this->get_type() );
	}


	/**
	 * @return string Post type slug.
	 * @since m2m
	 */
	public function get_type() {
		return $this->post->post_type;
	}


	/**
	 * @inheritdoc
	 * @return bool
	 */
	public function is_translatable() {
		return $this->wpml_service->is_post_type_translatable( $this->get_type() );
	}


	/**
	 * @inheritdoc
	 * @return string
	 */
	public function get_language() {
		if( null === $this->language_code ) {
			$this->language_code = $this->wpml_service->get_post_language( $this->get_id() );
		}

		return $this->language_code;
	}


	/**
	 * @param string $title New post title
	 *
	 * @return void
	 * @since m2m
	 */
	public function set_title( $title ) {
		$this->post->post_title = sanitize_text_field( $title );
	}


	/**
	 * @inheritdoc
	 * @return string
	 */
	public function get_slug() {
		return $this->post->post_name;
	}


	protected function get_post_type_repository() {
		if( null === $this->_post_type_repository ) {
			$this->_post_type_repository = Toolset_Post_Type_Repository::get_instance();
		}

		return $this->_post_type_repository;
	}


	/**
	 * @return IToolset_Post_Type|null
	 * @since 2.5.10
	 */
	public function get_type_object() {
		return $this->get_post_type_repository()->get( $this->get_type() );
	}


	/**
	 * @inheritdoc
	 *
	 * @param string $language_code
	 * @param bool $exact_match_only
	 *
	 * @return IToolset_Element|null
	 * @throws Toolset_Element_Exception_Element_Doesnt_Exist
	 */
	public function translate( $language_code, $exact_match_only = false ) {
		if( ! $this->is_translatable() ) {
			return $this;
		}

		// This could happen only in very rare cases, when WPML is active,
		// someone obtains a translation set from Toolset_Element_Factory,
		// calls translate() on it, which would return this instance, and then
		// they call translate() again... and here we are.
		$translation_set = $this->element_factory->get_post_translation_set( array( $this ) );
		return $translation_set->translate( $language_code, $exact_match_only );
	}


	/**
	 * @inheritdoc
	 *
	 * @return int
	 * @since 2.5.10
	 * @throws Toolset_Element_Exception_Element_Doesnt_Exist
	 */
	public function get_default_language_id() {
		if( ! $this->is_translatable() ) {
			return $this->get_id();
		}

		// This could happen only in very rare cases, when WPML is active,
		// someone obtains a translation set from Toolset_Element_Factory,
		// calls translate() on it, which would return this instance, and then
		// they call this method... and here we are.
		$translation_set = $this->element_factory->get_post_translation_set( array( $this ) );
		return $translation_set->get_default_language_id();
	}


	/**
	 * @return bool
	 * @since 2.5.10
	 */
	public function is_revision() {
		return ( 'revision' === $this->get_type() );
	}


	/**
	 * @inheritdoc
	 *
	 * @return int
	 * @since 2.5.11
	 */
	public function get_author() {
		return (int) $this->post->post_author;
	}


	/**
	 * @inheritdoc
	 *
	 * @return int
	 * @since 2.5.11
	 */
	public function get_trid() {
		if( ! $this->is_translatable() ) {
			return 0;
		}
		return $this->wpml_service->get_post_trid( $this->get_id() );
	}


	/**
	 * @inheritdoc
	 *
	 * @return string
	 * @since Types 3.2
	 */
	public function get_status() {
		return $this->post->post_status;
	}


	/**
	 * Retrieve field groups that are displayed for this particular post.
	 *
	 * That may include groups assigned based on the post type, but also on the used page template or other factors.
	 *
	 * @return Toolset_Field_Group_Post[]
	 * @since Types 3.3
	 */
	public function get_field_groups() {
		// Offload this to the field group part of the code, because it's all about field group settings.
		return $this->group_post_factory->get_groups_for_element( $this );
	}


	/**
	 * @inheritdoc
	 *
	 * @param null|string[] $taxonomies
	 *
	 * @return int[]
	 * @since Types 3.3
	 */
	public function get_term_taxonomy_ids( $taxonomies = null ) {
		if ( null === $taxonomies ) {
			$post_type = $this->get_type();
			// Get slugs all taxonomies assigned to this post type.
			$taxonomies = array_map(
				function ( $taxonomy ) { return $taxonomy->name; },
				array_filter(
					get_taxonomies( array(), 'objects' ),
					function ( $taxonomy ) use ( $post_type ) {
						return in_array( $post_type, $taxonomy->object_type );
					}
				)
			);
		}

		return wp_get_object_terms( $this->get_id(), array_values( $taxonomies ), array( 'fields' => 'tt_ids' ) );
	}

	/**
	 * @inheritdoc
	 *
	 * @return string|null
	 * @since Types 3.3
	 */
	public function get_assigned_native_page_template() {
		$value = get_post_meta( $this->get_id(), self::NATIVE_PAGE_TEMPLATE_META_KEY, true );
		if( ! is_string( $value ) || empty( $value ) ) {
			return null;
		}

		return $value;
	}


	/**
	 * @inheritdoc
	 *
	 * @return int|null
	 * @since Types 3.3
	 */
	public function get_assigned_content_template() {
		$value = apply_filters( 'wpv_content_template_for_post', 0, get_post( $this->get_id() ) );
		if( ! is_numeric( $value ) || empty( $value ) ) {
			return null;
		}

		return (int) $value;
	}


	/**
	 * Preferred editor mode for the current post.
	 *
	 * @return string
	 * @since Types 3.2.2
	 */
	public function get_editor_mode() {
		$meta_value = get_post_meta( $this->get_id(), self::EDITOR_MODE_META_KEY, true );

		if( ! EditorMode::is_valid( $meta_value ) ) {
			return EditorMode::CLASSIC;
		}

		return $meta_value;
	}


	/**
	 * @return string Raw post content
	 * @since Types 3.2.2
	 */
	public function get_content() {
		return $this->post->post_content;
	}


	/**
	 * Set a specific editor mode for a particular post.
	 *
	 * @param string $editor_mode Valid constant from EditorMode.
	 */
	public function set_post_editor_mode( $editor_mode ) {
		if( ! EditorMode::is_valid( $editor_mode ) ) {
			throw new InvalidArgumentException();
		}

		update_post_meta( $this->get_id(), Toolset_Post::EDITOR_MODE_META_KEY, $editor_mode );
	}



	/**
	 * Switch a particular post to use the block editor.
	 *
	 * Save the post content in case the user wants to restore it later.
	 */
	public function switch_to_block_editor() {
		$this->set_post_editor_mode( EditorMode::BLOCK );

		// Use base64 to prevent postmeta sanitization which might break things.
		$safe_content = base64_encode( $this->get_content() );

		update_post_meta( $this->get_id(), self::POST_CONTENT_BEFORE_GUTENBERG_META_KEY, $safe_content );
	}


	/**
	 * Switch a particular post to use the classic editor.
	 *
	 * Allow to (optionally) restore the post content from before the block editor was used (if there is any).
	 *
	 * @param bool $restore_previous_content
	 */
	public function switch_to_classic_editor( $restore_previous_content ) {
		$this->set_post_editor_mode( EditorMode::CLASSIC );

		if( $restore_previous_content ) {
			$encoded_content = get_post_meta( $this->get_id(), self::POST_CONTENT_BEFORE_GUTENBERG_META_KEY, true );

			if( ! empty( $encoded_content ) ) {
				wp_update_post( array(
					'ID' => $this->get_id(),
					'post_content' => base64_decode( $encoded_content ),
				) );
			}
		}

		delete_post_meta( $this->get_id(), self::POST_CONTENT_BEFORE_GUTENBERG_META_KEY );
	}

}
