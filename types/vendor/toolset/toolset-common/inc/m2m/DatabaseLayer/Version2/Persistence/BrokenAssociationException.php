<?php

namespace OTGS\Toolset\Common\Relationships\DatabaseLayer\Version2\Persistence;

use Exception;

/**
 * Informs about an association that couldn't have been loaded or created.
 *
 * @since 4.0
 */
class BrokenAssociationException extends \RuntimeException {


	/** @var int|null */
	private $association_uid;


	/**
	 * BrokenAssociationException constructor.
	 *
	 * @param int $association_uid ID of the broken association if available.
	 * @param string $message
	 * @param int $code
	 * @param Exception|null $previous
	 */
	public function __construct( $association_uid, $message = "", $code = 0, Exception $previous = null ) {
		parent::__construct( $message, $code, $previous );
		$this->association_uid = $association_uid;
	}


	/**
	 * @return int|null ID of the broken association.
	 */
	public function get_association_uid() {
		return $this->association_uid;
	}


}
