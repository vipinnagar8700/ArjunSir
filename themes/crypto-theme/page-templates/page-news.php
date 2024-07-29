<?php get_header(); 
/*
Template Name: News
*/

$ads_image = get_field( "ads_image" );
$ads_text = get_field( "ads_text" );
$ads_link = get_field( "ads_link" );
$news_heading = get_field( "news_heading" );
$news_left_side_heading = get_field( "news_left_side_heading" );
$news_center_heading = get_field( "news_center_heading" );
$news_right_side_heading = get_field( "news_right_side_heading" );
$news_right_side_text = get_field( "news_right_side_text" );
$more_news_heading = get_field( "more_news_heading" );


?>
 
      <!-- hype loot section  -->
      <section class="hype_loot text-center ">
         <div class="container">
            <div class="row">
               <div class="col-md-12">
                   <a href="<?php echo $ads_link ?>">
            <div class="d-inline-block d-md-flex text-center  justify-content-center align-items-center gap-2 ">
               <div><img src="<?php echo $ads_image ?>" class="img-fluid hype_loots " alt=""></div>
               <div>
                  <p class="m-0 "><?php echo $ads_text ?></p>
               </div>
            </div>
				 </a>
               </div>
            </div>
         </div>
      </section>
      <!-- mini banner section  -->
      <section class="mini_banner">
         <div class="container">
            <div class="row">
               <div class="col-md-8">
                  <h2 class="global_heading"><?php echo $news_heading ?></h2>
               </div>
            </div>
         </div>
      </section>
      <!-- mini banner section end -->
      <!-- news listing  -->
      <section class="news_listing mb-5">
         <div class="container">
            <div class="row">
               <div class="col-lg-3 mb-lg-25">
                  <div class="trending_blocks">
                     <h4 class="mb-3 fw-bold "><?php echo $news_left_side_heading ?></h4>
                     <ul class="trending_topics">
    <?php
    $terms = get_terms(array(
        'taxonomy' => 'news-category', // Replace 'news-category' with your actual taxonomy slug
        'hide_empty' => false,
    ));
    
    foreach ($terms as $term) {
        $term_link = get_term_link($term);
        echo '<li><a href="' . esc_url($term_link) . '">' . esc_html($term->name) . '</a></li>';
    }
    ?>
</ul>

                  </div>
               </div>
               <div class="col-lg-6 mb-lg-25">
                  <h4 class="mb-3 fw-bold "><?php echo $news_center_heading ?></h4>
                  <div class="main_news_listing">

                  <?php
    $args = array(
        'post_type' => 'news', // Specify the post type
        'posts_per_page' => 14, // Number of posts to display
        'order' => 'DESC', // Display posts in descending order
    );

    $query = new WP_Query($args);

    if ($query->have_posts()) :
        while ($query->have_posts()) : $query->the_post(); ?>
            <div>
                <div class="hot_topics">
                    <div class="hot_topic_img mb-3">
                        <ul class="hot_topics_tags ps-0 m-0">
                        <?php
                         $post_tags =  get_the_terms(get_the_ID(), 'news-tag');
                        if ($post_tags) {
                        foreach ($post_tags as $tag) {
                        echo '<li class="list-unstyled d-inline-block fs-13"><a href="' . get_tag_link($tag->term_id) . '">' . $tag->name . '</a></li>';
                        }
                        }
                        ?>
                        </ul>
                        <?php if (has_post_thumbnail()) { ?>
                            <img src="<?php the_post_thumbnail_url('full'); ?>" class="img-fluid" alt="">
                        <?php }else { ?>
                           <img src="<?php echo site_url('wp-content/uploads/2024/04/no-image.jpg'); ?>" class="img-fluid" alt="">
                           <?php } ?>
                    </div>
                    <div class="hot_topics_content">
                        <p class="fs-15 fw-500 mb-2">
                            <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                        </p>
                        <?php
                        $excerpt = get_the_excerpt();
                        $excerpt = wp_trim_words($excerpt, 50, '...');
                        ?>
                        <p class="fs-14"><?php echo $excerpt; ?></p>
                        <div class="hot_users d-flex justify-content-between align-items-center pt-2 ">
                            <div>
                                <div class="d-flex align-items-center gap-2 ">
                                    <div class="hot_user_image"><?php echo get_avatar(get_the_author_meta('ID'), 32); ?></div>
                                    <a href="<?php echo get_author_posts_url(get_the_author_meta('ID')); ?>" class="fs-13 black"><?php the_author(); ?></a>
                                </div>
                            </div>
                            <div>
                                <p class="fs-13 mb-0"><i class="bi bi-clock pe-1"></i>
                                    <?php echo human_time_diff(get_the_time('U'), current_time('timestamp')) . ' ago'; ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
    <?php endwhile;
    endif;
    wp_reset_postdata(); ?>
                  </div>
               </div>
               <div class="col-lg-3 mb-lg-25">
                  <h4 class="mb-3 fw-bold "><?php echo $news_right_side_heading ?></h4>
                  <div class="top_stories">

                  <?php
    $start_of_week = strtotime('last Monday', strtotime('tomorrow')); // Get the timestamp of the start of the current week (Monday)
    $end_of_week = strtotime('next Monday', strtotime('tomorrow')) - 1; // Get the timestamp of the end of the current week (Sunday)

    $args = array(
        'post_type'      => 'news', // Assuming you want to display posts
        'posts_per_page' => -1, // Number of top stories to display
        'order'          => 'DESC', // Display posts in descending order
        'orderby'        => 'date', // Order by date
        'date_query'     => array(
            array(
                'after'     => date('Y-m-d', $start_of_week),
                'before'    => date('Y-m-d', $end_of_week),
                'inclusive' => true,
            ),
        ),
    );

    $query = new WP_Query($args);

    if ($query->have_posts()) :
        while ($query->have_posts()) : $query->the_post(); ?>
                     <div class="more_posts">
                     <div class="row">
                    <div class="col-3">
                        <div class="news_images">
                            <?php if (has_post_thumbnail()) : ?>
                                <img src="<?php the_post_thumbnail_url('thumbnail'); ?>" class="img-fluid" alt="">
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-9">
                        <div class="mb-0">
                            <a href="<?php the_permalink(); ?>" class="news_title">
                                <?php the_title(); ?>
                            </a>
                        </div>
                        <div class="hot_users d-flex justify-content-between align-items-center pt-2 ">
                            <div>
                                <div class="d-flex align-items-center gap-2 ">
                                    <div class="hot_user_image">
                                        <?php echo get_avatar(get_the_author_meta('ID'), 32); ?>
                                    </div>
                                    <a href="<?php echo get_author_posts_url(get_the_author_meta('ID')); ?>" class="fs-12 black" tabindex="0">
                                        <?php the_author(); ?>
                                    </a>
                                </div>
                            </div>
                            <div>
                                <p class="fs-12 mb-0"><i class="bi bi-clock pe-1"></i>
                                    <?php echo human_time_diff(get_the_time('U'), current_time('timestamp')) . ' ago'; ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                     </div>
                     <?php endwhile;
    else : ?>
        <p><?php echo $news_right_side_text ?></p>
    <?php
    endif;
    wp_reset_postdata(); ?>  
                  </div>
               </div>
            </div>
         </div>
      </section>
      <!-- news listing end -->
      <!-- more news sections  -->
      <section class="more_news mb-5">
         <div class="container">
            <div class="row">
               <div class="col-md-12">
                  <h3 class="fw-bold mb-4">
                     <?php echo $more_news_heading ?>
                  </h3>
               </div>
            </div>
            <div class="row">

            <?php
            $paged = (get_query_var('paged')) ? get_query_var('paged') : 1;
            $args = array(
                'post_type'      => 'community',
                'posts_per_page' => 6, // Number of posts per page
                'order'          => 'DESC',
                'paged'          => $paged // Current page
            );

    $query = new WP_Query($args);

    if ($query->have_posts()) :
        while ($query->have_posts()) : $query->the_post(); ?>
               <div class="col-md-6 mb-lg-25 col-lg-4">
               <div class="hot_topics">
                    <div class="hot_topic_img mb-3">
                        <ul class="hot_topics_tags ps-0 m-0">
                        <?php
                         $post_tags =  get_the_terms(get_the_ID(), 'community-tag');
                        if ($post_tags) {
                        foreach ($post_tags as $tag) {
                        echo '<li class="list-unstyled d-inline-block fs-13"><a href="' . get_tag_link($tag->term_id) . '">' . $tag->name . '</a></li>';
                        }
                        }
                        ?>
                        </ul>
                        <?php if (has_post_thumbnail()) { ?>
                            <img src="<?php the_post_thumbnail_url('full'); ?>" class="img-fluid" alt="">
                        <?php }else { ?>
                           <img src="<?php echo site_url('wp-content/uploads/2024/04/no-image.jpg'); ?>" class="img-fluid" alt="">
                           <?php } ?>
                    </div>
                    <div class="hot_topics_content">
                        <p class="fs-15 fw-500 mb-2">
                            <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                        </p>
                        <?php
                        $excerpt = get_the_excerpt();
                        $excerpt = wp_trim_words($excerpt, 50, '...');
                        ?>
                        <p class="fs-14"><?php echo $excerpt; ?></p>
                        <div class="hot_users d-flex justify-content-between align-items-center pt-2 ">
                            <div>
                                <div class="d-flex align-items-center gap-2 ">
                                    <div class="hot_user_image"><?php echo get_avatar(get_the_author_meta('ID'), 32); ?></div>
                                    <a href="<?php echo get_author_posts_url(get_the_author_meta('ID')); ?>" class="fs-13 black"><?php the_author(); ?></a>
                                </div>
                            </div>
                            <div>
                                <p class="fs-13 mb-0"><i class="bi bi-clock pe-1"></i>
                                    <?php echo human_time_diff(get_the_time('U'), current_time('timestamp')) . ' ago'; ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
               </div>
 
               <?php endwhile;
    endif;
    wp_reset_postdata(); ?>
            </div>
            <div class="my-4">
                <?php
                $total_pages = $query->max_num_pages;
                if ($total_pages > 1) {
                    $current_page = max(1, get_query_var('paged'));
                    echo '<nav aria-label="Page navigation example">';
                    echo '<ul class="pagination">';
                    echo '<li class="page-item">';
                    echo get_previous_posts_link('«');
                    echo '</li>';
                    for ($i = 1; $i <= $total_pages; $i++) {
                        echo '<li class="page-item ' . ($current_page == $i ? 'active' : '') . '">';
                        echo '<a class="page-link" href="' . get_pagenum_link($i) . '">' . $i . '</a>';
                        echo '</li>';
                    }
                    echo '<li class="page-item">';
                    echo get_next_posts_link('»', $total_pages);
                    echo '</li>';
                    echo '</ul>';
                    echo '</nav>';
                }
                ?>
            </div>
         </div>
      </section>
      <!-- more news sections end -->
 
<?php get_footer();?>