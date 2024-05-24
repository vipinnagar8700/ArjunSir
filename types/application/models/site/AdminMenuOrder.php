<?php


namespace OTGS\Toolset\Types\Site;

/**
 * Class AdminMenuOrder
 *
 * Orders the wp admin menu by manipulating the given $wp_menu,
 * which allows far more accurate ordering than the wp default 'menu_position' allows.
 *
 * @package OTGS\Toolset\Types
 *
 * @since 3.2
 */
class AdminMenuOrder {

	/** @var array */
	private $wp_menu;

	/** @var array the restructured wp_menu*/
	private $reordered_menu;

	/** @var array collection of already reordered items */
	private $reordered_items = array();

	/** @var array keys should be the entry point and the values an array of the cpts plural labels,
	 * which should be placed after the entry, e.g:
	 *	'entry.php' => [ 'CPT 1 plural label, which should come after entry.php', 'CPT2 plural Label' ]
	 *	'another-entry.php' => [ 'CPT 3 plural label' ]
	 */
	private $types_cpts_order;


	/**
	 * AdminMenuOrder constructor.
	 *
	 * @param array $wp_menu use the global $wp_menu for this
	 * @param array $types_cpts_order
	 */
	public function __construct( $wp_menu, $types_cpts_order ) {
		$this->wp_menu = $wp_menu;
		$this->types_cpts_order = $types_cpts_order;
	}

	/**
	 * Menu with custom ordering for CPTs respected
	 *
	 * @return array|false
	 */
	public function get_ordered_menu() {
		if( $this->reordered_menu !== null ) {
			// already loaded
			return $this->reordered_menu;
		}

		if( ! $this->validate_wp_menu_compatibility() ) {
			// the give wp_menu is not compatible with this class
			// (maybe WP core changed or the caller did something wrong)
			return false;
		}

		$this->load_reordered_menu();
		return $this->reordered_menu;
	}

	/**
	 * Return the ordered menu in the format which WP filter "menu_order" expects
	 *
	 * @return array|false
	 */
	public function get_ordered_menu_for_wp_filter_menu_order() {
		if( ! $menu_ordered = $this->get_ordered_menu() ) {
			return false;
		}

		// the filter 'menu_order' expects a list with item entry points only
		$menu_ordered_using_filter_format = array();

		foreach( $menu_ordered as $item ) {
			$menu_ordered_using_filter_format[] = $item[2];
		}

		return $menu_ordered_using_filter_format;
	}

	/**
	 * The given $wp_menu must have a specific array to work with this class
	 *
	 * @return bool
	 */
	private function validate_wp_menu_compatibility() {
		if( ! is_array( $this->wp_menu ) ) {
			return false;
		}

		$unqiue_entry_points = array();
		$unique_labels = array();

		$key_label = 0;
		$key_entry_point = 2;

		foreach( $this->wp_menu as $item ) {
			/** @noinspection IssetConstructsCanBeMergedInspection */
			if(
				// no array
				! is_array( $item )
				// item[0](label) not set or no string
			    || ! isset( $item[ $key_label ] ) || ! is_string( $item[ $key_label ] )
				// item[0](label) is not empty, but already used
			    || ( ! empty( $item[ $key_label ] ) && isset( $unique_labels[ $item[ $key_label ] ] ) )
				// item[2](entry_point) not set or empty or no string
			    || ! isset( $item[ $key_entry_point ] ) || empty( $item[ $key_entry_point ] ) || ! is_string ( $item[ $key_entry_point ] )
				// item[2](entry_point) is already used
			    || isset( $unqiue_entry_points[ $item[ $key_entry_point ] ] )
			) {
				// invalid item
				return false;
			}

			if( ! empty( $item[0] ) ) {
				// store label if not empty (empty is fine as separators use no label)
				$unique_labels[ $item[0] ] = 'already used';
			}

			// store entry point to make sure it's not used by another
			$unqiue_entry_points[ $item[2] ] = 'already used';
		}

		return true;
	}

	/**
	 * Load the reordered menu with custom CPT order
	 */
	private function load_reordered_menu() {
		if( empty( $this->types_cpts_order ) ) {
			// no ordering set for Types cpts
			$this->reordered_menu = $this->wp_menu;
			return;
		}

		if( ! $menu = $this->get_ready_to_reorder_menu() ) {
			// the menu could not be rearranged for proper reordering
			$this->reordered_menu = $this->wp_menu;
			return;
		}

		foreach( $menu as $item ) {
			$this->add_to_reordered_menu( $item );
		}
	}

	/**
	 * To reorder the menu all items with dependencies must be called after their anchor is called
	 * Example: user wants to put Posts under Pages. To make this work Posts must come later than Pages
	 * in the array.
	 *
	 * @param array $rearranged_array
	 *
	 * @return array|false
	 */
	private function get_ready_to_reorder_menu( $rearranged_array = array() ) {
		$changes = 0;
		foreach( $this->wp_menu as $item ) {
			if( isset( $rearranged_array[ $item[2] ] ) ) {
				continue;
			}

			if( empty( $item[0] ) && ! empty( $rearranged_array ) ) {
				// separator (keep it where it is, except at the top of the array)
				$rearranged_array[ $item[2] ] = $item;
				continue;
			}

			$is_sorted = false;

			foreach( $this->types_cpts_order as $entry => $labels_to_add_after ) {
				if( in_array( $item[0], $labels_to_add_after, true ) ) {
					// the $item should be sorted
					$is_sorted = true;

					$anchor_item = $this->get_menu_item_by_entry_point( $entry );

					if(
						isset( $rearranged_array[ $entry ] )
						|| ( // special case: Books isset to be after Writers and Writers isset to be after Books,
							 // in this case we order as as the items are coming
							isset( $this->types_cpts_order[ $item[2] ] )
							&& is_array( $anchor_item ) && in_array( $anchor_item[0], $this->types_cpts_order[ $item[2] ], true )
						)
					) {
						// the anchor isset, so we can add our item
						$rearranged_array[ $item[2] ] = $item;
						$changes++;
						continue 2; // continue with the next $item
					}
				}
			}

			if( ! $is_sorted ) {
				// not sorted at all... the position doesn't matter in the rearranged array
				$rearranged_array[ $item[2] ] = $item;
				$changes++;
			}
		}

		if( count( $this->wp_menu ) === count( $rearranged_array ) ) {
			// all items are rearranged
			return array_values( $rearranged_array );
		}

		if( $changes === 0 ) {
			// just to be sure this will never end in an infinite loop...
			return false;
		}

		// continue rearranging
		return $this->get_ready_to_reorder_menu( $rearranged_array );
	}


	/**
	 * Add a menu item and all menu items, which should come after the given $item
	 *
	 * @param array $item
	 *
	 * @return void
	 */
	private function add_to_reordered_menu( $item ) {
		$entry_page = $item[2];

		if( isset( $this->reordered_items[ $entry_page ] ) ) {
			// already reordered
			return;
		}

		// add to reordered items
		$this->reordered_items[ $entry_page ] = $item;

		// add to reorderd menu
		$this->reordered_menu[] = $item;

		// check for menu items which should be below this one
		if( isset( $this->types_cpts_order[ $entry_page ] ) ) {
			foreach( $this->types_cpts_order[ $entry_page ] as $item_label ) {
				if( $menu_item = $this->get_menu_item_by_label( $item_label ) ) {
					$this->add_to_reordered_menu( $menu_item );
				}
			}
		}

	}

	/**
	 * Get menu item by label
	 *
	 * @param string $label
	 *
	 * @return bool|mixed
	 */
	private function get_menu_item_by_label( $label ) {
		foreach( $this->wp_menu as $item ) {
			if( isset( $item[0] ) && $item[0] === $label ) {
				return $item;
			}
		}

		return false;
	}

	/**
	 * Get menu item by entry point
	 *
	 * @param mixed $entry_point
	 *
	 * @return bool|mixed
	 */
	private function get_menu_item_by_entry_point( $entry_point ) {
		foreach( $this->wp_menu as $item ) {
			/** @noinspection TypeUnsafeComparisonInspection */
			if( isset( $item[2] ) && $item[2] == $entry_point ) {
				return $item;
			}
		}

		return false;
	}
}
