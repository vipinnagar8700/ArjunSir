<?php

namespace OTGS\Toolset\Types\Page\Extension\RelatedContent;


use Types_Page_Extension_Related_Content_Direct_Edit_Status;

/**
 * Factory for Types_Page_Extension_Related_Content_Direct_Edit_Status to allow dependency injection and unit testing.
 *
 * @package OTGS\Toolset\Types\Page\Extension\RelatedContent
 * @since 3.1.1
 */
class DirectEditStatusRepository {

	private $status_per_user = [];


	/**
	 * @param null|int $user_id
	 *
	 * @return Types_Page_Extension_Related_Content_Direct_Edit_Status
	 */
	public function create( $user_id ) {
		if ( ! array_key_exists( $user_id, $this->status_per_user ) ) {
			$this->status_per_user[ $user_id ] = new Types_Page_Extension_Related_Content_Direct_Edit_Status( $user_id );
		}
		return $this->status_per_user[ $user_id ];
	}


}
