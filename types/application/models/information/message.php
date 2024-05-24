<?php

use OTGS\Toolset\Types\Helper\Condition\ToolsetConditionWrapper;

/**
 * Represents a single piece of information that would show in a cell of the Toolset Dashboard tables
 * if all of its conditions are met.
 */
class Types_Information_Message {

	protected $id;

	protected $type = false;

	protected $conditions = array();

	public $priority;

	public $title;

	public $description;


	/**
	 * Type Set & Get
	 *
	 * @param int $id
	 */
	public function set_id( $id ) {
		$this->id = $id;
	}


	public function get_id() {
		return $this->id;
	}


	/**
	 * Type Set & Get
	 *
	 * @param string $type
	 */
	public function set_type( $type ) {
		switch ( $type ) {
			case 'information':
			case 'template':
			case 'archive':
			case 'views':
			case 'forms':
			case 'type':
			case 'fields':
			case 'taxonomies':
				$this->type = $type;
				break;
		}
	}


	public function get_type() {
		return $this->type;
	}


	/**
	 * Use this to add multiple conditions at ounce.
	 *
	 * @param string|Types_Helper_Condition|Types_Helper_Condition[]|false $conditions
	 *
	 * @return void
	 */
	public function add_conditions( $conditions ) {
		if ( $conditions === false ) {
			return;
		}

		if ( is_array( $conditions ) ) {
			foreach ( $conditions as $condition_class_name ) {
				$condition = new $condition_class_name();

				// Allow for conditions from Toolset Common to be used as well.
				if ( $condition instanceof Toolset_Condition_Interface ) {
					$condition = new ToolsetConditionWrapper( $condition );
				}

				$this->add_condition( $condition );
			}
		} else {
			$this->add_condition( $conditions );
		}
	}


	/**
	 * Add a condition to show the message.
	 *
	 * @param Types_Helper_Condition $condition
	 *
	 * @return Types_Information_Message
	 */
	public function add_condition( Types_Helper_Condition $condition ) {
		$this->conditions[] = $condition;

		return $this;
	}


	/**
	 * Check if all assigned conditions match
	 *
	 * @return bool
	 */
	public function valid() {
		foreach ( $this->conditions as $condition ) {
			if ( ! $condition->valid() ) {
				return false;
			}
		}

		return true;
	}


	/**
	 * Title Set & Get
	 *
	 * @param string $title
	 */
	public function set_title( $title ) {
		$this->title = $title;
	}


	public function get_title() {
		return $this->title;
	}


	/**
	 * Description Set & Get
	 *
	 * @param string|array $description
	 */
	public function set_description( $description ) {
		if ( ! is_array( $description ) ) {
			$this->description = array(
				array(
					'type' => 'paragraph',
					'content' => $description,
				),
			);

			return;
		}

		$on_post_edit_screen = isset( $_GET['post'] );

		foreach ( $description as &$element ) {
			// apply correct label
			if ( isset( $element['label'] )
				&& is_array( $element['label'] )
				&& array_key_exists( 'default', $element['label'] )
				&& array_key_exists( 'post-edit', $element['label'] )
			) {
				$element['label'] = $on_post_edit_screen
					? $element['label']['post-edit']
					: $element['label']['default'];
			}
		}
		unset( $element );

		$this->description = $description;
	}


	public function get_description() {
		return $this->description;
	}


	/**
	 * Import data
	 * see /application/data/information
	 *
	 * @param array $data
	 *
	 * @return void
	 */
	public function data_import( $data ) {
		if ( ! is_array( $data ) ) {
			return;
		}

		$default = array(
			'id' => false,
			'type' => false,
			'conditions' => false,
			'title' => false,
			'description' => false,
			'priority' => false,
		);

		$cfg = array_replace_recursive( $default, $data );

		$this->set_id( $cfg['id'] );
		$this->set_type( $cfg['type'] );
		$this->add_conditions( $cfg['conditions'] );
		$this->set_title( $cfg['title'] );
		$this->set_description( $cfg['description'] );
		$this->priority = $cfg['priority'];
	}


	/**
	 * Add link, used for example, documentation and how to resolve links
	 *
	 * @param array $target
	 * @param array $link
	 * @param bool $in_array
	 *  false for $target   = $link
	 *  true  for $target[] = $link
	 */
	protected function add_link( &$target, $link, $in_array = false ) {
		if ( isset( $link['label'], $link['link'] ) ) {
			$add = array(
				'label' => $link['label'],
				'link' => $link['link'],
			);
		} elseif ( isset( $link['label'], $link['dialog'] ) ) {
			$add = array(
				'label' => $link['label'],
				'dialog' => $link['dialog'],
			);
		} elseif ( count( $link, COUNT_RECURSIVE ) === 2 ) {
			$add = array(
				'label' => $link[0],
				'link' => $link[1],
			);
		}

		if ( isset( $link['class'] ) ) {
			$add['class'] = $link['class'];
		}

		if ( isset( $add ) ) {
			if ( $in_array ) {
				$target[] = $add;
			} else {
				$target = $add;
			}
		}

	}
}
