<?php

namespace OTGS\Toolset\Types\Post\Import\Association\View;


use OTGS\Toolset\Common\Utility\Admin\Notices\Builder as NoticeBuilder;

/**
 * Class Notice
 * @package OTGS\Toolset\Types\Post\Import\Association\View
 *
 * @since 3.0
 */
class Notice {

	/** @var NoticeBuilder */
	private $notice_builder;

	/**
	 * Notice constructor.
	 *
	 * @param NoticeBuilder $notice_builder
	 */
	public function __construct( NoticeBuilder $notice_builder ) {
		$this->notice_builder = $notice_builder;
	}

	/**
	 * Show admin notice
	 */
	public function show() {
		if( isset( $_GET['page'] ) && $_GET['page'] == 'toolset-export-import' ) {
			// don't show the notice on the import/export page
			return;
		}

		$notice = $this->notice_builder->createNotice( 'toolset-associations-import', 'required-action' );
		$notice->set_is_dismissible_permanent( true );

		$notice_content =
			'<p><b>' . __( 'There are associations ready for import.' ) . '</b></p>' .
			'<p>'. sprintf(
				__( 'To complete the import process, go to %sToolset > Export / Import > Associations%s.', 'wpcf' ),
				'<a href="'. admin_url( 'admin.php?page=toolset-export-import&tab=associations' ) .'">'
				, '</a>' )
			.'</p>';

		$this->notice_builder->addNotice( $notice, $notice_content );
	}
}