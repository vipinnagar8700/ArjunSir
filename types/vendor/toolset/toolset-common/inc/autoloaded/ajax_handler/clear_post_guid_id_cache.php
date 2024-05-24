<?php

use OTGS\Toolset\Common\Utils\TypesGuidIdGateway;

/**
 * AJAX callback for the clear_post_guid_id_cache troubleshooting section.
 *
 * @since Types 3.3.8
 */
class Toolset_Ajax_Handler_Clear_Post_Guid_Id_Cache extends Toolset_Ajax_Handler_Abstract {

	/** @var TypesGuidIdGateway */
	private $guid_id_gateway;


	/**
	 * Toolset_Ajax_Handler_Clear_Post_Guid_Id_Cache constructor.
	 *
	 * @param Toolset_Ajax $ajax_manager
	 * @param TypesGuidIdGateway $guid_id_gateway
	 */
	public function __construct( \Toolset_Ajax $ajax_manager, TypesGuidIdGateway $guid_id_gateway ) {
		parent::__construct( $ajax_manager );
		$this->guid_id_gateway = $guid_id_gateway;
	}


	/**
	 * Processes the Ajax call
	 *
	 * @param array $arguments Original action arguments.
	 *
	 * @return void
	 */
	function process_call( $arguments ) {
		$this->ajax_begin( array( 'nonce' => Toolset_Ajax::CALLBACK_CLEAR_POST_GUID_ID_CACHE ) );

		$result = new Toolset_Result_Set(
			$this->guid_id_gateway->truncate()
		);

		if( $result->has_message() ) {
			$message = $result->has_message();
		} elseif( $result->is_complete_success() ) {
			$message = __( 'The table has been truncated.', 'wpv-views' );
		} else {
			$message = __( 'An error has ocurred when truncating the table. Do you have Types active?', 'wpv-views' );
		}

		$this->ajax_finish( [
			'continue' => false,
			'message' => $message,
		], true );
	}


}

