<?php

namespace OTGS\Toolset\Common\Interop\Handler\WooCommerce;

use OTGS\Toolset\CRED\Controller\FieldsControl\Db as FieldsControlDb;

/**
 * WooCommerce integration with Forms and Types.
 *
 * It generates a Custom Field Group specific for WooCommerce with all its fields (most of them hidden)
 * This CFG will be used in both Views (searchs and sorting) and Forms.
 *
 * @see https://toolset.com/course-lesson/enabling-additional-woocommerce-fields-in-product-forms/
 * @since 4.2
 */
class Fields {

	/** @var string */
	const FIELD_GROUP_NAME = 'toolset-woocommerce-fields';

	/** @var string */
	const FIELD_GROUP_TITLE = 'Toolset WooCommerce';

	/** @var string */
	const WOOCOMMERCE_POST_TYPE = 'product';

	/** @var array<array> */
	private $woocommerce_fields;

	/** @var array<string> */
	const EXCLUDED_FIELDS_SORTING = [
		'_download_limit',
		'_download_expiry',
		'_regular_price',
		'_sale_price',
		'_sale_price_dates_from',
		'_sale_price_dates_to',
		'_stock',
		'_purchase_note',
		'_manage_stock',
		'_backorders',
		'_stock',
		'_sku',
		'_width',
		'_weight',
		'_length',
		'_stock_status',
	];

	/** @var array<string> */
	const EXCLUDED_FIELDS_FILTERING = [
		'postmeta__download_limit',
		'postmeta__download_expiry',
		'postmeta__regular_price',
		'postmeta__sale_price',
		'postmeta__sale_price_dates_from',
		'postmeta__sale_price_dates_to',
		'postmeta__stock',
		'postmeta__purchase_note',
		'postmeta__manage_stock',
		'postmeta__backorders',
		'postmeta__stock',
		'postmeta__sku',
		'postmeta__width',
		'postmeta__weight',
		'postmeta__length',
	];

	public function __construct() {
		$this->woocommerce_fields = [
			'_download_limit' => [
				/* translators: Limit of downloads for a WooCommerce product */
				'name' => __( 'Download limit', 'wpv-views' ),
				'type' => \Toolset_Field_Type_Definition_Factory::NUMERIC,
			],
			'_virtual' => [
				/* translators: A Virtual WooCommerce product */
				'name' => __( 'Virtual', 'wpv-views' ),
				'type' => \Toolset_Field_Type_Definition_Factory::CHECKBOX,
				'data' => [
					'set_value' => 'yes',
				],
			],
			'_downloadable' => [
				/* translators: A WooCommerce product is downloadable */
				'name' => __( 'Downloadable', 'wpv-views' ),
				'type' => \Toolset_Field_Type_Definition_Factory::CHECKBOX,
				'data' => [
					'set_value' => 'yes',
				],
			],
			'_download_expiry' => [
				/* translators: Time for a WooCommerce product download to expire */
				'name' => __( 'Download expiry', 'wpv-views' ),
				'type' => \Toolset_Field_Type_Definition_Factory::NUMERIC,
			],
			'_download_type' => [
				/* translators: A WooCommerce product is downloadable */
				'name' => __( 'Download type', 'wpv-views' ),
				'type' => \Toolset_Field_Type_Definition_Factory::RADIO,
				'data' => [
					'options' => [
						[
							'value' => '',
							/* translators: WooCommerce product type */
							'title' => __( 'Standard Product', 'wpv-views' ),
						],
						[
							'value' => 'application',
							/* translators: WooCommerce product type */
							'title' => __( 'Application', 'wpv-views' ),
						],
						[
							'value' => 'music',
							/* translators: WooCommerce product type */
							'title' => __( 'Music', 'wpv-views' ),
						],
					],
				],
			],
			'_regular_price' => [
				/* translators: WooCommerce product regular price */
				'name' => __( 'Regular price', 'wpv-views' ),
				'type' => \Toolset_Field_Type_Definition_Factory::NUMERIC,
			],
			'_sale_price' => [
				/* translators: WooCommerce product sale price */
				'name' => __( 'Sale price', 'wpv-views' ),
				'type' => \Toolset_Field_Type_Definition_Factory::NUMERIC,
			],
			'_sale_price_dates_from' => [
				/* translators: WooCommerce product sale price period: from date*/
				'name' => __( 'Sale price dates (from)', 'wpv-views' ),
				'type' => \Toolset_Field_Type_Definition_Factory::DATE,
			],
			'_sale_price_dates_to' => [
				/* translators: WooCommerce product sale price period: to date*/
				'name' => __( 'Sale price dates (to)', 'wpv-views' ),
				'type' => \Toolset_Field_Type_Definition_Factory::DATE,
			],
			'_sku' => [
				/* translators: Stock-keeping unit */
				'name' => __( 'SKU', 'wpv-views' ),
				'type' => \Toolset_Field_Type_Definition_Factory::TEXTFIELD,
			],
			'_manage_stock' => [
				/* translators: if the WooCommerce product manages stock */
				'name' => __( 'Manage stock', 'wpv-views' ),
				'type' => \Toolset_Field_Type_Definition_Factory::CHECKBOX,
				'data' => [
					'set_value' => 'yes',
				],
			],
			'_stock_status' => [
				/* translators: WooCommerce product stock status */
				'name' => __( 'Stock status', 'wpv-views' ),
				'type' => \Toolset_Field_Type_Definition_Factory::RADIO,
				'data' => [
					'options' => [
						[
							'value' => 'instock',
							/* translators: WooCommerce Product is in stock */
							'title' => __( 'In stock', 'wpv-views' ),
						],
						[
							'value' => 'outofstock',
							/* translators: WooCommerce Product is out of stock */
							'title' => __( 'Out of stock', 'wpv-views' ),
						],
						[
							'value' => 'onbackorder',
							/* translators: WooCommerce Product is on backorder */
							'title' => __( 'On backorder', 'wpv-views' ),
						],
					],
				],
			],
			'_stock' => [
				/* translators: WooCommerce product stock quantity */
				'name' => __( 'Stock quantity', 'wpv-views' ),
				'type' => \Toolset_Field_Type_Definition_Factory::NUMERIC,
			],
			'_backorders' => [
				/* translators: if a WooCommerce product allows backorder */
				'name' => __( 'Allow backorder', 'wpv-views' ),
				'type' => \Toolset_Field_Type_Definition_Factory::RADIO,
				'data' => [
					'options' => [
						[
							'value' => 'no',
							/* translators: In WooCommerce, do not allow backorders */
							'title' => __( 'Do not allow', 'wpv-views' ),
						],
						[
							'value' => 'notify',
							/* translators: In WooCommerce, allow backorders */
							'title' => __( 'Allow but notify customer', 'wpv-views' ),
						],
						[
							'value' => 'yes',
							/* translators: In WooCommerce, allow backorders */
							'title' => __( 'Allow', 'wpv-views' ),
						],
					],
				],
			],
			'_sold_individually' => [
				/* translators: WooCommerce Sold individually option */
				'name' => __( 'Sold individually', 'wpv-views' ),
				'type' => \Toolset_Field_Type_Definition_Factory::CHECKBOX,
				'data' => [
					'set_value' => 'yes',
				],
			],
			'_width' => [
				/* translators: WooCommerce product width */
				'name' => __( 'Dimensions width', 'wpv-views' ),
				'type' => \Toolset_Field_Type_Definition_Factory::NUMERIC,
			],
			'_weight' => [
				/* translators: WooCommerce product weight */
				'name' => __( 'Dimensions weight', 'wpv-views' ),
				'type' => \Toolset_Field_Type_Definition_Factory::NUMERIC,
			],
			'_length' => [
				/* translators: WooCommerce product length */
				'name' => __( 'Dimensions length', 'wpv-views' ),
				'type' => \Toolset_Field_Type_Definition_Factory::NUMERIC,
			],
			'_purchase_note' => [
				/* translators: WooCommerce product length */
				'name' => __( 'Purchase note', 'wpv-views' ),
				'type' => \Toolset_Field_Type_Definition_Factory::TEXTAREA,
			],
		];
	}

	/**
	 * Initializes the actions needed
	 */
	public function initialize() {
		add_action( 'after_setup_theme', [ $this, 'autoregister_woocommerce_fields' ], 11 );
		add_filter( 'wpcf_post_group_filter_settings', [ $this, 'hide_group_from_meta_boxes' ] );
		add_filter( 'wpv_filter_wpv_get_postmeta_keys', [ $this, 'get_woocommerce_fields_in_sorting_fields' ], 10, 2 );
		add_filter( 'wpv_filter_wpv_get_form_filters_shortcodes', [ $this, 'get_woocommerce_fields_for_filtering' ], 1000, 2 );
	}

	/**
	 * Process woocommerce fields regis
	 */
	public function autoregister_woocommerce_fields() {
		if ( wp_doing_ajax() || wp_doing_cron() || wp_is_json_request() || ! is_admin() ) {
			return;
		}
		$types_active = new \Toolset_Condition_Plugin_Types_Active();
		if ( $types_active->is_met() ) {
			$this->create_field_group();
		} else {
			$cred_active = new \Toolset_Condition_Plugin_Cred_Active();
			if ( $cred_active->is_met() ) {
				$this->assign_fields_using_forms();
			}
		}
	}

	/**
	 * Creates the field group and assign the fields
	 */
	private function create_field_group() {
		$post_group_factory = \Toolset_Field_Group_Factory::get_factory_by_domain( \Toolset_Element_Domain::POSTS );
		$field_group = $post_group_factory->load_field_group( self::FIELD_GROUP_NAME );
		if ( $field_group ) {
			return;
		}
		$post_fields_group = $post_group_factory->create( self::FIELD_GROUP_NAME, self::FIELD_GROUP_TITLE, 'publish' );
		$post_fields_group->assign_post_type( self::WOOCOMMERCE_POST_TYPE );
		$post_fields_group->set_purpose( \Toolset_Field_Group::PURPOSE_SYSTEM );
		$this->create_fields( $post_fields_group );
		if ( class_exists( '\OTGS\Toolset\Views\Controller\Cache\MetaFilters\Post' ) ) {
			delete_transient( \OTGS\Toolset\Views\Controller\Cache\MetaFilters\Post::GET_HOOK_HANDLE );
		}
	}

	/**
	 * Creates the fields for the CFG
	 *
	 * @param \Toolset_Field_Group_Post $post_fields_group Field Group
	 */
	private function create_fields( $post_fields_group ) {
		foreach ( $this->woocommerce_fields as $meta_key => $field_info ) {
			$factory = \Toolset_Field_Definition_Factory::get_factory_by_domain( \Toolset_Element_Domain::POSTS );
			$definition = $factory->meta_key_belongs_to_types_field( $meta_key, 'definition' );
			if ( null === $definition ) {
				$definition_factory = \Toolset_Field_Definition_Factory::get_factory_by_domain( \Toolset_Element_Domain::POSTS );
				$new_field_slug = $definition_factory->create_field_definition_for_existing_fields( $meta_key, $field_info['type'] );
				$field_definition = $factory->load_field_definition( $new_field_slug );
				if ( $field_definition ) {
					$post_fields_group->add_field_definition( $field_definition );
					$factory->set_field_definition_workaround( $new_field_slug, $definition );

					$definition = $field_definition->get_definition_array();
					$definition['name'] = $field_info['name'];
					if ( isset( $field_info['data'] ) ) {
						// Options key need to be format
						if ( isset( $field_info['data']['options'] ) ) {
							foreach( $field_info['data']['options'] as $index => $option ) {
								$field_info['data']['options'][ 'wpcf-fields-select-option-' . wpcf_unique_id( serialize( $option ) ) ] = $option;
								unset( $field_info['data']['options'][ $index ] );
							}
						}
						$definition['data'] = array_merge( $definition['data'], $field_info['data'] );
					}

					$factory->set_field_definition_workaround( $new_field_slug, $definition );
				}
			}
		}
	}

	/**
	 * Hides the group from the meta boxes.
	 *
	 * @param array $group Field Group
	 * @return array
	 */
	public function hide_group_from_meta_boxes( $group ) {
		if ( $group['slug'] === self::FIELD_GROUP_NAME ) {
			$group['__show_meta_box'] = false;
		}
		return $group;
	}

	/**
	 * Adds WooCommerce Fields to Sorting field list.
	 *
	 * @param Array $keys List of post meta keys
	 * @param int $limit Limit of the list?
	 * @return Array
	 */
	public function get_woocommerce_fields_in_sorting_fields( $keys, $limit = 512 ) {
		return array_merge( $keys, array_diff( array_keys( $this->woocommerce_fields ), self::EXCLUDED_FIELDS_SORTING ) );
	}

	/**
	 * Register the fields using Forms
	 */
	private function assign_fields_using_forms() {
		$fields_control_db = new FieldsControlDb();
		$product_fields = $fields_control_db->get_fields_per_post_type( self::WOOCOMMERCE_POST_TYPE );

		if ( ! empty( $product_fields) ) {
			return;
		}

		foreach ( $this->woocommerce_fields as $meta_key => $field_info ) {
			$field_data = array(
				'name' => $meta_key,
				'type' => $field_info['type'],
				'default' => '',
				'required' => false,
				'validate_format' => false,
				'include_scaffold' => true,
				'options' => [],
			);
			$fields_control_db->set_field( $field_data, self::WOOCOMMERCE_POST_TYPE );
		}
	}

	/**
	 * Filters the list of fields displayed in Views Filters
	 *
	 * @param array<array> $fields Fields to display
	 * @return
	 */
	public function get_woocommerce_fields_for_filtering( $fields ) {
		if ( isset( $fields['wpv-control-postmeta']['custom_search_filter_subgroups'] ) ) {
			foreach ( $fields['wpv-control-postmeta']['custom_search_filter_subgroups'] as $index => $post_metas ) {
				if ( isset( $post_metas['custom_search_filter_items']['postmeta__download_limit'] ) ) {
					// WooCommerce fields
					foreach ( self::EXCLUDED_FIELDS_FILTERING as $field_key ) {
						unset( $fields['wpv-control-postmeta']['custom_search_filter_subgroups'][ $index ]['custom_search_filter_items'][ $field_key ] );
					}
				}
			}
		}
		return $fields;
	}
}
