<?php

/**
 * Backend Editor class for Gutenberg.
 *
 * Handles all the functionality needed to allow Gutenberg to work with Content Template editing on the backend.
 *
 * @since 2.5.9
 */
class Toolset_User_Editors_Editor_Screen_Gutenberg_Backend
	extends Toolset_User_Editors_Editor_Screen_Abstract {

	const CONST_WPV_SUPPORT_TEMPLATE_EXTENDED_USAGE = 'WPV_SUPPORT_TEMPLATE_EXTENDED_USAGE';

	const WPV_CONTENT_TEMPLATE_CSS_METABOX = 'wpv-content-template-css-metabox';
	const WPV_CONTENT_TEMPLATE_JS_METABOX = 'wpv-content-template-js-metabox';

	/**
	 * @var WPV_Content_Template
	 */
	private $template_object = null;

	/**
	 * @var WPV_Content_Template
	 */
	private $maybe_original_template_object = null;

	/**
	 * @var WPV_View_Base
	 */
	private $parent_view = null;

	/**
	 * @var WPV_WordPress_Archive_Frontend
	 */
	private $archive_frontend = null;

	public function initialize() {
		parent::initialize();

		add_action( 'init', array( $this, 'register_assets' ), 50 );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_assets' ), 50 );

		add_filter( 'toolset_filter_toolset_registered_user_editors', array( $this, 'register_user_editor' ) );
		add_filter( 'wpv_filter_wpv_layout_template_extra_attributes', array( $this, 'layout_template_attribute' ), 10, 2 );

		add_action( 'wpv_action_wpv_ct_inline_user_editor_buttons', array( $this, 'register_inline_editor_action_buttons' ) );

		// Priority 100 is selected here to prevent Fusion builder from disabling the new editor (Gutenberg) which is basically
		// done in priority 99.
		// This filter is only included in the Gutenberg plugin.
		add_filter( 'gutenberg_can_edit_post', array( $this, 'enable_gutenberg_for_this_content_template' ), 100, 2 );
		// This filter is only included in the core.
		add_filter( 'use_block_editor_for_post', array( $this, 'enable_gutenberg_for_this_content_template' ), 100, 2 );

		add_action( 'enqueue_block_editor_assets', array( $this, 'register_assets_for_gutenberg_compatibility' ) );
		add_action( 'enqueue_block_editor_assets', array( $this, 'register_metaboxes_for_gutenberg_compatibility' ) );

		add_filter( 'toolset_filter_toolset_gutenberg_user_editor_active', array( $this, 'user_editor_active' ) );

		// Gutenberg editor: save metaboxes for the CT.
		add_action( 'save_post', array( $this, 'save_metaboxes' ), 10, 2 );

		// Compatibility: the Theme Settings need to manage this as a CT editor page
		add_filter( 'toolset_theme_settings_force_backend_editor', array( $this, 'set_toolset_themes_backend_editor' ) );

		add_filter( 'wpv_filter_get_original_language_ct_post_id_from_translation', array( $this, 'maybe_get_original_language_post_id_from_translation' ) );

		// Compatibility: the metabox with the Divi Page Settings needs to be removed as it makes no sense for CTs.
		add_action( 'add_meta_boxes', array( $this, 'remove_divi_page_settings_metabox' ), PHP_INT_MAX - 1 );
	}

	/**
	 * Check if current editor is active.
	 *
	 * @return bool
	 *
	 * @refactoring Change the name of the following function as it is confusing.
	 *              Warning!!! This has to be changed for all editors, otherwise it will break the editors integration.
	 */
	public function is_active() {
		if ( ! $this->set_medium_as_post() ) {
			return false;
		}

		$this->action();

		return true;
	}

	private function action() {
		add_action( 'admin_enqueue_scripts', array( $this, 'action_enqueue_assets' ) );
		$this->medium->set_html_editor_backend( array( $this, 'html_output' ) );
		$this->medium->page_reload_after_backend_save();
	}

	public function register_assets() {
		// Content Template own edit screen assets
		$this->assets_manager->register_style(
			'toolset-user-editors-gutenberg-style',
			$this->constants->constant( 'TOOLSET_COMMON_URL' ) . '/user-editors/editor/screen/gutenberg/backend.css',
			array(),
			$this->constants->constant( 'TOOLSET_COMMON_VERSION' )
		);

		$this->assets_manager->register_style(
			'toolset-user-editors-gutenberg-editor-style',
			$this->constants->constant( 'TOOLSET_COMMON_URL' ) . '/user-editors/editor/screen/gutenberg/backend_editor.css',
			array( Toolset_Assets_Manager::STYLE_CODEMIRROR ),
			$this->constants->constant( 'TOOLSET_COMMON_VERSION' )
		);

		// Native post editor screen assets

		$this->assets_manager->register_script(
			'toolset-user-editors-gutenberg-script',
			$this->constants->constant( 'TOOLSET_COMMON_URL' ) . '/user-editors/editor/screen/gutenberg/backend_editor.js',
			array(
				'jquery',
				'underscore',
				Toolset_Assets_Manager::SCRIPT_CODEMIRROR,
				Toolset_Assets_Manager::SCRIPT_CODEMIRROR_CSS,
			),
			$this->constants->constant( 'TOOLSET_COMMON_VERSION' ),
			true
		);

		$gutenberg_script_i18n = array(
			'id' => toolset_getget( 'post' ),
			'killDissidentPosts' => array(
				'action' => 'wpv_ct_kill_dissident_posts',
				'nonce' => wp_create_nonce( 'ct_kill_dissident_posts' ),
				'buttonLabel' => __( 'Apply to all', 'wpv-views' ),
			),
			'suggestReload' => __( 'Please save your work and reload this editor to update the list of posts for previewing your design.', 'wpv-views' ),
		);

		$this->assets_manager->localize_script(
			'toolset-user-editors-gutenberg-script',
			'toolset_user_editors_gutenberg_script_i18n',
			$gutenberg_script_i18n
		);

		// Content Template as inline object assets
		$this->assets_manager->register_script(
			'toolset-user-editors-gutenberg-layout-template-script',
			$this->constants->constant( 'TOOLSET_COMMON_URL' ) . '/user-editors/editor/screen/gutenberg/backend_layout_template.js',
			array( 'jquery', 'views-layout-template-js', 'underscore' ),
			$this->constants->constant( 'TOOLSET_COMMON_VERSION' ),
			true
		);

		$gutenberg_layout_template_i18n = array(
			'template_editor_url' => admin_url( 'admin.php?page=ct-editor' ),
			'template_overlay' => array(
				'title' => sprintf( __( 'You created this template using %1$s', 'wpv-views' ), $this->editor->get_name() ),
				'button' => sprintf( __( 'Edit with %1$s', 'wpv-views' ), $this->editor->get_name() ),
				'discard' => sprintf( __( 'Stop using %1$s for this Content Template', 'wpv-views' ), $this->editor->get_name() ),
			),
		);

		$this->assets_manager->localize_script(
			'toolset-user-editors-gutenberg-layout-template-script',
			'toolset_user_editors_gutenberg_layout_template_i18n',
			$gutenberg_layout_template_i18n
		);
	}

	public function admin_enqueue_assets() {
		if ( $this->is_views_or_wpa_edit_page() ) {
			do_action( 'toolset_enqueue_scripts', array( 'toolset-user-editors-gutenberg-layout-template-script' ) );
		}
	}

	public function action_enqueue_assets() {
		do_action( 'toolset_enqueue_styles', array( 'toolset-user-editors-gutenberg-style' ) );
	}

	private function set_medium_as_post() {
		$medium_id  = $this->medium->get_id();

		if ( ! $medium_id ) {
			return false;
		}

		$medium_post_object = get_post( $medium_id );
		if ( null === $medium_post_object ) {
			return false;
		}

		$this->post = $medium_post_object;

		return true;
	}

	public function register_user_editor( $editors ) {
		$editors[ $this->editor->get_id() ] = $this->editor->get_name();
		return $editors;
	}

	/**
	 * Content Template editor output.
	 *
	 * Displays the Native Editor message and button to fire it up.
	 *
	 * @since 2.5.1
	 */
	public function html_output() {

		if ( ! isset( $_GET['ct_id'] ) ) {
			return 'No valid content template id';
		}

		ob_start();
		include_once( dirname( __FILE__ ) . '/backend.phtml' );
		$output = ob_get_contents();
		ob_end_clean();

		$admin_url = admin_url( 'admin.php?page=ct-editor&ct_id=' . esc_attr( $_GET['ct_id'] ) );
		$output .= '<p>'
				   . sprintf(
					   __( '%1$sStop using %2$s for this Content Template%3$s', 'wpv-views' ),
					   '<a href="' . esc_url( $admin_url ) . '&ct_editor_choice=basic">',
					   $this->editor->get_name(),
					   '</a>'
				   )
				   . '</p>';

		return $output;
	}

	public function register_inline_editor_action_buttons( $content_template ) {
		?>
		<button
			class="button button-secondary js-wpv-ct-apply-user-editor js-wpv-ct-apply-user-editor-<?php echo esc_attr( $this->editor->get_id() ); ?>"
			data-editor="<?php echo esc_attr( $this->editor->get_id() ); ?>"
			<?php disabled( $this->maybe_ct_is_built_with_gutenberg( $content_template->ID ) ); ?>
		>
			<?php echo esc_html( $this->editor->get_name() ); ?>
		</button>
		<?php
	}

	/**
	 * Set the builder used by a Content Template, if any.
	 *
	 * On a Content Template used inside a View or WPA loop output, we set which builder it is using
	 * so we can link to the CT edit page with the right builder instantiated.
	 *
	 * @param array   $attributes
	 * @param WP_POST $content_template
	 *
	 * @return array
	 *
	 * @since 2.5.1
	 */
	public function layout_template_attribute( $attributes, $content_template ) {
		if ( $this->maybe_ct_is_built_with_gutenberg( $content_template->ID ) ) {
			$attributes['builder'] = $this->editor->get_id();
		}
		return $attributes;
	}

	public function register_assets_for_gutenberg_compatibility() {
		if ( $this->maybe_ct_is_built_with_gutenberg() ) {
			do_action( 'toolset_enqueue_scripts', array( 'toolset-user-editors-gutenberg-script' ) );
			do_action( 'toolset_enqueue_styles', array( 'toolset-user-editors-gutenberg-editor-style' ) );

			/**
			 * Allow third parties to register and enqueue their own assets when a CT is edited with Gutenberg.
			 *
			 * @since Views 2.8
			 */
			do_action( 'wpv-action-content-template-enqueue-gutenberg-editor-assets' );
		}
	}

	public function register_metaboxes_for_gutenberg_compatibility() {
		if ( $this->maybe_ct_is_built_with_gutenberg() ) {
			$this->add_metaboxes();
		}
	}

	/**
	 * See "Toolset_User_Editors_Editor_Screen_Abstract::maybe_ct_is_built_with_editor".
	 *
	 * @param int $ct_id
	 *
	 * @return bool
	 */
	public function maybe_ct_is_built_with_gutenberg( $ct_id = null ) {
		if ( null !== $ct_id ) {
			return parent::maybe_ct_is_built_with_editor( $ct_id );
		}

		global $post;
		if (
			$post &&
			$post instanceof \WP_Post
		) {
			return parent::maybe_ct_is_built_with_editor( $post->ID );
		}

		return false;
	}

	/**
	 * Callback for the filter to check whether the current CT is using Gutenberg as user editor.
	 *
	 * @param bool $status
	 * @return bool
	 */
	public function user_editor_active( $status ) {
		return $this->maybe_ct_is_built_with_gutenberg();
	}

	/**
	 * Re-enable the new editor (Gutenberg) for this Content Template, if it uses Gutenberg as a Content Template builder.
	 *
	 * @param bool    $is_enabled The status of the new editor (Gutenberg) for the selected post type.
	 * @param WP_Post $post       The selected post type.
	 *
	 * @return bool
	 */
	public function enable_gutenberg_for_this_content_template( $is_enabled, $post ) {
		if (
			'view-template' === $post->post_type &&
			$this->maybe_ct_is_built_with_gutenberg( $post->ID )
		) {
			return true;
		}

		return $is_enabled;
	}

	/**
	 * Removes the "Divi Page Settings" metabox for CTs as it wouldn't make any sense to have this there.
	 *
	 * @param string $post_type The post type of the currently edited post.
	 */
	public function remove_divi_page_settings_metabox( $post_type ) {
		if ( $this->maybe_ct_is_built_with_gutenberg() ) {
			remove_meta_box( 'et_settings_meta_box_gutenberg', $post_type, 'side' );
		}
	}

	/**
	 * Register the right metaboxes in the CT Gutenberg editor:
	 * - If the CT comes from a View/WPA loop, a metabox linkint to it.
	 * - Otherwise, a metabox for each usage that the CT can have.
	 * - In any case the custom CSS metabox.
	 * - In any case, a metabox offering to return to the basic editor.
	 *
	 * @since Views 2.8
	 */
	public function add_metaboxes() {
		global $post;
		if (
			! $post
			|| ! $post instanceof \WP_Post
		) {
			return;
		}
		$this->template_object = WPV_Content_Template::get_instance( $post->ID );
		$this->maybe_original_template_object = WPV_Content_Template::get_instance( $this->maybe_get_original_language_post_id_from_translation( $post->ID ) );

		if ( null === $this->template_object ) {
			return;
		}

		if ( $this->template_object->is_owned_by_view ) {
			$this->parent_view = WPV_View_Base::get_instance( $this->template_object->loop_output_id );
		}

		if ( null !== $this->parent_view ) {
			add_meta_box( 'wpv-content-template-usage-metabox', __( 'Usage', 'wpv-views' ), array( $this, 'usage_owned_by_view_metabox' ), 'view-template', 'side', 'high' );
		} else {
			$this->archive_frontend = WPV_WordPress_Archive_Frontend::get_instance();

			if ( $this->has_cpt_usage() || $this->has_taxonomy_usage() ) {
				add_meta_box( 'wpv-content-template-usage-cpt-archive-metabox', __( 'Usage: post archives', 'wpv-views' ), array(
					$this,
					'usage_cpt_archive_metabox',
				), 'view-template', 'side', 'high' );
			}
			if ( $this->has_taxonomy_usage() || $this->has_cpt_usage() ) {
				add_meta_box( 'wpv-content-template-usage-taxonomy-archive-metabox', __( 'Usage: taxonomy archives', 'wpv-views' ), array(
					$this,
					'usage_taxonomy_archive_metabox',
				), 'view-template', 'side', 'high' );
			}
		}

		add_meta_box( self::WPV_CONTENT_TEMPLATE_CSS_METABOX, __( 'CSS editor', 'wpv-views' ), array( $this, 'custom_css_metabox' ), 'view-template', 'side', 'high' );

		add_meta_box( self::WPV_CONTENT_TEMPLATE_JS_METABOX, __( 'JS editor', 'wpv-views' ), array( $this, 'custom_js_metabox' ), 'view-template', 'side', 'high' );

		if (
			'classic' === apply_filters( 'toolset_views_editing_experience', 'classic' )
			|| 'mixed' === apply_filters( 'toolset_views_editing_experience', 'classic' )
		) {
			// Do not show this selector when using the pure blocks editing experience.
			add_meta_box( 'wpv-content-template-user-editor-metabox', __( 'Editor for this Template', 'wpv-views' ), array( $this, 'user_editor_metabox' ), 'view-template', 'side', 'high' );
		}

		/**
		 * Allow third parties to register their own metaboxes on a CT edited with Gutenberg.
		 *
		 * @since Views 2.8
		 */
		do_action( 'wpv-action-content-template-add-gutenberg-editor-metabox', $this->template_object );
	}

	/**
	 * Render the custom CSS metabox.
	 *
	 * @param WP_Post $post_object
	 * @since Views 2.8
	 */
	public function custom_css_metabox( $post_object ) {
		$extra_css = $this->template_object->get_template_extra_css();
		echo '<textarea id="wpv_template_extra_css" name="wpv_template_extra_css">';
		echo $extra_css;
		echo '</textarea>';
	}

	/**
	 * Render the custom JS metabox.
	 *
	 * @param WP_Post $post_object
	 *
	 * @since Views 3.2
	 */
	public function custom_js_metabox( $post_object ) {
		$extra_js = $this->template_object->get_template_extra_js();
		echo '<textarea id="wpv_template_extra_js" name="wpv_template_extra_js">';
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $extra_js;
		echo '</textarea>';
		echo '<div class="components-notice is-info">';
		echo '<div class="components-notice__content">';
		echo esc_html( __( 'This JS code won\'t affect the preview in the editor but only the frontend content.' ) );
		echo '</div>';
		echo '</div>';
	}

	/**
	 * Render the metabox offering to return to the basic editor.
	 *
	 * @param WP_Post $post_object
	 * @since Views 2.8
	 */
	public function user_editor_metabox( $post_object ) {
		// Both vars are used in the template, so ignored the phpcs warnings
		$admin_url = admin_url( 'admin.php?page=ct-editor&ct_id=' . esc_attr( $this->template_object->id ) . '&ct_editor_choice=basic&ct_origin=gutenberg' ); // phpcs:ignore
		$snapshot_id = get_post_meta( $this->template_object->id, 'wpv-ct-snapshot', true ); // phpcs:ignore
		ob_start();
		include_once dirname( __FILE__ ) . '/change-editor.phtml';
		$output = ob_get_contents();
		ob_end_clean();
		echo $output; // phpcs:ignore
	}

	/**
	 * Render the metabox when the Templae is owned by a View/WPA.
	 *
	 * @param WP_Post $post_object
	 * @since Views 2.8
	 */
	public function usage_owned_by_view_metabox( $post_object ) {
		if ( null === $this->parent_view ) {
			return;
		}

		if ( $this->parent_view->is_published ) {
			$edit_page = 'views-editor';
			if ( WPV_View_Base::is_archive_view( $this->parent_view->id ) ) {
				$edit_page = 'view-archives-editor';
			}
			$loop_template_notice = sprintf(
				__( 'This Content Template is used as the loop block for the %s <a href="%s" target="_blank">%s</a>.', 'wpv-views' ),
				$this->parent_view->query_mode_display_name,
				esc_attr( add_query_arg(
					array(
						'page' => $edit_page,
						'view_id' => $this->parent_view->id
					),
					admin_url( 'admin.php' )
				) ),
				$this->parent_view->title
			);

		} else {

			$loop_template_notice = sprintf(
				__( 'This Content Template is used as the loop block for the trashed %s %s.', 'wpv-views' ),
				$this->parent_view->query_mode_display_name,
				"<strong>{$this->parent_view->title}</strong>"
			);
		}

		printf( '<div class="wpv-advanced-setting"><p>%s</p></div>', $loop_template_notice );
	}

	/**
	 * Render the metabox for single pages usage.
	 *
	 * @param WP_Post $post_object
	 * @since Views 2.8
	 */
	public function usage_single_metabox( $post_object ) {
		if ( null === $this->archive_frontend ) {
			return;
		}
		$single_post_types = $this->archive_frontend->get_archive_loops( 'post_type', false, true, true );
		$dissident_posts = $this->template_object->dissident_posts;

		if ( count( $single_post_types ) > 0 ) {
			?><ul class="wpv-mightlong-list" style="padding:0 2px"><?php
			foreach ( $single_post_types as $post_type ) {
				$this->usage_item( $post_type, $post_type['single_ct'], $post_type['post_type_name'], 'single' );
			}
			?></ul>
			<?php
		} else {

		}
	}

	/**
	 * Render the metabox for post type archives usage.
	 *
	 * @param WP_Post $post_object
	 * @since Views 2.8
	 */
	public function usage_cpt_archive_metabox( $post_object ) {
		if ( null === $this->archive_frontend ) {
			return;
		}
		$custom_post_types_loops = $this->archive_frontend->get_archive_loops( 'post_type', false, true, false );

		if ( count( $custom_post_types_loops ) > 0 ) {
			?><ul class="wpv-mightlong-list" style="padding:0 2px"><?php
			foreach ( $custom_post_types_loops as $post_type ) {
				$this->usage_item( $post_type, $post_type['ct'], $post_type['post_type_name'], 'cpt-archive' );
			}
			?></ul><?php
		} else {

		}
	}

	/**
	 * Render the metabox for yaxonomy archives usage.
	 *
	 * @param WP_Post $post_object
	 * @since Views 2.8
	 */
	public function usage_taxonomy_archive_metabox( $post_object ) {
		if ( null === $this->archive_frontend ) {
			return;
		}
		$taxonomy_loops = $this->archive_frontend->get_archive_loops( 'taxonomy', false, true );

		if ( count( $taxonomy_loops ) > 0 ) {
			?><ul class="wpv-mightlong-list" style="padding:0 2px"><?php
			foreach ( $taxonomy_loops as $taxonomy ) {
				$this->usage_item( $taxonomy, $taxonomy['ct'], $taxonomy['slug'], 'taxonomy-archive' );
			}
			?></ul><?php
		} else {

		}
	}

	/**
	 * Render each single usage option.
	 *
	 * @param array $item
	 * @param int $item_id The ID of the CT assigned to this usage
	 * @param string $item_value
	 * @param string $group
	 * @since Views 2.8
	 */
	private function usage_item( $item, $item_id, $item_value, $group ) {
		$template_object_is_translation = (int) $this->template_object->id !== (int) $this->maybe_original_template_object->id;
		$template_object = $template_object_is_translation ?
			$this->maybe_original_template_object :
			$this->template_object;
		?>
		<li class="<?php echo esc_attr( $template_object_is_translation ? 'js-otgs-popover-tooltip' : '' ); ?>"
			title="<?php echo esc_attr( $template_object_is_translation ? __( 'Content Template usage can only be modified when editing the original language.', 'wpv-views' ) : '' ); ?>"
		>
			<label>
				<?php
					$checkbox_classname = 'js-wpv-content-template-usage-selector';
					// The following part decides whether the kill dissident posts button will appear or not (the button
					// is controlled by the existence of the "js-wpv-content-template-usage-selector-has-dissident".
					// This buttons should be hidden when editing the translation of a CT.
					if (
						! $template_object_is_translation &&
						'single' === $group
					) {
						if ( (int) $template_object->id === (int) $item_id ) {
							$dissident_posts = $template_object->dissident_posts;
							if ( toolset_getarr( $dissident_posts, $item_value, false ) ) {
								$checkbox_classname .= ' js-wpv-content-template-usage-selector-has-dissident';
							}
						} else {
							$checkbox_classname .= ' js-wpv-content-template-usage-selector-has-dissident';
						}
					}
					printf(
						'<input type="checkbox" autocomplete="off" class="%s" value="%s" name="wpv-content-template-usage[%s][]" %s %s/> ',
						esc_attr( $checkbox_classname ),
						esc_attr( $item_value ),
						esc_attr( $group ),
						checked( $item_id, $template_object->id, false ),
						// The checkbox of a usage item will be disabled when editing the translation of a Content Template.
						$template_object_is_translation ? 'disabled' : ''
					);

					echo $item['display_name'];
				?>
			</label>
		</li>
		<?php
	}

	/**
	 * Save the metaboxes from the CT Gutenberg editor.
	 *
	 * @param int $post_id
	 * @param WP_Post $post_object
	 * @since Views 2.8
	 */
	public function save_metaboxes( $post_id, $post_object ) {
		if (
			'view-template' !== $post_object->post_type
			|| 'editpost' !== toolset_getpost( 'action' )
		) {
			return;
		}

		$template_object = WPV_Content_Template::get_instance( $post_id );

		if ( null === $template_object ) {
			return;
		}

		if ( ! $this->maybe_ct_is_built_with_gutenberg( $post_id ) ) {
			// Note that this integration is loaded on the native post edit page,
			// which is used by other user editors to load their backend page builders:
			// when saving a post with those editors, the assignment data will never be posted.
			return;
		}

		$template_usage = toolset_getpost( 'wpv-content-template-usage', array() );
		$template_usage['single'] = toolset_getarr( $template_usage, 'single', array() );
		$template_usage['cpt-archive'] = toolset_getarr( $template_usage, 'cpt-archive', array() );
		$template_usage['taxonomy-archive'] = toolset_getarr( $template_usage, 'taxonomy-archive', array() );

		$transaction_data = array(
			'template_extra_css' => toolset_getpost( 'wpv_template_extra_css' ),
			'template_extra_js' => toolset_getpost( 'wpv_template_extra_js' ),
			'assigned_post_archives' => toolset_getarr( $template_usage, 'cpt-archive', array() ),
			'assigned_taxonomy_archives' => toolset_getarr( $template_usage, 'taxonomy-archive', array() ),
		);

		$template_object->update_transaction( $transaction_data, false );
	}

	/**
	 * Force the Toolset Themes to manage this CT gutenberg editor as a proper editor.
	 *
	 * @param bool $is_editor_page
	 * @since Views 2.8
	 */
	public function set_toolset_themes_backend_editor( $is_editor_page ) {
		global $pagenow;
		if (
			is_admin()
			&& (
				(
					'post.php' === $pagenow
					&& 'view-template' === get_post_type( toolset_getget( 'post', 0 ) )
					&& 'edit' === toolset_getget( 'action' )
					&& $this->maybe_ct_is_built_with_gutenberg( toolset_getget( 'post', 0 ) )
				) ||
				(
					'post-new.php' === $pagenow
					&& 'view-template' === toolset_getget( 'post_type' )
				)
			)
		) {
			return true;
		}
		return $is_editor_page;
	}

	/**
	 * Render the metabox for General usage.
	 *
	 * @param WP_Post $post_object
	 * @since Views 3.0
	 */
	public function general_metabox( $post_object ) {
		?>
		<div class="components-base-control">
			<label class="components-base-control__label" for="wpv-content-template-general-post-name"><?php esc_html_e( 'Name', 'wpv-views' ); ?></label>
			<input class="components-text-control__input" type="text" id="wpv-content-template-general-post-name" value="<?php echo esc_html( $post_object->post_title ); ?>" />
		</div>
		<?php
	}

	/**
	 * Retrieves the original post (Content Template) ID if the provided ID is the ID of the translated Content Template.
	 *
	 * @param  int $post_id The translation or the original post (Content Template) ID.
	 *
	 * @return int The original post (Content Template) ID.
	 */
	public function maybe_get_original_language_post_id_from_translation( $post_id ) {
		$element_type = 'post_' . get_post_type( $post_id );
		$trid = apply_filters( 'wpml_element_trid', null, $post_id, $element_type );
		$translations = apply_filters( 'wpml_get_element_translations', array(), $trid, $element_type );

		foreach ( $translations as $translation ) {
			if ( null === $translation->source_language_code ) {
				$post_id = $translation->element_id ? (int) $translation->element_id : $post_id;
				break;
			}
		}

		return $post_id;
	}


	/**
	 * Check if there is any assignments to CPT usage
	 *
	 * @return bool
	 */
	public function has_cpt_usage() {
		if (
			$this->toolset_constants->defined( self::CONST_WPV_SUPPORT_TEMPLATE_EXTENDED_USAGE )
			&& true === $this->toolset_constants->constant( self::CONST_WPV_SUPPORT_TEMPLATE_EXTENDED_USAGE )
		) {
			return true;
		}

		if ( null !== $this->wpv_settings ) {
			return $this->wpv_settings->has_ct_assigned_to_cpt_archive();
		}
		return false;
	}

	/**
	 * Check if there is any assignments to Taxonomy usage
	 *
	 * @return bool
	 */
	public function has_taxonomy_usage() {
		if (
			$this->toolset_constants->defined( self::CONST_WPV_SUPPORT_TEMPLATE_EXTENDED_USAGE )
			&& true === $this->toolset_constants->constant( self::CONST_WPV_SUPPORT_TEMPLATE_EXTENDED_USAGE )
		) {
			return true;
		}
		if ( null !== $this->wpv_settings ) {
			return $this->wpv_settings->has_ct_assigned_to_taxonomy_archive();
		}
		return false;
	}
}
