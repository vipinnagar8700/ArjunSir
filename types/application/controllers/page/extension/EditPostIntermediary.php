<?php

namespace OTGS\Toolset\Types\Controller\Page\Extension;

use OTGS\Toolset\Types\Model\Post\Intermediary\Request;
use OTGS\Toolset\Types\Model\Post\Intermediary\View\PostEdit as ViewPostEdit;

/**
 * Class EditPostIntermediary
 * @package OTGS\Toolset\Types\Controller\Page\Extension
 *
 * @since 3.0
 */
class EditPostIntermediary {

	/**
	 * @var Request
	 */
	private $request;

	/**
	 * @var ViewPostEdit
	 */
	private $view;

	/**
	 * EditPostIntermediary constructor.
	 *
	 * @param Request $request
	 * @param ViewPostEdit $view
	 */
	public function __construct( Request $request, ViewPostEdit $view ) {
		$this->request = $request;
		$this->view    = $view;

		// Meta Box
		if( isset( $_REQUEST['post'] ) ) {
			$this->metaBoxPostEdit();
		} elseif( $_REQUEST['post_type'] ) {
			$this->metaBoxPostNew();
		}
	}

	/**
	 * Metabox for edit intermediary post.
	 */
	private function metaBoxPostEdit() {
		try {
			$this->view->render( $this->request );
		} catch( \Toolset_Element_Exception_Element_Doesnt_Exist $e ) {
			// should not happen, but if so, nothing to break the script for
			return;
		} catch( \Exception $e ) {
			// something unexpected went wrong
			if( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( $e->getMessage() );
			}

			return;
		}
	}


	/**
	 * Metabox for new intermediary post.
	 */
	private function metaBoxPostNew() {
		try {
			if( ! isset( $_REQUEST['post_type'] ) ) {
				// core change...
				return;
			}

			$this->request->setPostTypeSlug( $_REQUEST['post_type'] );

			$this->view->render( $this->request );
		} catch( \Exception $e ) {
			// something unexpected went wrong
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( $e->getMessage() );
			}

			return;
		}
	}
}
