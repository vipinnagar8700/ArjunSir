<?php
use Essential_Addons_Elementor\Classes\Helper;
/**
 * Template Name: Style 2
 *
 * @var $cs_product
 * @var $image_size
 * @var $is_purchasable
 * @var $settings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="eael-cs-single-product">
    <div class="eael-cs-product-image">
		<?php
		echo wp_kses( $cs_product->get_image( $image_size ), Helper::eael_allowed_tags() );
		if ( $settings['eael_cross_sales_visibility_buttons'] === 'yes' ) {
			?>
            <div class="eael-cs-product-buttons <?php echo $is_purchasable ? 'eael-cs-purchasable' : ''; ?>">
                <a href="<?php echo esc_url( $cs_product->get_permalink() ); ?>"><i class="fas fa-eye"></i></a>
				<?php if ( $is_purchasable ) { ?>
                    <a href="<?php echo esc_url( $cs_product->add_to_cart_url() ); ?>" class="add_to_cart_button ajax_add_to_cart"
                       data-product_id="<?php echo esc_html( $cs_product->get_ID() ); ?>" data-quantity="1"></a>
				<?php } ?>
            </div>
		<?php } ?>
    </div>
	<?php if ( $settings['eael_cross_sales_visibility_title'] === 'yes' || $settings['eael_cross_sales_visibility_price'] === 'yes' ) { ?>
        <div class="eael-cs-product-info">
			<?php if ( $settings['eael_cross_sales_visibility_title'] === 'yes' ) { ?>
                <div class="eael-cs-product-title">
					<?php echo esc_html( $cs_product->get_title() ); ?>
                </div>
			<?php }
			if ( $settings['eael_cross_sales_visibility_price'] === 'yes' ) { ?>
                <div class="eael-cs-product-price">
					<?php echo wp_kses( $cs_product->get_price_html(), Helper::eael_allowed_tags() ); ?>
                </div>
			<?php } ?>
        </div>
	<?php } ?>
</div>
