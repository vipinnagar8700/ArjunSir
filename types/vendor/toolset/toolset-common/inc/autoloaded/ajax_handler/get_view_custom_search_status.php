<?php

/**
 * Returns information about the status of the custom search for a given View:
 *      1. If it includes custom search.
 *      2. If it contains a submit button.
 *
 * @since 3.0.7
 */
class Toolset_Ajax_Handler_Get_View_Custom_Search_Status extends Toolset_Ajax_Handler_Abstract {
	/**
	 * @param array $arguments Original action arguments.
	 *
	 * @return void
	 */
	function process_call( $arguments ) {
		$this->ajax_begin(
			array(
				'nonce' => Toolset_Ajax::CALLBACK_GET_VIEW_CUSTOM_SEARCH_STATUS,
				'is_public' => false,
			)
		);

		$view_id = toolset_getpost( 'view_id', '0' );

		if ( '0' === $view_id ) {
			$this->ajax_finish( array( 'message' => __( 'Wrong or missing View ID.', 'wpv-views' ) ), false );
		} else {
			$view = WPV_View_Base::get_instance( $view_id );

			if ( null !== $view ) {
				global $WP_Views;

				$has_submit_button = false;

				$view_settings = null !== $view ? $view->view_settings : null;

				if ( isset( $view_settings['filter_meta_html'] ) ) {
					$filter_meta_html = $view_settings['filter_meta_html'];

					if ( strpos( $filter_meta_html, '[wpv-filter-submit' ) !== false ) {
						$has_submit_button = true;
					}
				}

				$output = array(
					'hasCustomSearch' => $WP_Views->does_view_have_form_controls( $view_id ),
					'hasSubmitButton' => $has_submit_button,
					'hasBlocks' => (bool) get_post_meta( $view_id, '_wpv_is_gutenberg_view', true ),
				);

				$this->ajax_finish( $output, true );
			} else {
				$this->ajax_finish( array( 'message' => __( 'Error while retrieving the View custom search status.', 'wpv-views' ) ), false );
			}
		}
	}
}
