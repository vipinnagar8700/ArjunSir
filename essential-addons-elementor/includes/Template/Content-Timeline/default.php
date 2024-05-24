<?php

/**
 * Template Name: Default
 *
 */

use Essential_Addons_Elementor\Pro\Classes\Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly


echo '<div class="eael-content-timeline-block">
    <div class="eael-content-timeline-line">
        <div class="eael-content-timeline-inner"></div>
    </div>
    <div class="eael-content-timeline-img eael-picture ' . ( ( 'bullet' === $settings['eael_show_image_or_icon'] ) ? 'eael-content-timeline-bullet' : '' ) . '">';

echo wp_kses( $content['image'], Helper::eael_allowed_icon_tags() );

echo '</div>';

$eael_ct_content = '<div class="eael-content-timeline-content">';
if ( 'yes' == $settings['eael_show_title'] ) {
	$eael_ct_content .= '<' . \Essential_Addons_Elementor\Classes\Helper::eael_validate_html_tag( $settings['title_tag'] ) . ' class="eael-timeline-title"><a href="' . esc_url( $content['permalink'] ) . '"' . $content['nofollow'] . '' . $content['target_blank'] . '>' . $content['title'] . '</a></' . \Essential_Addons_Elementor\Classes\Helper::eael_validate_html_tag( $settings['title_tag'] ) . '>';
}

if ( ! empty( $content['image_linkable'] ) && $content['image_linkable'] === 'yes' ) {
	$eael_ct_content .= '<a href="' . esc_url( $content['permalink'] ) . '"' . $content['image_link_nofollow'] . '' . $content['image_link_target'] . '>';
}

$eael_ct_content .= $content['post_thumbnail'];

if ( ! empty( $content['image_linkable'] ) && $content['image_linkable'] === 'yes' ) {
	$eael_ct_content .= '</a>';
}

if ( 'yes' == $settings['eael_show_excerpt'] ) {
	$eael_ct_content .= $content['excerpt'];
}

$eael_ct_content .= $content['read_more_btn'];

$eael_ct_content .= '<span class="eael-date">';
$eael_ct_content .= $content['date'];
$eael_ct_content .= '</span>';
$eael_ct_content .= '</div></div>';

echo wp_kses( $eael_ct_content, Helper::eael_allowed_tags() );