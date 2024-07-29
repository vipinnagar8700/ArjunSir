<?php get_header();
/*
Template Name: Home
*/

$ads_image = get_field( "ads_image" );
$ads_text = get_field( "ads_text" );
$ads_link = get_field( "ads_link" );

$banner_image = get_field( "banner_image" );
$heading_black_text = get_field( "heading_black_text" );
$heading_yellow_text = get_field( "heading_yellow_text" );
$content = get_field( "content" );
$button_text = get_field( "button_text" );
$button_link = get_field( "button_link" );

$heading_yellow_part = get_field( "heading_yellow_part" );
$heading_black_part = get_field( "heading_black_part" );
$whats_hot_text = get_field( "what’s_hot_text" );
$whats_hot_button_text = get_field( "what’s_hot_button_text" );
$whats_hot_button_link = get_field( "what’s_hot_button_link" );

$press_releases_heading = get_field( "press_releases_heading" );
$press_releases_link_text = get_field( "press_releases_link_text" );
$press_releases_text_url = get_field( "press_releases_text_url" );

$news_heading = get_field( "news_heading" );
$news_text = get_field( "news_text" );
$news_link_text= get_field( "news_link_text" );
$news_url = get_field( "news_url" );
$news_bottom_heading = get_field( "news_bottom_heading" );
$news_bottom_text = get_field( "news_bottom_text" );

$yellow_heading = get_field( "yellow_heading" );
$black_heading = get_field( "black_heading" );
$must_have_text= get_field( "must_have_text" );
$must_have_link_text = get_field( "must_have_link_text" );
$must_have_url = get_field( "must_have_url" );

$trending_yellow_heading = get_field( "trending_yellow_heading" );
$trending_black_heading = get_field( "trending_black_heading" );
$trending_text= get_field( "trending_text" );
$trending_link_text = get_field( "trending_link_text" );
$trending_url = get_field( "trending_url" );
$trending_right_image = get_field( "trending_right_image" );

$our_partners_yellow_heading = get_field( "our_partners_yellow_heading" );
$our_partners_black_heading = get_field( "our_partners_black_heading" );
$our_partners_text = get_field( "our_partners_text" );

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
<!-- hype loot section end -->

<!-- main banner section  -->
<section class="main_banner" style="background-image: url(<?php echo $banner_image ?>);">
   <div class="container">
      <div class="row">
         <div class="col-12 col-lg-8">
            <h2 class="banner_title"><?php echo $heading_black_text ?> <span class="yellow"><?php echo $heading_yellow_text ?></span></h2>
            <p class="banner_subtitle white fs-18"><?php echo $content ?></p>
            <div>
               <a href="<?php echo $button_link ?>" class="btn btn-primary "><?php echo $button_text ?></a>
            </div>
         </div>
      </div>
   </div>
</section>
<!-- main banner section end -->
<!-- hot section  -->
<section class="hots py-5">
   <div class="container">
      <div class="row pb-5">
         <div class="col-md-12">
            <h2 class="global_heading"><span class="yellow"><?php echo $heading_yellow_part ?></span> <?php echo $heading_black_part ?></h2>
            <p><?php echo $whats_hot_text ?>
            </p>
            <a href="<?php echo $whats_hot_button_link ?>" class="btn btn-secondary "><?php echo $whats_hot_button_text ?></a>
         </div>
      </div>
   </div>
   <div class="container">
      <div class="row">
         <div class="col-md-12">
            <div class="hot_slider">
               <?php
               $args = array(
                  'post_type' => 'post', // Specify the post type
                  'posts_per_page' => 15, // Number of posts to display
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
                                 $post_tags = get_the_tags();
                                 if ($post_tags) {
                                    foreach ($post_tags as $tag) {
                                       echo '<li class="list-unstyled d-inline-block fs-13"><a href="' . get_tag_link($tag->term_id) . '">' . $tag->name . '</a></li>';
                                    }
                                 }
                                 ?>
                              </ul>
                              <?php if (has_post_thumbnail()) : ?>
                                 <img src="<?php the_post_thumbnail_url('full'); ?>" class="img-fluid" alt="">
                              <?php else : ?>
                                 <img src="<?php echo site_url('wp-content/themes/crypto-theme/images/default-thumbnail.jpg');?>" class="img-fluid" alt="Default Thumbnail">
                              <?php endif; ?>
                           </div>
                           <div class="hot_topics_content">
                              <p class="fs-16 fw-500 mb-2">
                                 <a href="<?php the_permalink(); ?>" class="hot_title_"><?php the_title(); ?></a>
                              </p>
                              <?php
                              $excerpt = get_the_excerpt();
                              $excerpt = wp_trim_words($excerpt, 30, '...');
                              ?>
                              <p class="fs-14 hot_subtitle"><?php echo $excerpt; ?></p>
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
<!-- hot section end -->
<!-- latest Release section  -->
<section class="py-5 grey_bg">
   <div class="container mb-5">
      <div class="row">
         <div class="col-md-12">
            <h4 class="mb-2"><?php echo $press_releases_heading ?></h4>
            <div>
               <a href="<?php echo $press_releases_text_url ?>" class="borderless_btn"><span><?php echo $press_releases_link_text ?></span><i class="bi bi-arrow-right ps-1"></i></a>
            </div>
         </div>
      </div>
   </div>
   <div class="container">
      <div class="row">
         <div class="col-md-12">
            <div class="press_slider">
               <?php
               $args = array(
                  'post_type'      => 'press-release', // Custom post type name
                  'posts_per_page' => -1, // Display all press releases
                  'order'          => 'DESC', // Display posts in descending order
               );

               $query = new WP_Query($args);

               if ($query->have_posts()) :
                  while ($query->have_posts()) : $query->the_post(); ?>
                     <div>
                        <div class="press_release_grid box_shadow">
                           <div class="mb-3">
                              <a href="<?php the_permalink(); ?>" class="fs-14"><?php the_title(); ?></a>
                           </div>
                           <div class="hot_users d-flex justify-content-between align-items-center pt-2 ">
                              <div>
                                 <div class="d-flex align-items-center gap-2 ">
                                    <div class="hot_user_image">
                                       <?php echo get_avatar(get_the_author_meta('ID'), 32); ?>
                                    </div>
                                    <a href="<?php echo get_author_posts_url(get_the_author_meta('ID')); ?>" class="fs-13 black" tabindex="0">
                                       <?php the_author(); ?>
                                    </a>
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
               <?php endwhile;
               endif;
               wp_reset_postdata(); ?>
            </div>

         </div>
      </div>
   </div>
</section>
<!-- latest Release section end -->
<!-- news section  -->
<section class="news_ py-5 ">
   <div class="container">
      <div class="row pb-5">
         <div class="col-md-12">
            <h2 class="global_heading"><span class="yellow"><?php echo $news_heading ?></span> </h2>
            <p><?php echo $news_text ?></p>
            <div>
               <a href="<?php echo $news_url ?>" class="borderless_btn"><span><?php echo $news_link_text ?></span><i class="bi bi-arrow-right ps-1"></i></a>
            </div>
         </div>
      </div>
   </div>
   <div class="container">
      <div class="row news_wraps_">
         <div class="col-md-4">
            <div class="week_stories">
               <p class="fs-18 fw-bold"><?php echo $news_bottom_heading ?></p>

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
                  <p><?php echo $news_bottom_text ?></p>
               <?php
               endif;
               wp_reset_postdata(); ?>
            </div>

         </div>
         <div class="col-md-8">
            <div class="news_wrap">
               <div class="row">
                  <?php
                  $args = array(
                     'post_type'      => 'news', // Specify the custom post type
                     'posts_per_page' => 6, // Number of posts to display
                     'order'          => 'DESC', // Display posts in descending order
                  );

                  $query = new WP_Query($args);

                  if ($query->have_posts()) :
                     while ($query->have_posts()) : $query->the_post(); ?>
                        <div class="col-md-6">
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
                        </div>
                  <?php endwhile;
                  endif;
                  wp_reset_postdata(); ?>
               </div>

            </div>
         </div>





      </div>
   </div>
</section>
<!-- news section end -->
<!-- must have section  -->
<section class="must-have py-5 grey_bg">
   <div class="container">
      <div class="row pb-5">
         <div class="col-md-12">
            <h2 class="global_heading"><span class="yellow"><?php echo $yellow_heading ?></span> <?php echo $black_heading ?></h2>
            <p><?php echo $must_have_text ?></p>
            <div>
               <a href="<?php echo $must_have_url ?>" class="borderless_btn"><span><?php echo $must_have_link_text ?></span><i class="bi bi-arrow-right ps-1"></i></a>
            </div>
         </div>
      </div>
      <div class="row tools_logos">
		   <?php if( have_rows('platforms_list') ):  
            while( have_rows('platforms_list') ): the_row();  
           
                  $image = get_sub_field('image');
				$platforms_name = get_sub_field('platforms_name');
				$platforms_link = get_sub_field('platforms_link');
                   
          ?>
         <div class="col-2">
            <a href="<?php echo $platforms_link; ?>">
               <div class="tools_logos_grid text-center ">
                  <img src="<?php echo $image; ?>" class="img-fluid " alt="">
                  <p class="mb-0 black fs-18 fw-600"><?php echo $platforms_name; ?></p>
               </div>
            </a>
         </div>
		  <?php endwhile;   endif; ?>
		  
<!--          <div class="col-2">
            <a href="">
               <div class="tools_logos_grid text-center ">
                  <img src="<?php echo get_template_directory_uri(); ?>/images/tool (1).svg" class="img-fluid " alt="">
                  <p class="mb-0 black fs-18 fw-600">Coinbase</p>
               </div>
            </a>
         </div>
         <div class="col-2">
            <a href="">
               <div class="tools_logos_grid text-center ">
                  <img src="<?php echo get_template_directory_uri(); ?>/images/chainz-shaddow.svg" class="img-fluid " alt="">
                  <p class="mb-0 black fs-18 fw-600">Coinbase</p>
               </div>
            </a>
         </div>
         <div class="col-2">
            <a href="">
               <div class="tools_logos_grid text-center ">
                  <img src="<?php echo get_template_directory_uri(); ?>/images/tool (2).svg" class="img-fluid " alt="">
                  <p class="mb-0 black fs-18 fw-600">Coinbase</p>
               </div>
            </a>
         </div>
         <div class="col-2">
            <a href="">
               <div class="tools_logos_grid text-center ">
                  <img src="<?php echo get_template_directory_uri(); ?>/images/chainz-shaddow.svg" class="img-fluid " alt="">
                  <p class="mb-0 black fs-18 fw-600">Coinbase</p>
               </div>
            </a>
         </div>
         <div class="col-2">
            <a href="">
               <div class="tools_logos_grid text-center ">
                  <img src="<?php echo get_template_directory_uri(); ?>/images/tool (1).svg" class="img-fluid " alt="">
                  <p class="mb-0 black fs-18 fw-600">Coinbase</p>
               </div>
            </a>
         </div>
         <div class="col-2">
            <a href="">
               <div class="tools_logos_grid text-center ">
                  <img src="<?php echo get_template_directory_uri(); ?>/images/tool (2).svg" class="img-fluid " alt="">
                  <p class="mb-0 black fs-18 fw-600">Coinbase</p>
               </div>
            </a>
         </div>
         <div class="col-2">
            <a href="">
               <div class="tools_logos_grid text-center ">
                  <img src="<?php echo get_template_directory_uri(); ?>/images/tool (2).svg" class="img-fluid " alt="">
                  <p class="mb-0 black fs-18 fw-600">Coinbase</p>
               </div>
            </a>
         </div>
         <div class="col-2">
            <a href="">
               <div class="tools_logos_grid text-center ">
                  <img src="<?php echo get_template_directory_uri(); ?>/images/chainz-shaddow.svg" class="img-fluid " alt="">
                  <p class="mb-0 black fs-18 fw-600">Coinbase</p>
               </div>
            </a>
         </div>
         <div class="col-2">
            <a href="">
               <div class="tools_logos_grid text-center ">
                  <img src="<?php echo get_template_directory_uri(); ?>/images/tool (1).svg" class="img-fluid " alt="">
                  <p class="mb-0 black fs-18 fw-600">Coinbase</p>
               </div>
            </a>
         </div>
         <div class="col-2">
            <a href="">
               <div class="tools_logos_grid text-center ">
                  <img src="<?php echo get_template_directory_uri(); ?>/images/tool (2).svg" class="img-fluid " alt="">
                  <p class="mb-0 black fs-18 fw-600">Coinbase</p>
               </div>
            </a>
         </div>
         <div class="col-2">
            <a href="">
               <div class="tools_logos_grid text-center ">
                  <img src="<?php echo get_template_directory_uri(); ?>/images/tool (2).svg" class="img-fluid " alt="">
                  <p class="mb-0 black fs-18 fw-600">Coinbase</p>
               </div>
            </a>
         </div> -->
      </div>
   </div>
</section>
<!-- must have section end -->
<!-- trending section  -->
<section class="trending py-5 ">
   <div class="container">
      <div class="row align-items-center ">
         <div class="col-md-7">
            <h2 class="global_heading"><span class="yellow"><?php echo $trending_yellow_heading ?></span> <?php echo $trending_black_heading ?></h2>
            <p><?php echo $trending_text ?></p>
            <div>
               <a href="<?php echo $trending_url ?>" class="borderless_btn"><span><?php echo $trending_link_text ?></span><i class="bi bi-arrow-right ps-1"></i></a>
            </div>
         </div>
         <div class="col-md-5">
            <img src="<?php echo $trending_right_image ?>" class="img-fluid " alt="">
         </div>
      </div>
   </div>
   <div class="container ">
      <div class="row">
         <div class="col-md-12">
            <div class="press_slider">

               <?php
               $args = array(
                  'post_type'      => 'community', // Custom post type name
                  'posts_per_page' => -1, // Display all press releases
                  'order'          => 'DESC', // Display posts in descending order
               );

               $query = new WP_Query($args);

               if ($query->have_posts()) :
                  while ($query->have_posts()) : $query->the_post(); ?>
                     <div>
                        <div class="press_release_grid box_shadow">
                           <div class="mb-3">
                              <a href="<?php the_permalink(); ?>" class="fs-14"><?php the_title(); ?></a>
                           </div>
                           <div class="hot_users d-flex justify-content-between align-items-center pt-2 ">
                              <div>
                                 <div class="d-flex align-items-center gap-2 ">
                                    <div class="hot_user_image">
                                       <?php echo get_avatar(get_the_author_meta('ID'), 32); ?>
                                    </div>
                                    <a href="<?php echo get_author_posts_url(get_the_author_meta('ID')); ?>" class="fs-13 black" tabindex="0">
                                       <?php the_author(); ?>
                                    </a>
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
               <?php endwhile;
               endif;
               wp_reset_postdata(); ?>
            </div>
         </div>
      </div>
   </div>
   </div>
</section>
<!-- trending section end -->
<!-- partner section  -->
<section class="py-5 grey_bg">
   <div class="container">
      <div class="row">
         <div class="col-md-12">
            <h2 class="global_heading"><span class="yellow"><?php echo $our_partners_yellow_heading ?></span> <?php echo $our_partners_black_heading ?></h2>
            <p><?php echo $our_partners_text ?></p>
         </div>
      </div>
      <div class="row">
         <div class="col-md-12">
            <div class="partner_slider">
               <?php
               $args = array(
                  'post_type'      => 'partners', // Custom post type name
                  'posts_per_page' => -1, // Display all partner posts
                  'order'          => 'ASC', // Display posts in ascending order
               );

               $query = new WP_Query($args);

               if ($query->have_posts()) :
                  while ($query->have_posts()) : $query->the_post(); ?>
                     <div>
                        <a href="<?php the_permalink(); ?>">
                           <div class="tools_logos_grid text-center">
                              <?php if (has_post_thumbnail()) : ?>
                                 <img src="<?php the_post_thumbnail_url('full'); ?>" class="img-fluid" alt="">
                              <?php endif; ?>
                              <p class="mb-0 white fs-18 fw-600"><?php the_title(); ?></p>
                           </div>
                        </a>
                     </div>
               <?php endwhile;
               endif;
               wp_reset_postdata(); ?>
            </div>

         </div>
      </div>
   </div>
</section>
<!-- partner section end -->

<?php get_footer(); ?>