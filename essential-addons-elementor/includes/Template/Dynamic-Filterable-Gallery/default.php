<?php
use Essential_Addons_Elementor\Classes\Helper;
/**
 * Template Name: Default
 *
 */

if ( !defined( 'ABSPATH' ) ) {
    exit;
} // Exit if accessed directly

$helperClass                  = new Essential_Addons_Elementor\Pro\Classes\Helper();
$show_category_child_items    = ! empty( $settings['category_show_child_items'] ) && 'yes' === $settings['category_show_child_items'] ? 1 : 0;
$show_product_cat_child_items = ! empty( $settings['product_cat_show_child_items'] ) && 'yes' === $settings['product_cat_show_child_items'] ? 1 : 0;
$classes                      = $helperClass->get_dynamic_gallery_item_classes( $show_category_child_items, $show_product_cat_child_items );
$has_post_thumbnail           = has_post_thumbnail();

$image_clickable = 'yes' === $settings['eael_dfg_full_image_clickable'] && $settings['eael_fg_grid_style'] == 'eael-cards';

if ($settings['eael_fg_grid_style'] == 'eael-hoverer') {
        echo '<div class="dynamic-gallery-item ' . esc_attr(urldecode(implode(' ', $classes))) . '">
            <div class="dynamic-gallery-item-inner" data-itemid=" ' . esc_attr( get_the_ID() ) . ' ">
                <div class="dynamic-gallery-thumbnail">';
                    $thumb_url = $has_post_thumbnail ? wp_get_attachment_image_url(get_post_thumbnail_id(), $settings['image_size']) : \Elementor\Utils::get_placeholder_image_src();
                    $alt_text = $has_post_thumbnail ? get_post_meta(get_post_thumbnail_id(), '_wp_attachment_image_alt', true) : '';
                    echo '<img src="' . esc_url( $thumb_url ) . '" alt="' . esc_attr( $alt_text ) . '">';


                    if ('eael-none' !== $settings['eael_fg_grid_hover_style']) {
                        echo  '<div class="caption ' . esc_attr($settings['eael_fg_grid_hover_style']) . ' ">';
                            if ('true' == $settings['eael_fg_show_popup']) {
                                if ('media' == $settings['eael_fg_show_popup_styles']) {
                                    $thumb_url = wp_get_attachment_image_url(get_post_thumbnail_id(), 'full');
                                    echo '<a href="' . esc_url( $thumb_url ) . '" class="popup-media eael-magnific-link"></a>';
                                } elseif ('buttons' == $settings['eael_fg_show_popup_styles']) {
                                    echo '<div class="item-content">';
                                        $item_content = '';
                                        if($settings['eael_show_hover_title']) {
                                            $item_content .= '<h2 class="title"><a href="' . esc_url( get_the_permalink() ) . '"'. ( $settings['title_link_nofollow'] ? 'rel="nofollow"' : '' ) . '' . ( $settings['title_link_target_blank'] ? 'target="_blank"' : '' ) .'>' . get_the_title() . '</a></h2>';
                                        }
                                        if($settings['eael_show_hover_excerpt']) {
                                            $item_content .= '<p>' . wp_trim_words(strip_shortcodes(get_the_excerpt() ? get_the_excerpt() : get_the_content()), $settings['eael_post_excerpt'], '<a class="eael_post_excerpt_read_more" href="' . get_the_permalink() . '"'. ( $settings['read_more_link_nofollow'] ? 'rel="nofollow"' : '' ) . '' . ( $settings['read_more_link_target_blank'] ? 'target="_blank"' : '' ) .'> ' . $settings['eael_post_excerpt_read_more'] . '</a>') . '</p>';
                                        }
                                        echo wp_kses( $item_content, Helper::eael_allowed_tags() );
                                    echo '</div>';
                                    echo '<div class="buttons">';
                                        if (!empty($settings['eael_section_fg_zoom_icon'])) {
                                            $thumb_url = $has_post_thumbnail ? wp_get_attachment_image_url(get_post_thumbnail_id(), 'full') : \Elementor\Utils::get_placeholder_image_src();
                                            echo '<a href="'. esc_url( $thumb_url ) .'" class="eael-magnific-link">';

                                                if( isset($settings['eael_section_fg_zoom_icon']['url']) ) {
                                                    echo '<img class="eael-dnmcg-svg-icon" src="'.esc_url($settings['eael_section_fg_zoom_icon']['url']).'" alt="'.esc_attr(get_post_meta($settings['eael_section_fg_zoom_icon']['id'], '_wp_attachment_image_alt', true)).'" />';
                                                }else if ( ! empty( $settings['eael_section_fg_zoom_icon_new'] ) ) {
                                                    \Elementor\Icons_Manager::render_icon($settings['eael_section_fg_zoom_icon_new'], ['aria-hidden' => 'true']);
                                                } else {
                                                    echo '<i class="sss ' . esc_attr($settings['eael_section_fg_zoom_icon']) . '"></i>';
                                                }
                                            echo '</a>';
                                        }

                                        if (!empty($settings['eael_section_fg_link_icon'])) {
                                            echo  '<a href="' . esc_url( get_the_permalink() ) . '"'. ( $settings['link_nofollow'] ? 'rel="nofollow"' : '' ) . '' . ( $settings['link_target_blank'] ? 'target="_blank"' : '' ) .'>';
                                                if( isset($settings['eael_section_fg_link_icon']['url'])) {
                                                    echo '<img class="eael-dnmcg-svg-icon" src="'.esc_url($settings['eael_section_fg_link_icon']['url']).'" alt="'.esc_attr(get_post_meta($settings['eael_section_fg_link_icon']['id'], '_wp_attachment_image_alt', true)).'" />';
                                                }else if ( ! empty( $settings['eael_section_fg_link_icon_new'] ) ) {
                                                    \Elementor\Icons_Manager::render_icon($settings['eael_section_fg_link_icon_new'], ['aria-hidden' => 'true']);
                                                } else {
                                                    echo '<i class="' . esc_attr($settings['eael_section_fg_link_icon']) . '"></i>';
                                                }
                                            echo '</a>';
                                        }
                                    echo '</div>';
                                }
                            }
                        echo '</div>';
                    }
                echo '</div>
            </div>
        </div>';
} else if ($settings['eael_fg_grid_style'] == 'eael-cards') {
    echo '<div class="dynamic-gallery-item ' . esc_attr(implode(' ', $classes)) . '">
        <div class="dynamic-gallery-item-inner" data-itemid=" ' . esc_attr( get_the_ID() ) . ' ">';

		if ( $image_clickable ){
			echo '<a href="' . esc_url( get_the_permalink() ) . '"'. ( $settings['image_link_nofollow'] ? 'rel="nofollow"' : '' ) . '' . ( $settings['image_link_target_blank'] ? 'target="_blank"' : '' ) .'>';
		}
           echo '<div class="dynamic-gallery-thumbnail">';
                $thumb_url = $has_post_thumbnail ? wp_get_attachment_image_url(get_post_thumbnail_id(), $settings['image_size']) : \Elementor\Utils::get_placeholder_image_src();
                $alt_text = $has_post_thumbnail ? get_post_meta(get_post_thumbnail_id(), '_wp_attachment_image_alt', true) : '';
                echo '<img src="' . esc_url( $thumb_url ) . '" alt="' . esc_attr( $alt_text ) . '">';


                if ('media' == $settings['eael_fg_show_popup_styles'] && 'eael-none' == $settings['eael_fg_grid_hover_style']) {
                    $thumb_url = $has_post_thumbnail ? wp_get_attachment_image_url(get_post_thumbnail_id(), 'full') : \Elementor\Utils::get_placeholder_image_src();
                    echo '<a href="'. esc_url( $thumb_url ) .'" class="popup-only-media eael-magnific-link"></a>';
                }

                if ('eael-none' !== $settings['eael_fg_grid_hover_style'] && ! $image_clickable ) {
                    if ('media' == $settings['eael_fg_show_popup_styles']) {
                        echo '<div class="caption media-only-caption">';
                    } else {
                        echo '<div class="caption ' . esc_attr($settings['eael_fg_grid_hover_style']) . ' ">';
                    }
                    if ('true' == $settings['eael_fg_show_popup']) {
                        if ('media' == $settings['eael_fg_show_popup_styles']) {
                            $thumb_url = $has_post_thumbnail ? wp_get_attachment_image_url(get_post_thumbnail_id(), 'full') : \Elementor\Utils::get_placeholder_image_src();
                            echo '<a href="'. esc_url( $thumb_url ) .'" class="popup-media eael-magnific-link"></a>';
                        } elseif ('buttons' == $settings['eael_fg_show_popup_styles']) {
                            echo '<div class="buttons">';
                                if (!empty($settings['eael_section_fg_zoom_icon'])) {

                                    $thumb_url = $has_post_thumbnail ? wp_get_attachment_image_url(get_post_thumbnail_id(), 'full') : \Elementor\Utils::get_placeholder_image_src();
                                    echo  '<a href="'. esc_url( $thumb_url ) .'" class="eael-magnific-link">';

                                        if( isset($settings['eael_section_fg_zoom_icon']['url']) ) {
                                            echo '<img class="eael-dnmcg-svg-icon" src="'.esc_url($settings['eael_section_fg_zoom_icon']['url']).'" alt="'.esc_attr(get_post_meta($settings['eael_section_fg_zoom_icon']['id'], '_wp_attachment_image_alt', true)).'" />';
                                        }else if ( ! empty( $settings['eael_section_fg_zoom_icon_new'] ) ) {
                                            \Elementor\Icons_Manager::render_icon($settings['eael_section_fg_zoom_icon_new'], ['aria-hidden' => 'true']);
                                        }else {
                                            echo '<i class="' . esc_attr($settings['eael_section_fg_zoom_icon']) . '"></i>';
                                        }
                                    echo '</a>';
                                }

                                if (!empty($settings['eael_section_fg_link_icon'])) {
                                    echo  '<a href="' . esc_url( get_the_permalink() ) . '"'. ( $settings['link_nofollow'] ? 'rel="nofollow"' : '' ) . '' . ( $settings['link_target_blank'] ? 'target="_blank"' : '' ) .'>';
                                        if( isset($settings['eael_section_fg_link_icon']['url'])) {
                                            echo '<img class="eael-dnmcg-svg-icon" src="'.esc_url($settings['eael_section_fg_link_icon']['url']).'" alt="'.esc_attr(get_post_meta($settings['eael_section_fg_link_icon']['id'], '_wp_attachment_image_alt', true)).'" />';
                                        }else if ( ! empty( $settings['eael_section_fg_link_icon_new'] ) ) {
                                            \Elementor\Icons_Manager::render_icon($settings['eael_section_fg_link_icon_new'], ['aria-hidden' => 'true']);
                                        }else {
                                            echo '<i class="' . esc_attr($settings['eael_section_fg_link_icon']) . '"></i>';
                                        }
                                    echo '</a>';
                                }
                            echo '</div>';
                        }
                    }
                    echo '</div>';
                }
            echo '</div>';

		if ( $image_clickable ){
			echo '</a>';
		}

          echo ' <div class="item-content">';
             if($settings['eael_show_hover_title']) {
                echo '<h2 class="title"><a href="' . esc_url( get_the_permalink() ) . '"'. ( $settings['title_link_nofollow'] ? 'rel="nofollow"' : '' ) . '' . ( $settings['title_link_target_blank'] ? 'target="_blank"' : '' ) .'>' . wp_kses( get_the_title(), Helper::eael_allowed_tags() ) . '</a></h2>';
            } if($settings['eael_show_hover_excerpt']) {
                $content =  wp_trim_words(strip_shortcodes(get_the_excerpt() ? get_the_excerpt() : get_the_content()), $settings['eael_post_excerpt'], '<a class="eael_post_excerpt_read_more" href="' . get_the_permalink() . '"'. ( $settings['read_more_link_nofollow'] ? 'rel="nofollow"' : '' ) . '' . ( $settings['read_more_link_target_blank'] ? 'target="_blank"' : '' ) .'> ' . $settings['eael_post_excerpt_read_more'] . '</a>');
                 echo '<p>' . wp_kses( $content, Helper::eael_allowed_tags() ) . '</p>';
             }

                if (('buttons' == $settings['eael_fg_show_popup_styles']) && ('eael-none' == $settings['eael_fg_grid_hover_style'])) {
                    echo '<div class="buttons entry-footer-buttons">';
                        if (!empty($settings['eael_section_fg_zoom_icon'])) {
                            $attachment_url = wp_get_attachment_image_url(get_post_thumbnail_id(), 'full');
                            echo '<a href="' . esc_url( $attachment_url ) . '" class="eael-magnific-link"><i class="' . esc_attr($settings['eael_section_fg_zoom_icon']) . '"></i></a>';
                        }
                        if (!empty($settings['eael_section_fg_link_icon'])) {
                            echo '<a href="' . esc_url( get_the_permalink() ) . '"'. ( $settings['link_nofollow'] ? 'rel="nofollow"' : '' ) . '' . ( $settings['link_target_blank'] ? 'target="_blank"' : '' ) .'><i class="' . esc_attr($settings['eael_section_fg_link_icon']) . '"></i></a>';
                        }
                    echo '</div>';
                }
            echo '</div>
        </div>
    </div>';
}
