<?php get_header(); 
 
?>
      <!-- hype loot section  -->
      <section class="hype_loot text-center ">
         <div class="container">
            <div class="row">
               <div class="col-md-12">
                  <div class="d-inline-block d-md-flex text-center  justify-content-center align-items-center gap-2 ">
                     <div><img src="images/hypeloot-full.png" class="img-fluid hype_loots " alt=""></div>
                     <div>
                        <p class="m-0 "> Join the First-Ever AI Powered Casino and Get Profits Daily! 💰</p>
                     </div>
                  </div>
               </div>
            </div>
         </div>
      </section>
  

      <section class="bread_crumb">
         <div class="container">
            <div class="row">
               <div class="col-md-12">
                  <ul>
                     <li class="d-inline-block list-unstyled pe-2 "><a class="fs-13 black" href="">Home</a></li>
                     <li class="d-inline-block list-unstyled pe-2 "><a class="fs-13 black" href=""><i class="fa fa-angle-right"></i></a></li>
                     <li class="d-inline-block list-unstyled pe-2 "><a class="fs-13 black" href="">Category</a></li>
                     <li class="d-inline-block list-unstyled pe-2 "><a class="fs-13 black" href=""><i class="fa fa-angle-right"></i></a></li>
                     <li class="d-inline-block list-unstyled pe-2 "><a class="fs-13 black" href=""><?php echo get_queried_object()->name ; ?></a></li>
                  </ul>
               </div>
            </div>
         </div>
      </section>
 
      


 
      <section class="single_wallet_post py-4">
         <div class="container">
            <div class="row">
               <div class="col-md-8">
                  <h2 class="global_heading"><?php echo get_queried_object()->name ; ?></h2>
                   
                  
               </div>

               
            </div>
         </div>
 
      
      </section>


      <!-- categories listings with filter  -->
      <section class="listing_with_filter pb-5">
         <div class="container">
            <div class="row">
               <div class="col-md-12">
                     <!-- filter_main_container (Center website) -->
      <div class="filter_main_container archive_ ">
      

         <!-- Portfolio Gallery Grid -->
         <div class="row">
    <!-- filter columns  -->
    <?php
     $paged = (get_query_var('paged')) ? get_query_var('paged') : 1;
    $args = array(
        'post_type' => 'post', 
        'posts_per_page' => 12, // Number of posts per page
        'order'          => 'DESC',
        'paged'          => $paged ,// Current page
        'tax_query' => array(
            array(
                'taxonomy' => 'category',
                'field'    => 'slug', // Change to 'term_id' if you want to use category ID
                'terms'    => get_queried_object()->slug, // Change to the slug of your reviews category
            ),
        ),
    );

    $query = new WP_Query($args);

    if ($query->have_posts()) :
        while ($query->have_posts()) : $query->the_post();
        $post_categories = wp_get_post_terms(get_the_ID(), 'category', array('fields' => 'all'));

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
                        $terms = get_the_terms(get_the_ID(), 'post_tag');
                        ?>

                        <?php if ($terms) : ?>
                           <ul class="hot_topics_tags ps-0 m-0">
                              <?php foreach ($terms as $term) : ?>
                     
                                 <li class="list-unstyled d-inline-block fs-13"><a href="javascript:void(0)" tabindex="0"><?php echo $term->name; ?></a></li>
                              <?php endforeach; ?>
                           </ul>
                        <?php endif; ?>
                        <a href="<?php the_permalink(); ?>">
                            <?php
                            if (has_post_thumbnail()) {
                                the_post_thumbnail('full', array('class' => 'img-fluid'));
                            } else {
                                echo '<img src="' . site_url() . '/wp-content/themes/crypto-theme/images/default-thumbnail.jpg" class="img-fluid archive_image" alt="Default Image">';
                            }
                            ?>
                        </a>
                        
                    </div>
                    <div class="hot_topics_content">
                        <a class="fs-15 fw-600 mb-2 black d-inline-block archive_title " href="<?php the_permalink(); ?>">
                            <?php the_title(); ?>
                        </a>
                       <p class="fs-14 archive_content"><?php echo wp_trim_words( get_the_content(), 30 ); ?></p>
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
      <!-- categories listings with filter end -->
  
 <?php get_footer(); ?>