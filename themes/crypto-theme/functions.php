<?php
function wfThemeSetup()
{
    add_theme_support('menus');
    add_theme_support('widgets');
    add_theme_support('custom-logo');
    add_theme_support('automatic-feed-links');
    add_theme_support('post-thumbnails');
    add_theme_support('featured-image');
    add_theme_support('woocommerce');
    add_theme_support('post-formats', array('video', 'audio', 'image', 'gallery'));

    // custom files include
    // API files

    // require_once(get_template_directory() . '/api/api.php');

    // Ajax Files

    // require_once(get_template_directory() . '/ajax/ajax.php');

    // Shortcode Files

    require_once(get_template_directory() . '/shortcodes/single-ads.php');

    // Postypes

    require_once(get_template_directory() . '/post-types/news.php');
    require_once(get_template_directory() . '/post-types/press-release.php');
    require_once(get_template_directory() . '/post-types/community.php');
    require_once(get_template_directory() . '/post-types/our-partners.php');
    require_once(get_template_directory() . '/post-types/discover.php');
    require_once(get_template_directory() . '/post-types/topics.php');
    require_once(get_template_directory() . '/post-types/people.php');
    require_once(get_template_directory() . '/post-types/leaders.php');
    //custom settings

    require_once(get_template_directory() . '/custom-settings/menu.php');
    require_once(get_template_directory() . '/custom-settings/login.php');
    require_once(get_template_directory() . '/custom-settings/register.php');
}
add_action('after_setup_theme', 'wfThemeSetup');
 
function add_custom_rewrite_rules() {
    add_rewrite_rule('^data/cryptocurrency/([^/]*)/?', 'index.php?pagename=data/cryptocurrency&crypto_name=$matches[1]', 'top');
}
add_action('init', 'add_custom_rewrite_rules');
 

function flush_rewrite_rules_on_activation() {
    add_custom_rewrite_rules();
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'flush_rewrite_rules_on_activation');

function search_topics() {
    $query = sanitize_text_field($_POST['query']);
    
    $args = array(
        'post_type' => 'topics',
        's' => $query,
        'posts_per_page' => -1
    );
    
    $search_query = new WP_Query($args);
    
    if ($search_query->have_posts()) {
		echo '        <div class="search_slider">';
        while ($search_query->have_posts()) {
            $search_query->the_post();
            $post_title = get_the_title();
            $post_link = get_permalink();
            $post_excerpt = get_the_excerpt();
            $post_thumbnail = get_the_post_thumbnail_url(get_the_ID(), 'full');
            $post_author = get_the_author();
            $post_author_link = get_author_posts_url(get_the_author_meta('ID'));
            $post_date = get_the_date('j F Y');
            
            echo '            <div>';
            echo '                <div class="hot_topics">';
            echo '                    <div class="hot_topic_img mb-3">';
            echo '                        <img src="' . esc_url($post_thumbnail) . '" class="img-fluid" alt="">';
            echo '                    </div>';
            echo '                    <div class="hot_topics_content">';
            echo '                        <p class="fs-16 fw-500 mb-2">';
            echo '                            <a href="' . esc_url($post_link) . '" class="hot_title_">' . esc_html($post_title) . '</a>';
            echo '                        </p>';
            echo '                        <p class="fs-14 hot_subtitle">' . esc_html($post_excerpt) . '</p>';
            echo '                        <div class="hot_users d-flex justify-content-between align-items-center pt-2">';
            echo '                            <div>';
            echo '                                <div class="d-flex align-items-center gap-2">';
            echo '                                    <div class="hot_user_image"><img alt="" src="' . esc_url(get_avatar_url(get_the_author_meta('ID'))) . '" class="avatar avatar-32 photo" height="32" width="32" decoding="async"/></div>';
            echo '                                    <a href="' . esc_url($post_author_link) . '" class="fs-13 black">' . esc_html($post_author) . '</a>';
            echo '                                </div>';
            echo '                            </div>';
            echo '                            <div>';
            echo '                                <p class="fs-13 mb-0"><i class="bi bi-clock pe-1"></i>' . esc_html($post_date) . '</p>';
            echo '                            </div>';
            echo '                        </div>';
            echo '                    </div>';
            echo '                </div>';
            echo '            </div>';
        }
		echo '        </div>';
    } else {
        echo 'No results found.';
    }
    
    wp_die(); // Required to terminate immediately and return a proper response
}
add_action('wp_ajax_search_topics', 'search_topics');
add_action('wp_ajax_nopriv_search_topics', 'search_topics');

function enqueue_inline_search_script() {
    ?>
    <script type="text/javascript">
        var ajax_params = {
            ajax_url: "<?php echo admin_url('admin-ajax.php'); ?>"
        };
    </script>
    <script type="text/javascript">
        jQuery(document).ready(function($) {
            function initializeSlider() {
                $('.search_slider').slick({
                    infinite: true,
                    slidesToShow: 3,
                    slidesToScroll: 1,
                    dots: false,
                    arrows: true,
                    autoplay: true,
                    autoplaySpeed: 3000
                });
            }

            $('#search-input').on('keyup', function() {
                var query = $(this).val();
                if (query.length >= 3) { // Start searching when at least 3 characters are typed
                    $.ajax({
                        url: ajax_params.ajax_url,
                        type: 'POST',
                        data: {
                            action: 'search_topics',
                            query: query
                        },
                        success: function(response) {
                            $('#search-results').html(response);
                            initializeSlider(); // Initialize the slider after AJAX content is loaded
                        }
                    });
                } else {
                    $('#search-results').html('');
                }
            });

            // Initialize Slick Slider on page load
            initializeSlider();
        });
    </script>
    <?php
}
add_action('wp_footer', 'enqueue_inline_search_script');

class Custom_Walker_Nav_Menu extends Walker_Nav_Menu {
    // Start Level
    function start_lvl(&$output, $depth = 0, $args = null) {
        $indent = str_repeat("\t", $depth);
        $classes = array('sub-menu');
        $class_names = join(' ', apply_filters('nav_menu_submenu_css_class', $classes, $args, $depth));
        $class_names = $class_names ? ' class="' . esc_attr($class_names) . '"' : '';
        $output .= "\n$indent<ul$class_names>\n";
    }

    // Start Element
    function start_el(&$output, $item, $depth = 0, $args = null, $id = 0) {
        $indent = ($depth) ? str_repeat("\t", $depth) : '';
        $classes = empty($item->classes) ? array() : (array) $item->classes;
        $class_names = join(' ', apply_filters('nav_menu_css_class', array_filter($classes), $item, $args, $depth));
        $class_names = $class_names ? ' class="' . esc_attr($class_names) . '"' : '';
        $output .= $indent . '<li' . $class_names .'>';

        $atts = array();
        $atts['title']  = ! empty($item->attr_title) ? $item->attr_title : '';
        $atts['href']   = ! empty($item->url) ? $item->url : '';

        // Add custom class to <a> element
        $atts['class']  = 'mega_menu_title ' . (!empty($item->classes) ? join(' ', $item->classes) : '');

        $attributes = '';
        foreach ( $atts as $attr => $value ) {
            if (!empty($value)) {
                $value = esc_attr($value);
                $attributes .= ' ' . $attr . '="' . $value . '"';
            }
        }

        $title = apply_filters('the_title', $item->title, $item->ID);

        $item_output = $args->before;
        $item_output .= '<a'. $attributes .'>';
        $item_output .= $args->link_before . $title . $args->link_after;
        $item_output .= '</a>';

        // Add menu item description if available
        if ( ! empty( $item->description ) ) {
            $item_output .= '<p class="menu-item-description">' . esc_html( $item->description ) . '</p>';
        }

        $item_output .= $args->after;

        $output .= apply_filters('walker_nav_menu_start_el', $item_output, $item, $depth, $args, $id);
    }
}