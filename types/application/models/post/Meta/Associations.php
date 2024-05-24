<?php

namespace OTGS\Toolset\Types\Post\Meta;

use Toolset_Relationship_Definition;
use WP_Post;

/**
 * This class extracts all formating of the meta for associations.
 *
 * @since 3.0
 */
class Associations {

	// ATTENTION: this key is dynamic as the slug of the relationship will be appended
	const KEY_BASE = '_toolset_associations_';

	const KEY_RELATIONSHIP_PLACEHOLDER = '%relationship_slug%';

	const BETWEEN_PARENT_INTERMEDIARY = ' + ';

	const BETWEEN_MULTIPLE_ASSOCIATIONS = ', ';

	const ELEMENT_NOTATION_LEFT = '{!{';

	const ELEMENT_NOTATION_RIGHT = '}!}';

	/** @var string */
	private $_regex_elements;


	/**
	 * @param Toolset_Relationship_Definition|null $relationship
	 *
	 * @return string
	 */
	public function getKeyForRelationship( Toolset_Relationship_Definition $relationship = null ) {
		return $relationship
			? self::KEY_BASE . $relationship->get_slug()
			: self::KEY_BASE . self::KEY_RELATIONSHIP_PLACEHOLDER;
	}


	/**
	 * Metakey with wildcard ready for MYSQL query
	 * Agains any abstractrion, just because it would be too nasty to build this on every use outside
	 *
	 * @return string
	 */
	public function getKeyWithWildcardForMysql() {
		return str_replace( '_', '\_', self::KEY_BASE ) . '%';
	}


	/**
	 * @param WP_Post $parent
	 * @param WP_Post $intermediary
	 *
	 * @return string
	 */
	public function parentIntermediaryToMeta( WP_Post $parent, WP_Post $intermediary = null ) {
		$parent_guid = self::ELEMENT_NOTATION_LEFT . $parent->guid . self::ELEMENT_NOTATION_RIGHT;

		$intermediary_guid = $intermediary !== null
			? self::BETWEEN_PARENT_INTERMEDIARY
			. self::ELEMENT_NOTATION_LEFT
			. $intermediary->guid
			. self::ELEMENT_NOTATION_RIGHT
			: '';

		return $parent_guid . $intermediary_guid;
	}


	/**
	 * @param string $meta_string Must be the same format that $this->toMeta() outputs
	 *
	 * @return string|null
	 */
	public function getParentTitleOrGUIDByMeta( $meta_string ) {
		preg_match_all( $this->getRegexForElements(), $meta_string, $elements_by_notation, PREG_SET_ORDER );

		if ( empty( $elements_by_notation ) ) {
			// this means there was only one association with a parent and for this case no notation is used
			return $meta_string;
		}

		if ( ! isset( $elements_by_notation[0][1] ) ) {
			// something really unexpected
			return null;
		}

		// return parent GUID/Title
		return $elements_by_notation[0][1];
	}


	/**
	 * @param string $meta_string Must be the same format that $this->toMeta() outputs
	 *
	 * @return string|null
	 */
	public function getIntermediaryTitleOrGUIDByMeta( $meta_string ) {
		preg_match_all( $this->getRegexForElements(), $meta_string, $elements_by_notation, PREG_SET_ORDER );

		if ( count( $elements_by_notation ) !== 2 ) {
			// no intermediary
			return null;
		}

		if ( ! isset( $elements_by_notation[1][1] ) ) {
			// something really unexpected
			return null;
		}

		// return intermediary GUID/Title
		return $elements_by_notation[1][1];
	}


	/**
	 * @param string $meta_key_string
	 *
	 * @return string|null
	 */
	public function getRelationshipSlugByMeta( $meta_key_string ) {
		if ( strpos( $meta_key_string, self::KEY_BASE ) !== 0 ) {
			// no valid given (the $meta_string does not start with self::KEY)
			return null;
		}

		$relationship_slug = str_replace( self::KEY_BASE, '', $meta_key_string );

		if ( empty( $relationship_slug ) ) {
			return null;
		}

		return $relationship_slug;
	}


	/**
	 * Implode Array
	 * Nothing more than an agreement for the glue of implode
	 *
	 * @param array $array
	 *
	 * @return string
	 */
	public function arrayToString( $array ) {
		if ( count( $array ) === 1 ) {
			// only one association, this means we can remove our notations
			$first_association = reset( $array );

			if ( strpos( $first_association, self::BETWEEN_PARENT_INTERMEDIARY ) === false ) {
				// only a parent exists in the association - for this case we don't need the notations
				$first_association = trim( $first_association );

				// return the single association without notation
				return preg_replace( $this->getRegexForElements(), '$1', $first_association );
			}

			// return the single association
			return $first_association;
		}

		// more than one association
		return implode( self::BETWEEN_MULTIPLE_ASSOCIATIONS, $array );
	}


	/**
	 * Explode Array
	 * Nothing more than agreement for the glue of explode
	 *
	 * @param string $string
	 *
	 * @return array
	 */
	public function stringToArray( $string ) {
		return explode( self::BETWEEN_MULTIPLE_ASSOCIATIONS, $string );
	}


	/**
	 * @return string
	 */
	private function getRegexForElements() {
		if ( $this->_regex_elements === null ) {
			$this->_regex_elements = '#'
				. preg_quote( self::ELEMENT_NOTATION_LEFT )
				. '(.*)'
				. preg_quote( self::ELEMENT_NOTATION_RIGHT )
				. '#U';
		}

		return $this->_regex_elements;
	}
}
