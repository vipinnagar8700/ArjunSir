<?php

namespace OTGS\Toolset\Types\Compatibility\Yoast\Field;

use InvalidArgumentException;
use OTGS\Toolset\Types\Compatibility\Yoast\Field\Type\Image;
use OTGS\Toolset\Types\Compatibility\Yoast\Field\Type\MultiLine;
use OTGS\Toolset\Types\Compatibility\Yoast\Field\Type\PostReferenceField;
use OTGS\Toolset\Types\Compatibility\Yoast\Field\Type\SingleLine;
use OTGS\Toolset\Types\Compatibility\Yoast\Field\Type\URL;
use OTGS\Toolset\Types\Compatibility\Yoast\Field\Type\WYSIWYG;

/**
 * @since 3.1
 */
class Factory {

	/**
	 * @param string $type_slug
	 * @param string $slug
	 *
	 * @return IField
	 */
	public function createField( $type_slug, $slug = null ) {
		switch( $type_slug ) {
			case 'textfield':
				$field = new SingleLine();
				break;
			case 'textarea':
				$field = new MultiLine();
				break;
			case 'wysiwyg':
				$field = new WYSIWYG();
				break;
			case 'image':
				$field = new Image();
				break;
			case 'url':
				$field = new URL();
				break;
			case 'post':
				$field = new PostReferenceField();
				break;
			default:
				throw new InvalidArgumentException( 'No field for type "' . $type_slug . '" found.' );
		}

		if( $slug !== null ) {
			$field->setSlug( $slug );
		}

		return $field;
	}
}
