<?php get_header();
/*
Template Name: Community
*/
?>
<?php
$ads = get_field('ads');
?>
 
      <!-- hype loot section  -->
      <section class="hype_loot text-center ">
         <div class="container">
            <div class="row">
            <div class="col-md-12">
				<a href="<?php echo $ads[0]['link'];?>">
                <div class="d-inline-block d-md-flex text-center  justify-content-center align-items-center gap-2 ">
                    <div><img src="<?php echo $ads[0]['image'];?>" class="img-fluid hype_loots " alt=""></div>
                    <div>
                        <p class="m-0 "><?php echo $ads[0]['text'];?></p>
                    </div>
                </div>
					</a>
            </div>
            </div>
         </div>
      </section>
      <!-- hype loot section end -->
<!--       <section class="comunity_navigations pt-5">
         <div class="container">
            <div class="row">
                <div class="col-md-12">
                  <?php
               if ( is_user_logged_in() ) { ?>
                  <ul class="post_navigations horizontal_    loggedin">
                     <li class="d-block  list-unstyled "><a href="<?php echo site_url('communities');?>"><i class="bi bi-house-fill"></i><span>Overview</span></a></li>
                     <li class="d-block  list-unstyled "><a href="<?php echo site_url('my-account');?>"><i class="bi bi-person-circle"></i><span>Account</span></a></li>
                     <li class="d-block  list-unstyled "><a href="<?php echo site_url('communities');?>"><i class="bi bi-stack"></i><span>Communities</span></a></li>
                     <li class="d-block  list-unstyled "><a href="<?php echo site_url('my-account');?>"><i class="bi bi-gear"></i><span>Settings</span></a></li>
                     <li class="d-block  list-unstyled "><a href="<?php echo site_url('my-account');?>"><i class="bi bi-box-arrow-in-right"></i><span>Profile</span></a></li>
                     <li class="d-block  list-unstyled "><a href="<?php echo site_url('cart');?>"><i class="bi bi-cart-dash"></i><span>Cart</span></a></li>
                  </ul> 
                  <?php } else{?>
                     <ul class="post_navigations    horizontal_">
                     <li class="d-block  list-unstyled "><a href="<?php echo site_url('communities');?>"><i class="bi bi-house-fill"></i><span>Overview</span></a></li>
                     <li class="d-block  list-unstyled "><a href="javascript:void(0)" data-bs-toggle="modal" data-bs-target="#loginModal"><i class="bi bi-person-circle"></i><span>Account</span></a></li>
                     <li class="d-block  list-unstyled "><a href="<?php echo site_url('communities');?>"><i class="bi bi-stack"></i><span>Communities</span></a></li>
                     <li class="d-block  list-unstyled "><a href="javascript:void(0)" data-bs-toggle="modal" data-bs-target="#loginModal"><i class="bi bi-gear"></i><span>Settings</span></a></li>
                     <li class="d-block  list-unstyled "><a href="javascript:void(0)" data-bs-toggle="modal" data-bs-target="#loginModal"><i class="bi bi-box-arrow-in-right"></i><span>Profile</span></a></li>
                     <li class="d-block  list-unstyled "><a href="<?php echo site_url('cart');?>"><i class="bi bi-cart-dash"></i><span>Cart</span></a></li>
                  </ul>
                  <?php } ?>
               </div>
            </div>
         </div>
      </section> -->
      <!-- hot section  -->
      <section class="community py-5">
         <div class="container">
            <div class="row">
				<div class="col-lg-2">
					 <?php
               if ( is_user_logged_in() ) { ?>
                  <ul class="post_navigations   myStickyDiv loggedin">
                     <li class="d-block  list-unstyled "><a href="<?php echo site_url('communities');?>"><i class="bi bi-house-fill"></i><span><?php the_field('overview'); ?></span></a></li>
                     <li class="d-block  list-unstyled "><a href="<?php echo site_url('my-account');?>"><i class="bi bi-person-circle"></i><span><?php the_field('account'); ?></span></a></li>
                     <li class="d-block  list-unstyled "><a href="<?php echo site_url('communities');?>"><i class="bi bi-stack"></i><span><?php the_field('communities'); ?></span></a></li>
                     <li class="d-block  list-unstyled "><a href="<?php echo site_url('my-account');?>"><i class="bi bi-gear"></i><span><?php the_field('settings'); ?></span></a></li>
                     <li class="d-block  list-unstyled "><a href="<?php echo site_url('my-account');?>"><i class="bi bi-box-arrow-in-right"></i><span><?php the_field('profile'); ?></span></a></li>
                     <li class="d-block  list-unstyled "><a href="<?php echo site_url('cart');?>"><i class="bi bi-cart-dash"></i><span><?php the_field('cart'); ?></span></a></li>
                  </ul> 
                  <?php } else{?>
                     <ul class="post_navigations    myStickyDiv">
                     <li class="d-block  list-unstyled "><a href="<?php echo site_url('communities');?>"><i class="bi bi-house-fill"></i><span>Overview</span></a></li>
                     <li class="d-block  list-unstyled "><a href="javascript:void(0)" data-bs-toggle="modal" data-bs-target="#loginModal"><i class="bi bi-person-circle"></i><span>Account</span></a></li>
                     <li class="d-block  list-unstyled "><a href="<?php echo site_url('communities');?>"><i class="bi bi-stack"></i><span>Communities</span></a></li>
                     <li class="d-block  list-unstyled "><a href="javascript:void(0)" data-bs-toggle="modal" data-bs-target="#loginModal"><i class="bi bi-gear"></i><span>Settings</span></a></li>
                     <li class="d-block  list-unstyled "><a href="javascript:void(0)" data-bs-toggle="modal" data-bs-target="#loginModal"><i class="bi bi-box-arrow-in-right"></i><span>Profile</span></a></li>
                     <li class="d-block  list-unstyled "><a href="<?php echo site_url('cart');?>"><i class="bi bi-cart-dash"></i><span>Cart</span></a></li>
                  </ul>
                  <?php } ?>
				</div>
               <div class="col-lg-7 comunity_col">
                  <div class="comunity_slider">

                  <?php
    $args = array(
        'post_type' => 'community', // Specify the post type
        'posts_per_page' => -1, // Number of posts to display
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
               </div>
               <div class="col-lg-3">
                  <div class="publish_your_post grid_radius mb-3  ">
                     <div class="d-block d-md-flex gap-md-2 align-items-center ">
                        <div class="col-8 mb-25">
                           <h5 class="mb-0 white "><?php the_field('publish_heading'); ?></h5>
                        </div>
                        <div class="col-4">
                           <a href="<?php the_field('publish_button_url'); ?>" class="publish_btn w-100 "><?php the_field('publish_button_text'); ?></a>
                        </div>
                     </div>
                  </div>
                  <div class="week_stories mt-auto ">
                     <p class="fs-18 fw-bold "><?php the_field('top_stories_heading'); ?></p>
                     <?php
    $start_of_week = strtotime('last Monday', strtotime('tomorrow')); // Get the timestamp of the start of the current week (Monday)
    $end_of_week = strtotime('next Monday', strtotime('tomorrow')) - 1; // Get the timestamp of the end of the current week (Sunday)

    $args = array(
        'post_type'      => 'community', // Assuming you want to display posts
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
            <div class="news_grid box_shadow">
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
        <p><?php the_field('top_stories_text'); ?></p>
    <?php
    endif;
    wp_reset_postdata(); ?>
                  </div>
               </div>
            </div>
         </div>
      </section>
      <!-- hot section end -->
      <!-- editors pics  -->
      <section class="editors_pics py-5 ">
         <div class="container">
            <div class="row pb-4">
               <div class="col-md-12">
                  <h2 class="global_heading"><span class="yellow"><?php the_field('yellow_heading'); ?> </span><?php the_field('black_heading'); ?></h2>
               </div>
            </div>
         </div>
         <div class="container-fluid">
            <div class="row">
               <div class="col-md-12">
                  <div class="hot_slider">
                  <?php
    $args = array(
        'post_type' => 'community', // Specify the post type
        'posts_per_page' => -1, // Number of posts to display
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
               </div>
            </div>
         </div>
      </section>
      <!-- editors pics end -->
      <!-- topics section  -->
      <section class="topics_for_you py-5 grey_bg mb-5">
         <div class="container">
            <div class="row pb-4">
               <div class="col-md-12">
                  <h2 class="global_heading"><span class="yellow"><?php the_field('topics_yellow_heading'); ?> </span><?php the_field('topics_black_heading'); ?></h2>
               </div>
            </div>
            <div class="row topics_row">
            <?php
    // Get all categories in the 'community-category' taxonomy
    $categories = get_categories(array(
        'taxonomy' => 'community-category',
        'hide_empty' => false, // Show categories even if they have no posts
    ));

    // Loop through each category
    foreach ($categories as $category) {
        // Query posts for the current category
        $category_posts = new WP_Query(array(
            'post_type' => 'community', // Assuming 'community' is your custom post type
            'tax_query' => array(
                array(
                    'taxonomy' => 'community-category',
                    'field' => 'slug',
                    'terms' => $category->slug,
                ),
            ),
            'posts_per_page' => 4, // Display only 1 post per category
        ));

        // Check if there are posts in the current category
        if ($category_posts->have_posts()) :
    ?>
            <div class="col-md-6 mb-lg-25 col-lg-4 mb-4">
                <div class="grid_radius_white">
                    <h5 class="green fw-bold mb-3"><?php echo esc_html($category->name); ?></h5>

                    <div class="news_grid_wrapper">
                        <?php
                        // Loop through posts in the current category
                        while ($category_posts->have_posts()) : $category_posts->the_post();
                        ?>
                            <div class="more_posts">
                                <div class="row">
                                    <div class="col-3">
                                        <div class="news_images">
                                            <?php if (has_post_thumbnail()) : ?>
                                                <?php the_post_thumbnail('thumbnail', array('class' => 'img-fluid', 'alt' => get_the_title())); ?>
                                            <?php else : ?>
                                                <img src="<?php echo site_url('wp-content/uploads/2024/04/sample-150x150.jpg'); ?>" class="img-fluid" alt="Default Thumbnail">
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="col-9">
                                        <div class="mb-0">
                                            <a href="<?php the_permalink(); ?>" class="news_title">
                                                <?php the_title(); ?>
                                            </a>
                                        </div>
                                        <div class="hot_users d-flex justify-content-between align-items-center pt-2">
                                            <div>
                                                <div class="d-flex align-items-center gap-2">
                                                    <div class="hot_user_image"> <?php echo get_avatar(get_the_author_meta('ID'), 32); ?></div>
                                                    <a href="<?php echo esc_url(get_author_posts_url(get_the_author_meta('ID'))); ?>" class="fs-12 black" tabindex="0"><?php the_author(); ?></a>
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
                        <?php endwhile; ?>
                    </div>
                    <div class="text-center mb-2">
                        <a href="<?php echo get_term_link($category); ?>" class="borderless_btn"><span>More Articles</span><i class="bi bi-arrow-right ps-1"></i></a>
                    </div>
                </div>
            </div>
    <?php
            // Restore original post data
            wp_reset_postdata();
        endif;
    }
    ?>  
            </div>
         </div>
      </section>
      <!-- topics section end -->
      <div class="container mb-3">
         <div class="hype_loot text-center ">
            <div class="row">
            <div class="col-md-12">
				<a href="<?php echo $ads[1]['link'];?>">
                <div class="d-inline-block d-md-flex text-center  justify-content-center align-items-center gap-2 ">
                    <div><img src="<?php echo $ads[1]['image'];?>" class="img-fluid hype_loots " alt=""></div>
                    <div>
                        <p class="m-0 "><?php echo $ads[1]['text'];?></p>
                    </div>
                </div>
				</a>
            </div>
            </div>
         </div>
      </div>
       <!-- categories listings with filter  -->
       <section class="listing_with_filter pb-5">
         <div class="container">
            <div class="row">
               <div class="col-md-12">
                     <!-- filter_main_container (Center website) -->
      <div class="filter_main_container ">
      <div id="filter_btn_container" class="mb-4">
      <button class="filter_btn active" onclick="filterSelection('all')"> All</button>
    <?php
    $taxonomy = 'community-category'; // Change to your taxonomy name
   //  $parent_term_slug = 'reviews'; // Change to the slug of your parent term

   //  $parent_term = get_term_by('slug', $parent_term_slug, $taxonomy);
    $child_terms = get_terms(array(
        'taxonomy' => $taxonomy,
      //   'parent' => $parent_term->term_id,
        'hide_empty' => false,
    ));

    // Display buttons for child terms
    foreach ($child_terms as $child_term) {
    ?>
        <button class="filter_btn" onclick="filterSelection('<?php echo $child_term->slug; ?>')"><?php echo $child_term->name; ?></button>
    <?php
    }
    ?>
</div>

         <!-- Portfolio Gallery Grid -->
         <div class="row">
    <!-- filter columns  -->
    <?php
    $args = array(
        'post_type' => 'community', // Change to your custom post type if needed
    );

    $query = new WP_Query($args);

    if ($query->have_posts()) :
        while ($query->have_posts()) : $query->the_post();
        $post_categories = wp_get_post_terms(get_the_ID(), 'community-category', array('fields' => 'all'));

        $category_classes = '';
        
        if (!empty($post_categories)) {
            foreach ($post_categories as $category) {
                $category_classes .= $category->slug . ' ';
            }
        }
        
        $category_classes = rtrim($category_classes);
    ?>
            <div class="col-md-6 mb-lg-25 col-lg-4 mb-3 filter_column <?php echo esc_attr($category_classes); ?>">
                <div class="hot_topics">
                    <div class="hot_topic_img mb-3">
                        
                        <?php
                        $terms = get_the_terms(get_the_ID(), 'community-tag');
                        ?>

                        <?php if ($terms) : ?>
                           <ul class="hot_topics_tags ps-0 m-0">
                              <?php foreach ($terms as $term) : ?>
                     
                                 <li class="list-unstyled d-inline-block fs-13"><a href="" tabindex="0"><?php echo $term->name; ?></a></li>
                              <?php endforeach; ?>
                           </ul>
                        <?php endif; ?>
                        <a href="<?php the_permalink(); ?>">
                            <?php
                            if (has_post_thumbnail()) {
                                the_post_thumbnail('full', array('class' => 'img-fluid'));
                            } else {
                                echo '<img src="' .site_url('wp-content/uploads/2024/04/no-image.jpg') .'" class="img-fluid" alt="Default Image">';
                            }
                            ?>
                        </a>
                        
                    </div>
                    <div class="hot_topics_content">
                        <a class="fs-15 fw-500 mb-2 black d-inline-block " href="<?php the_permalink(); ?>">
                            <?php the_title(); ?>
                        </a>
                        <p class="fs-14"><?php the_excerpt(); ?></p>
                        <div class="hot_users d-flex justify-content-between align-items-center pt-2 ">
                            <div>
                                <div class="d-flex align-items-center gap-2 ">
                                <div class="hot_user_image"><?php echo get_avatar(get_the_author_meta('ID'), 32); ?></div>
                                    <a href="<?php echo get_author_posts_url(get_the_author_meta('ID')); ?>" class="fs-13 black" tabindex="0"><?php the_author(); ?></a>
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
    <?php
        endwhile;
        wp_reset_postdata();
    else :
        echo '<p>No posts found</p>';
    endif;
    ?>
    <!-- filter columns end -->
</div>

         
      </div>
               </div>
            </div>



            <div class="mt-5">
    <nav aria-label="Page navigation example">
        <ul class="pagination">
            <?php
            $pagination_args = array(
                'prev_text' => '&laquo;',
                'next_text' => '&raquo;',
                'type'      => 'list',
            );

            $paginate_links = paginate_links($pagination_args);

            if ($paginate_links) {
                echo $paginate_links;
            }
            ?>
        </ul>
    </nav>
</div>

         </div>



         
      </section>
      <!-- categories listings with filter end -->



    <!-- login and sigup popup   -->
    <div class="modal fade" id="loginModal" tabindex="-1" aria-labelledby="loginModalLabel" aria-hidden="true">
   <div class="modal-dialog modal-custom">
     <div class="modal-content">
       <div class="modal-header border-0 ">
        
         <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
       </div>
       <div class="modal-body">
        
   <ul class="nav nav-tabs login_tabs border-0   justify-content-center gap-2  " id="myTab" role="tablist">
      <li class="nav-item p-0 " role="presentation">
        <button class="nav-link active login_button" id="login-tab" data-bs-toggle="tab" data-bs-target="#login" type="button" role="tab" aria-controls="login" aria-selected="true">login</button>
      </li>
      <li class="nav-item p-0 " role="presentation">
        <button class="nav-link signup_button" id="sign-up-tab" data-bs-toggle="tab" data-bs-target="#sign-up" type="button" role="tab" aria-controls="sign-up" aria-selected="false">sign-up</button>
      </li>       
    </ul>
    <div class="tab-content" id="myTabContent">
      <div class="tab-pane fade show active" id="login" role="tabpanel" aria-labelledby="login-tab">
            <div class="login_form">
               <div class="d-flex justify-content-between align-items-center mb-3 ">
                  <h5 class="mb-0 fw-bold  ">Login</h5>
                  
               </div>
   
              
               <form  class="mb-3 " method="post" action="<?php echo esc_url( admin_url('admin-post.php') ); ?>">
               <input type="hidden" name="action" value="custom_user_login">
                  <div class="mb-2">
                     <input type="text" class="form-control " placeholder="Username or Email" name="username_or_email">
                  </div>
                  <div class="mb-2">
                     <input type="password" class="form-control " placeholder="Password" name="password">
                  </div>
               
               <div class="d-flex justify-content-between align-items-center mb-3 ">
                  <div class="form-check">
                     <input class="form-check-input" type="checkbox" value="" name="remember_me" id="remember_me">
                     <label class="form-check-label fs-14" for="flexCheckDefault">
                      Remember Me
                     </label>
                   </div>
                  <div>
                  <button type="submit" class="btn btn-primary">Login</button>
                  </div>
               </div>
               </form>
               <div class="mb-3 text-center ">
                  <p class="mb-0 ">OR</p>
               </div>
   
               
               <ul class="mb-3 text-center ">
                  <li class="d-inline-block pe-2">
                     <a href="" class="btn btn-primary fs-12 mb-1 "><i class="bi bi-facebook pe-2"></i>Sign In with facebook</a>
                  </li>
                  <li class="d-inline-block pe-2">
                     <a href="" class="btn btn-secondary  fs-12 mb-1 "><i class="bi bi-google pe-2"></i>Sign In with Google</a>
                  </li>
               </ul>
               <div class="text-center ">
                  <a href="" class="yellow text-decoration-underline ">Lost password?</a>
               </div>
            </div>
      </div>
      <div class="tab-pane fade" id="sign-up" role="tabpanel" aria-labelledby="sign-up-tab">
         <div class="login_form">
            <div class="d-flex justify-content-between align-items-center mb-3 ">
                
               <h5 class="mb-0 fw-bold  ">SignUp</h5>
               
               
            </div>

            <form id="registrationForm" method="post" action="<?php echo esc_url( admin_url('admin-post.php') ); ?>">
            <input type="hidden" name="action" value="custom_user_register">
            <div class="mb-2">
                <input type="text" name="username" id="username" class="form-control" placeholder="Username">
            </div>
            <div class="mb-2">
                <input type="email" name="email" id="email" class="form-control" placeholder="Email">
            </div>
            <div class="mb-2">
                <input type="password" name="password" id="password" class="form-control" placeholder="Password">
            </div>
            <div class="mb-2">
                <input type="password" name="confirm_password" id="confirm_password" class="form-control" placeholder="Confirm Password">
            </div>
            <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" value="1" id="consentCheck" name="consentCheck">
                <label class="form-check-label fs-12 mb-0" for="consentCheck">
                    I consent collecting and storing my data from this form.
                </label>
                <a href="#" class="yellow fs-10">Privacy Policy</a>
            </div>
            <div>
                <button type="submit" class="btn btn-primary">Sign Up</button>
            </div>
        </form>
         </div>
      </div>
     
    </div>
       </div>
        
     </div>
   </div>
 </div>
    <!-- login and sigup popup  end -->
    <script>
 jQuery(document).ready(function($) {
    $('#registrationForm').on('submit', function(event) {
        event.preventDefault(); // Prevent form submission

        // Perform client-side validation
        var username = $('#username').val();
        var email = $('#email').val();
        var password = $('#password').val();
        var confirm_password = $('#confirm_password').val();
        var consentCheck = $('#consentCheck').is(':checked');

        if (!username || !email || !password || !confirm_password || !consentCheck) {
            alert('Please fill in all fields and agree to the consent.');
            return;
        }

        if (password !== confirm_password) {
            alert('Passwords do not match.');
            return;
        }

        // If validation passes, proceed with AJAX call
        var data = {
            action: 'check_user_exists',
            username: username,
            email: email,
            nonce: '<?php echo wp_create_nonce('check_user_exists_nonce'); ?>' // Add nonce for security
        };

        $.post('<?php echo admin_url('admin-ajax.php'); ?>', data, function(response) {
            if (response.exists) {
                alert('This ' + response.field + ' is already taken.');
            } else {
                // If username and email are unique, submit the form
                $('#registrationForm')[0].submit();
            }
        }, 'json');
    });
});

</script>





       
<?php get_footer(); ?>