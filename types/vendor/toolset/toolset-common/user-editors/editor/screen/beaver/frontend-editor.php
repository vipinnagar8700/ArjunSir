<?php

class Toolset_User_Editors_Editor_Screen_Beaver_Frontend_Editor
	extends Toolset_User_Editors_Editor_Screen_Abstract {

	/**
	 * Let's activate "Views and Fields" button for any frontend-editor
	 * Not only for our defined 'mediums' like Content Template
	 */
	public function initialize() {
		if ( ! array_key_exists( 'fl_builder', $_REQUEST ) ) {
			return;
		}

		add_action( 'init', array( $this, 'register_frontend_editor_assets' ) );

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_editor_assets' ) );

		/* disable Toolset Starters "No Content Template assigned" message */
		add_filter( 'toolset_starter_show_msg_no_content_template', '__return_false' );
	}

	public function is_active() {
		if ( ! array_key_exists( 'fl_builder', $_REQUEST ) ) {
			return false;
		}
		
		$this->action();
		return true;
	}

	private function action() {
		// todo move to content-template frontend-editor as this is independent of the user editor
		// Caution! this depends on the current editor option name, as different editors might store different templates (?)
		// we need to change the frontend editor template
		add_filter( 'template_include', array( $this, 'frontend_editor_template_file' ) );
	}

	public function frontend_editor_template_file( $template_file ) {
		global $post;

		if( $post->post_type != $this->medium->get_slug() ) {
			return $template_file;
		}

		$template_selected_usage = $this->get_frontend_editor_template_file( $post->ID );
		
		add_filter( 'get_the_excerpt', array( $this, 'get_ontent_instead_of_excerpt' ) );
		
		return $template_selected_usage;
		
	}

	public function get_ontent_instead_of_excerpt( $excerpt ) {
		ob_start();
			the_content();
			$content = ob_get_contents();
		ob_end_clean();
		return $content;
	}

	private function get_frontend_editor_template_file( $ct_id ) {
		$stored_template = get_post_meta( $ct_id, $this->editor->get_option_name(), true );
		$stored_template = array_key_exists( 'template_path', $stored_template )
			? $stored_template['template_path']
			: false;

		if( $stored_template ) {
			return $stored_template;
		}

		// shouldn't happen
		return dirname( __FILE__ ) . '/frontend-editor-template-fallback.php';
	}

	public function register_frontend_editor_assets() {
		$toolset_assets_manager = Toolset_Assets_Manager::get_instance();
		$toolset_assets_manager->register_style(
			'toolset-user-editors-beaver-frontend-editor-style',
			TOOLSET_COMMON_URL . '/user-editors/editor/screen/beaver/frontend-editor.css',
			array(),
			TOOLSET_COMMON_VERSION
		);
	}

	public function enqueue_frontend_editor_assets() {
		$toolset_assets_manager = Toolset_Assets_Manager::get_instance();
		$toolset_assets_manager->enqueue_styles( array( 'toolset-user-editors-beaver-frontend-editor-style' ) );
		$toolset_assets_manager->enqueue_scripts( array( 'views-widgets-gui-script' ) );
	}
}