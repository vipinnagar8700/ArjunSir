<?php

/**
 * Handles AJAX calls to get the CRED Form block preview.
 *
 * @since 3.0.1
 */
class Toolset_Ajax_Handler_Get_Cred_Form_Block_Preview extends Toolset_Ajax_Handler_Abstract {
	/**
	 * The Toolset Forms preview renderer instance.
	 *
	 * @var OTGS\Toolset\Common\Interop\Shared\PageBuilders\ToolsetFormsPreviewRenderer
	 */
	private $forms_preview_renderer;

	private $constants;

	private $toolset_renderer;

	/**
	 * Toolset_Ajax_Handler_Get_Cred_Form_Block_Preview constructor.
	 *
	 * @param Toolset_Ajax $ajax_manager
	 * @param \OTGS\Toolset\Common\Interop\Shared\PageBuilders\ToolsetFormsPreviewRenderer|null $forms_preview_renderer
	 * @param Toolset_Constants|null $constants
	 * @param Toolset_Renderer|null $toolset_renderer
	 */
	public function __construct(
		Toolset_Ajax $ajax_manager,
		\OTGS\Toolset\Common\Interop\Shared\PageBuilders\ToolsetFormsPreviewRenderer $forms_preview_renderer = null,
		\Toolset_Constants $constants = null,
		\Toolset_Renderer $toolset_renderer = null
	) {
		parent::__construct( $ajax_manager );

		$this->constants = $constants
			? $constants
			: new \Toolset_Constants();

		$this->toolset_renderer = $toolset_renderer
			? $toolset_renderer
			: \Toolset_Renderer::get_instance();

		if ( null === $forms_preview_renderer ) {
			$forms_preview_renderer = new OTGS\Toolset\Common\Interop\Shared\PageBuilders\ToolsetFormsPreviewRenderer();
		}
		$this->forms_preview_renderer = $forms_preview_renderer;
	}

	/**
	 * @param array $arguments Original action arguments.
	 *
	 * @return void
	 */
	function process_call( $arguments ) {

		$this->ajax_begin(
			array(
				'nonce' => Toolset_Ajax::CALLBACK_GET_CRED_FORM_BLOCK_PREVIEW,
				'is_public' => true,
			)
		);

		$cred_form_id = (int) toolset_getpost( 'formID', '0' );

		if ( 0 === $cred_form_id ) {
			$this->ajax_finish( array( 'message' => __( 'Form ID not set.', 'wpv-views' ) ), false );
		} else {
			$cred_form = get_post( $cred_form_id );

			if ( null === $cred_form ) {
				$this->ajax_finish( array( 'message' => sprintf( __( 'Error while retrieving the form preview. The selected form (ID: "%s") was not found.', 'wpv-views' ), $cred_form_id ) ), false );
			} else {
				$cred_form_content = $this->forms_preview_renderer->render_preview( $cred_form );
				$cred_form_content .= $this->render_block_overlay( $cred_form_id, $cred_form->post_title, $cred_form->post_type );

				$result = array(
					'formID' => strval( $cred_form_id ),
					'formContent' => $cred_form_content,
				);

				$this->ajax_finish( $result, true );
			}
		}
	}

	/**
	 * Renders the Toolset Form Gutenberg block overlay for the block preview on the editor.
	 *
	 * @param string $form_id    The ID of the selected Form.
	 * @param string $form_title The title of the selected Form.
	 * @param string $form_type Form type.
	 *
	 * @return bool|string The markup for the editor block overlay rendering echo-ed or returned.
	 */
	public function render_block_overlay( $form_id, $form_title, $form_type ) {
		$renderer = $this->toolset_renderer;
		$template_repository = \Toolset_Output_Template_Repository::get_instance();
		$context = array(
			'module_title' => $form_title,
			'module_type' => __( 'Form', 'wpv-views' ),
		);
		// The edit link is only offered for users with proper permissions.
		if ( current_user_can( 'manage_options' ) ) {
			if ( 'cred_rel_form' === $form_type ) {
				$context['edit_link'] =admin_url( 'admin.php?page=cred_relationship_form&action=edit&id=' . $form_id );
			} else {
				$context['edit_link'] = get_edit_post_link( $form_id );
			}
		}

		$html = $renderer->render(
			$template_repository->get( $this->constants->constant( 'Toolset_Output_Template_Repository::PAGE_BUILDER_MODULES_OVERLAY' ) ),
			$context,
			false
		);

		return $html;
	}
}
