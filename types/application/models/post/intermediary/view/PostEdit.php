<?php

namespace OTGS\Toolset\Types\Model\Post\Intermediary\View;

use OTGS\Toolset\Types\Controller\Ajax\Handler\Intermediary\Result;
use OTGS\Toolset\Types\Model\Post\Intermediary\Request;
use Toolset_Element_Exception_Element_Doesnt_Exist;
use Toolset_Relationship_Role_Child;
use Toolset_Relationship_Role_Parent;
use Types_Ajax;
use Types_Asset_Manager;
use Types_Helper_Twig;

/**
 * @since 3.0
 */
class PostEdit {

	/**
	 * @var string
	 */
	private $metaBoxId = 'toolset_intermediary_parent_child';

	/**
	 * @var string
	 */
	private $metaBoxTitle;

	/**
	 * @var Request
	 */
	private $request;


	/**
	 * @var Types_Helper_Twig
	 */
	private $twig;


	/**
	 * EditPostIntermediary constructor.
	 *
	 * @param Types_Helper_Twig $twig
	 */
	public function __construct( Types_Helper_Twig $twig ) {
		$this->twig = $twig;
		$this->metaBoxTitle = __( 'Intermediary post of', 'wpcf' );
	}


	/**
	 * Render
	 *
	 * @param Request $request
	 */
	public function render( Request $request ) {
		$this->request = $request;

		if ( $request->getIntermediaryPost() ) {
			// existing post
			add_action( 'add_meta_boxes', array( $this, 'addMetaBoxForParentAndChild' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'adminScripts' ) );
			add_action( 'admin_print_scripts', array( $this, 'adminScriptsData' ) );
		} else {
			// new post
			add_action( 'add_meta_boxes', array( $this, 'addMetaBoxForParentAndChildNewPost' ) );
		}
	}


	public function addMetaBoxForParentAndChildNewPost() {
		// Add meta box so that a Content Template can be set for a post
		$self = $this;
		add_meta_box( $this->metaBoxId, $this->metaBoxTitle, static function () use ( $self ) {
			$parent_post_types_names = $self->getSingularNamesByPostTypes(
				$self->request->getRelationshipDefinition()->get_parent_type()->get_types()
			);

			$child_post_types_names = $self->getSingularNamesByPostTypes(
				$self->request->getRelationshipDefinition()->get_child_type()->get_types()
			);

			// display the template
			echo $self->twig->render(
				'/post/intermediary/post-new.twig',
				array(
					'parent_post_types_names' => $parent_post_types_names,
					'child_post_types_names' => $child_post_types_names,
				)
			);

		}, null, 'side', 'high' );
	}


	/**
	 * Metabox Display
	 */
	public function addMetaBoxForParentAndChild() {
		$self = $this;

		// Add meta box so that a Content Template can be set for a post
		add_meta_box( $this->metaBoxId, $this->metaBoxTitle, static function () use ( $self ) {
			// array of parent post types
			$parent_post_types = $self->request->getRelationshipDefinition()->get_parent_type()->get_types();

			// array of parent singular labels
			$parent_post_types_names = $self->getSingularNamesByPostTypes( $parent_post_types );

			// parent post (or null if no assignment yet)
			$association = $self->request->getAssociation();

			try {
				$parent_post = $association
					? $association->get_element( new Toolset_Relationship_Role_Parent() )->get_underlying_object()
					: null;
			} catch ( Toolset_Element_Exception_Element_Doesnt_Exist $e ) {
				$parent_post = null;
			}

			// array of child post types
			$child_post_types = $self->request->getRelationshipDefinition()->get_child_type()->get_types();

			// array of child singular labels
			$child_post_types_names = $self->getSingularNamesByPostTypes( $child_post_types );

			// child post (or null if not assignment yet)
			try {
				$child_post = $association
					? $association->get_element( new Toolset_Relationship_Role_Child() )->get_underlying_object()
					: null;
			} catch ( Toolset_Element_Exception_Element_Doesnt_Exist $e ) {
				$child_post = null;
			}

			// display the template
			echo $self->twig->render(
				'/post/intermediary/post-edit.twig',
				array(
					'intermediary_post' => $self->request->getIntermediaryPost()->get_underlying_object(),
					'parent_post' => $parent_post,
					'parent_post_edit_url' => $parent_post ? get_edit_post_link( $parent_post->ID ) : '',
					'parent_post_types' => $parent_post_types,
					'parent_post_types_names' => $parent_post_types_names,
					'child_post' => $child_post,
					'child_post_edit_url' => $child_post ? get_edit_post_link( $child_post->ID ) : '',
					'child_post_types' => $child_post_types,
					'child_post_types_names' => $child_post_types_names,
				)
			);

		}, null, 'side', 'high' );
	}


	/**
	 * Scripts
	 */
	public function adminScripts() {
		if ( function_exists( 'wpcf_edit_post_screen_scripts' ) ) {
			wpcf_edit_post_screen_scripts();
		}

		wp_enqueue_script(
			'toolset-types-intermediary-post-parent-child',
			TYPES_RELPATH . '/public/post/intermediary/post-edit.js',
			array(
				'jquery',
				'underscore',
				Types_Asset_Manager::SCRIPT_KNOCKOUT,
				Types_Asset_Manager::SCRIPT_UTILS,
			),
			TYPES_VERSION
		);
	}


	/**
	 * Data passing to JS
	 */
	public function adminScriptsData() {
		$types_settings_action = Types_Ajax::get_instance()
			->get_action_js_name( Types_Ajax::CALLBACK_INTERMEDIARY_PARENT_CHILD );

		$data = array(
			'relationshipSlug' => $this->request->getRelationshipDefinition()->get_slug(),
			'intermediaryPost' => $this->request->getIntermediaryPost(),
			'association' => $this->request->getAssociation(),
			'intermediaryId' => $this->request->getIntermediaryPost()->get_underlying_object()->ID,
			'action' => array(
				'name' => $types_settings_action,
				'nonce' => wp_create_nonce( $types_settings_action ),
				'responseStatus' => array(
					'success' => Result::RESULT_SUCCESS,
					'conflict' => Result::RESULT_CONFLICT,
					'domError' => Result::RESULT_DOM_ERROR,
					'systemError' => Result::RESULT_SYSTEM_ERROR,
				),
			),
			'select2' => array(
				'posts_per_load' => 10,
			),
		);
		echo '<script id="toolset-types-intermediary-post-parent-child-data" type="text/plain">'
			. base64_encode( wp_json_encode( $data ) )
			. '</script>';
	}


	/**
	 * Input a list of post types to get a list of their singular names
	 *
	 * @param array $post_types
	 *
	 * @return array
	 */
	protected function getSingularNamesByPostTypes( array $post_types ) {
		return array_filter(
			array_map( static function ( $post_type ) {
				if ( $post_type = get_post_type_object( $post_type ) ) {
					return $post_type->labels->singular_name;
				}
				return null;
			}, $post_types ),
			static function ( $label ) {
				return ! empty( $label );
			}
		);
	}
}
