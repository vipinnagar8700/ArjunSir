<?php get_header();
// Get the current post author's ID
$author_id = get_the_author_meta('ID');
if (have_posts()) {
   // Start the Loop
   while (have_posts()) {
      // Iterate the post index
      the_post();
?>

      <section class="bread_crumb">
         <div class="container">
            <div class="row">
               <div class="col-md-12">
                  <ul>
                     <li class="d-inline-block list-unstyled pe-2 "><a class="fs-13 black" href="<?php echo esc_url(home_url('/')); ?>">Home</a></li>
                     <li class="d-inline-block list-unstyled pe-2 "><a class="fs-13 black" href=""><i class="fa fa-angle-right"></i></a></li>
                     <li class="d-inline-block list-unstyled pe-2 "><a class="fs-13 black" href="<?php echo esc_url(get_permalink()); ?>">Reviews</a></li>
                     <li class="d-inline-block list-unstyled pe-2 "><a class="fs-13 black" href=""><i class="fa fa-angle-right"></i></a></li>
                     <li class="d-inline-block list-unstyled pe-2 "><a class="fs-13 black" href=""><?php the_title(); ?></a></li>
                  </ul>
               </div>
            </div>
         </div>
      </section>


      <!-- wallet single   -->
      <section class="single_wallet_post py-5">
         <div class="container">
            <div class="row">
               <div class="col-md-10">
                  <h3 class="global_post_title mb-4"><?php the_title(); ?></h3>

               </div>

               <div class="col-md-12">
                  <p class="post_date"><span class="fw-bold"><?php echo get_the_date(); ?></span> <?php echo __('updated', 'textdomain') . ' ' . get_the_modified_date(); ?></p>
                  <div class="single-featureImage">
                     <?php if (has_post_thumbnail()) : ?>
                        <img src="<?php the_post_thumbnail_url('full'); ?>" class="img-fluid" alt="">
                     <?php endif; ?>
                  </div>
               </div>
            </div>
         </div>


      </section>
      <!-- wallet single  end -->


      <!-- read complete blog  -->
      <section class="wallet_all_content pb-5">
         <div class="container">
            <div class="row">

               <div class="col-md-8">
                  <div class="single_wallet_grid">

                     <div class="blog_users  d-flex gap-2 flex-wrap  flex-md-nowrap  align-items-center justify-content-between mb-4">

                        <div class="d-flex align-items-center gap-2">
                           <div><?php echo get_avatar(get_the_author_meta('ID'), 32); ?></div>
                           <div>
                              <a href="<?php echo esc_url(get_author_posts_url(get_the_author_meta('ID'))); ?>" class="fs-13 black"><?php the_author(); ?></a>
                              <p class="mb-0 fs-12">@<?php echo esc_html(get_the_author_meta('user_login')); ?></p>
                           </div>
                        </div>




                        <?php
                        $terms = get_the_terms(get_the_ID(), 'news-tag');
                        ?>

                        <?php if ($terms) : ?>
                           <ul class="dcard-tags">
                              <?php foreach ($terms as $term) : ?>
                                 <li class="post_tags d-inline-block list-unstyled"><?php echo $term->name; ?></li>
                              <?php endforeach; ?>
                           </ul>
                        <?php endif; ?>
                     </div>


                     <div class="all_content mb-4">
                        <?php the_content(); ?>
                     </div>
                  </div>

                  <!-- updated on  -->
                  <div class="mb-4">
                     <p class="yellow fst-italic fw-bold ">The article was updated on <?php echo __('updated', 'textdomain') . ' ' . get_the_modified_date(); ?></p>
                  </div>
                  <!-- updated on  -->



                  <?php
                  $prev_post = get_previous_post();
                  $next_post = get_next_post();
                  ?>

                  <!-- pre next button -->
                  <div class="d-flex align-items-center gap-2 mb-3">
                     <?php if ($prev_post) : ?>
                        <div class="paginations_grid">
                           <a href="<?php echo get_permalink($prev_post); ?>">
                              <div class="d-flex align-items-center gap-2">
                                 <div>
                                    <i class="bi bi-arrow-left-circle-fill fs-24 yellow"></i>
                                 </div>
                                 <div>
                                    <span class="fs-12">Previous</span>
                                    <p class="fs-12 m-0 "><?php echo esc_html($prev_post->post_title); ?></p>
                                 </div>
                              </div>
                           </a>
                        </div>
                     <?php endif; ?>

                     <?php if ($next_post) : ?>
                        <div class="paginations_grid">
                           <a href="<?php echo get_permalink($next_post); ?>">
                              <div class="d-flex align-items-center gap-2 text-end">
                                 <div>
                                    <span class="fs-12">Next</span>
                                    <p class="fs-12 m-0 "><?php echo esc_html($next_post->post_title); ?></p>
                                 </div>
                                 <div>
                                    <i class="bi bi-arrow-right-circle-fill fs-24 yellow"></i>
                                 </div>
                              </div>
                           </a>
                        </div>
                     <?php endif; ?>
                  </div>
                  <!-- pre next button end -->




                  <!-- written by  -->
                  <div class="written_by_box mb-5">
                     <p>Written by</p>
                     <div class="blog_users  d-flex gap-2 flex-wrap  flex-md-nowrap  align-items-center justify-content-between mb-4">

                        <div class="d-flex align-items-center gap-2">
                           <div><?php echo get_avatar(get_the_author_meta('ID'), 32); ?></div>
                           <div>
                              <a href="<?php echo esc_url(get_author_posts_url(get_the_author_meta('ID'))); ?>" class="fs-13 black"><?php the_author(); ?></a>
                              <p class="mb-0 fs-12">@<?php echo esc_html(get_the_author_meta('user_login')); ?></p>
                           </div>
                        </div>



                        <ul class="dcard-tags">
                           <li class="post_tags d-inline-block list-unstyled ">306</li>
                           <li class="post_tags d-inline-block list-unstyled "> Posts</li>

                        </ul>
                     </div>

                     <div>
                        <p class="fs-14">Bogdan is a genuine outside-the-box thinker. His philosophy is never to settle and always evolve. Since the very first moment, he heard of Bitcoin, he knew it would revolutionize the world.</p>

                        <a href="<?php echo esc_url(get_author_posts_url(get_the_author_meta('ID'))); ?>" class="btn btn-primary-small">View author</a>
                     </div>
                  </div>
                  <!-- written by end -->

                  <?php


                  // Query additional posts by the same author
                  $author_posts = new WP_Query(array(
                     'post_type'      => 'news',
                     'author' => $author_id, // Query posts by the author ID
                     'posts_per_page' => 2, // Number of posts to display
                     'post_status' => 'publish', // Only display published posts
                     'post__not_in' => array(get_the_ID()) // Exclude the current post from the query
                  ));

                  // Check if there are posts
                  if ($author_posts->have_posts()) :
                  ?>
                     <!-- more author posts -->
                     <div class="row more_author_posts">
                        <div class="col-md-12">
                           <h3 class="fw-bold mb-md-4">More author posts</h3>
                        </div>
                     </div>

                     <div class="row">
                        <?php
                        // Loop through the author's posts
                        while ($author_posts->have_posts()) :
                           $author_posts->the_post();
                        ?>
                           <div class="col-md-6">
                              <div class="more_posts">
                                 <div class="row">
                                    <div class="col-3">
                                       <div class="news_images">
                                          <?php if (has_post_thumbnail()) : ?>
                                             <!-- Display post thumbnail -->
                                             <?php the_post_thumbnail('thumbnail', array('class' => 'img-fluid')); ?>
                                          <?php else : ?>
                                             <!-- Display a default image if no post thumbnail is set -->
                                             <img src="<?php echo get_template_directory_uri(); ?>/images/news.jpg" class="img-fluid" alt="">
                                          <?php endif; ?>
                                       </div>
                                    </div>
                                    <div class="col-9">
                                       <div class="mb-0">
                                          <!-- Display post title with link -->
                                          <a href="<?php the_permalink(); ?>" class="news_title"><?php the_title(); ?></a>
                                       </div>
                                       <div class="hot_users d-flex justify-content-between align-items-center pt-2">
                                          <div>
                                             <div class="d-flex align-items-center gap-2">
                                                <div class="hot_user_image">
                                                   <?php echo get_avatar(get_the_author_meta('ID'), 32); ?>
                                                </div>
                                                <a href="<?php echo get_author_posts_url(get_the_author_meta('ID')); ?>" class="fs-12 black" tabindex="0"><?php the_author(); ?></a>
                                             </div>
                                          </div>
                                          <div>
                                             <!-- Display post date -->
                                             <p class="fs-12 mb-0"><i class="bi bi-clock pe-1"></i><?php echo human_time_diff(get_the_time('U'), current_time('timestamp')) . ' ago'; ?></p>
                                          </div>
                                       </div>
                                    </div>
                                 </div>
                              </div>
                           </div>
                        <?php endwhile; ?>
                     </div>
                     <!-- more author posts end -->

                  <?php
                     // Restore global post data
                     wp_reset_postdata();
                  endif;
                  ?>


               </div>



               <div class="col-md-4">
               <?php  echo do_shortcode( '[single-ads-box]' ); ?>
               </div>
            </div>
         </div>
      </section>
      <!-- read complete blog end -->




      <section class="publish_your_posts pb-5">
         <div class="container">
            <div class="row justify-content-center text-center ">
               <div class="col-md-10">
                  <div class="publish_now">
                     <div class="mb-4">
                        <h2 class="fw-bold">Publish your own article</h2>
                        <p>Guest post article. Guaranteed publishing with just a few clicks</p>
                     </div>
                     <div>
                        <div class="d-flex gap-2 justify-content-center ">
                           <a href="" class="btn btn-primary ">Start Publishing</a>
                           <a href="" class="btn btn-secondary  ">Advertise With Us</a>
                        </div>
                     </div>
                  </div>
               </div>
            </div>
         </div>
      </section>



      <!-- wallet single page  -->


      <!-- Browse categories  -->
      <section class="browse_category pb-5 pt-5 grey_bg">
         <div class="container">
            <div class="row pb-3">
               <div class="col-md-12">
                  <h3 class="fw-bold">Browse categories</h3>
                  <p class="fw-bold fs-20">Explore trending topics in the crypto community right now.
                  </p>
               </div>
            </div>
       
            <div class="row topics_row">
    <?php
    // Get all categories in the 'news-category' taxonomy
    $categories = get_categories(array(
        'taxonomy' => 'news-category',
        'hide_empty' => false, // Show categories even if they have no posts
    ));

    // Loop through each category
    foreach ($categories as $category) {
        // Query posts for the current category
        $category_posts = new WP_Query(array(
            'post_type' => 'news', // Assuming 'news' is your custom post type
            'tax_query' => array(
                array(
                    'taxonomy' => 'news-category',
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
                                                <img src="<?php echo get_template_directory_uri(); ?>/images/default-thumbnail.jpg" class="img-fluid" alt="Default Thumbnail">
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
      <!-- Browse categories end -->





<?php
   }
}
get_footer(); ?>