<?php


namespace OTGS\Toolset\Types\Post\Import;

use IToolset_Relationship_Definition;
use JsonSerializable;
use Toolset_Relationship_Definition;
use WP_Post;

/**
 * Association in the scope of importing.
 *
 * Note: This is also used by the script of the GUI. Keep that in mind on changes.
 *
 * @since 3.0
 */
class Association implements JsonSerializable {

	/** @var int child post id of meta key */
	private $meta_post_id;

	/** @var string */
	private $meta_key;

	/** @var string the association string (not the full meta value as meta value can have many association strings) */
	private $meta_association_string;

	/** @var IToolset_Relationship_Definition|string
	 * \Toolset_Relationship_Definition - all good
	 * string - the relationship could not be found, the string is the relationship slug, which does not exist
	 */
	private $relationship;

	/**
	 * @var WP_Post|string
	 * \WP_Post - all good
	 * string - the parent could not be found, the string is the GUID which cannot be resolved
	 */
	private $parent;

	/** @var WP_Post|null
	 * \WP_Post - all good
	 * null - the child could not be found (shouldn't happen as we export by child)
	 */
	private $child;

	/** @var WP_Post|string|null
	 * \WP_Post - All good
	 * string - Association should have an intermediary post, but it could not be found and the string is the GUID
	 * null - All good (the association do not need an intermediary post)
	 */
	private $intermediary;

	/** @var bool */
	private $is_already_imported = false;

	/** @var bool */
	private $has_missing_data = false;


	/**
	 * @param mixed $meta_post_id
	 */
	public function setMetaPostId( $meta_post_id ) {
		$this->meta_post_id = $meta_post_id;
	}


	/**
	 * @param mixed $meta_key
	 */
	public function setMetaKey( $meta_key ) {
		$this->meta_key = $meta_key;
	}


	/**
	 * @param mixed $meta_association_string
	 */
	public function setMetaAssociationString( $meta_association_string ) {
		$this->meta_association_string = $meta_association_string;
	}


	/**
	 * @param bool $is_already_imported
	 */
	public function setIsAlreadyImported( $is_already_imported ) {
		$this->is_already_imported = $is_already_imported;
	}


	/**
	 * @param string|IToolset_Relationship_Definition $relationship
	 */
	public function setRelationship( $relationship ) {
		$this->relationship = $relationship;
	}


	/**
	 * @param string|WP_Post $parent
	 */
	public function setParent( $parent ) {
		$this->parent = $parent;
	}


	/**
	 * @param null|WP_Post $child
	 */
	public function setChild( $child ) {
		$this->child = $child;
	}


	/**
	 * @param null|string|WP_Post $intermediary
	 */
	public function setIntermediary( $intermediary ) {
		$this->intermediary = $intermediary;
	}


	/**
	 * @param bool $has_missing_data
	 */
	public function setHasMissingData( $has_missing_data ) {
		$this->has_missing_data = $has_missing_data;
	}


	/**
	 * @return int
	 */
	public function getMetaPostId() {
		return $this->meta_post_id;
	}


	/**
	 * @return string
	 */
	public function getMetaKey() {
		return $this->meta_key;
	}


	/**
	 * @return string
	 */
	public function getMetaAssociationString() {
		return $this->meta_association_string;
	}


	/**
	 * @return Toolset_Relationship_Definition|null
	 */
	public function getRelationship() {
		if ( ! $this->relationship instanceof Toolset_Relationship_Definition ) {
			return null;
		}

		return $this->relationship;
	}


	/**
	 * @return WP_Post|null
	 */
	public function getParent() {
		if ( ! $this->parent instanceof WP_Post ) {
			return null;
		}

		return $this->parent;
	}


	/**
	 * @return null|WP_Post
	 */
	public function getChild() {
		if ( ! $this->child instanceof WP_Post ) {
			return null;
		}

		return $this->child;
	}


	/**
	 * @return bool
	 */
	public function hasIntermediary() {
		return $this->intermediary !== null;
	}


	/**
	 * @return WP_Post|null
	 */
	public function getIntermediary() {
		if ( ! $this->intermediary instanceof WP_Post ) {
			return null;
		}

		return $this->intermediary;
	}


	/**
	 * @return bool
	 */
	public function isAlreadyImported() {
		return $this->is_already_imported;
	}


	/**
	 * @return array
	 */
	public function jsonSerialize() {
		// posts of association
		$response = array(
			'meta' => array(
				'postId' => $this->meta_post_id,
				'key' => $this->meta_key,
				'associationString' => $this->meta_association_string,
			),
			'isAlreadyImported' => $this->is_already_imported,
			'hasMissingData' => $this->has_missing_data,
			'child' => $this->serializePost( $this->child ),
			'parent' => $this->serializePost( $this->parent ),
			'intermediary' => $this->serializePost( $this->intermediary ),
		);

		// relationship
		if ( $this->relationship instanceof Toolset_Relationship_Definition ) {
			$response['relationship'] = array(
				'isAvailable' => true,
				'slug' => $this->relationship->get_slug(),
				'pluralName' => $this->relationship->get_display_name_plural(),
				'singularName' => $this->relationship->get_display_name_singular(),
			);
		} else {
			$response['relationship'] = array(
				'isAvailable' => false,
				'slug' => $this->relationship,
				'pluralName' => '',
				'singularName' => '',
			);
		}

		return $response;
	}


	/**
	 * @return array
	 */
	public function toArray() {
		return $this->jsonSerialize();
	}


	/**
	 * @param WP_Post|string|null $post Post instance, GUID or null if a post is not available.
	 *
	 * @return array
	 */
	private function serializePost( $post = null ) {
		$post_array = array(
			'isAvailable' => true,
			'isRequired' => true,
			'id' => 0,
			'postTitle' => '',
			'guid' => '',
		);


		if ( $post instanceof WP_Post ) {
			$post_array['postTitle'] = $post->post_title;
			$post_array['id'] = $post->ID;

			return $post_array;
		}

		if ( is_string( $post ) && ! empty( $post ) ) {
			$post_array['guid'] = $post;
			$post_array['isAvailable'] = false;

			return $post_array;
		}

		$post_array['isAvailable'] = false;
		$post_array['isRequired'] = false;

		return $post_array;
	}

}
