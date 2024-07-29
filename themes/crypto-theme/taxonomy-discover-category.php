<?php get_header();?>

  <!-- mini banner section  -->
  <section class="mini_banner">
         <div class="container">
            <div class="row">
               <div class="col-md-8">
                  <h2 class="global_heading"><span class="yellow"><?php single_term_title(); ?></span></h2>
                  <p class="fw-bold fs-18"><?php echo category_description(); ?></p>
                  
               </div>
            </div>
         </div>
      </section>
      <!-- mini banner section end -->





      <!-- wallet listing section  -->
      <section class="wallet_">
         <div class="container">
            <div class="row">
               <div class="col-md-12">
               <?php 
                // Query posts related to the current category
                $args = array(
                    'post_type' => 'discover', // You can change 'post' to your custom post type if applicable
                    'tax_query' => array(
                        array(
                            'taxonomy' => 'discover-category',
                            'field' => 'slug',
                            'terms' => get_queried_object()->slug, // Get the slug of the current category
                        ),
                    ),
                );
                $query = new WP_Query($args);

                if ($query->have_posts()) :
                  while ($query->have_posts()) : $query->the_post();
                ?>
                  <div class="wallet_grid">
                     <div class="wallet_group d-flex flex-wrap flex-sm-nowrap gap-2   align-items-center justify-content-between mb-4 ">
                           <div class="wallet_image_group d-flex align-items-center gap-2  ">
                              <div>
                                  
                                 <?php if (has_post_thumbnail()) : ?>
                                             <!-- Display post thumbnail -->
                                             <?php the_post_thumbnail('thumbnail', array('class' => 'img-fluid wallet_logo')); ?>
                                          <?php else : ?>
                                             <!-- Display a default image if no post thumbnail is set -->
                                             <img src="<?php echo get_template_directory_uri(); ?>/images/Ellipal.svg" class="img-fluid wallet_logo" alt="<?php the_title(); ?>">
                                          <?php endif; ?>
                              </div>
                              <div><h3 class="mb-0 fw-bold "><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3></div>
                           </div>

                           <div class="wallet_rating">
                              <ul>
                                 <li class="d-inline-block list-unstyled"><span class="fa fa-star yellow"></span></li>
                                 <li class="d-inline-block list-unstyled"><span class="fa fa-star yellow"></span></li>
                                 <li class="d-inline-block list-unstyled"><span class="fa fa-star yellow"></span></li>
                                 <li class="d-inline-block list-unstyled"><span class="fa fa-star yellow"></span></li>
                                 <li class="d-inline-block list-unstyled"><span class="fa fa-star yellow"></span></li>
                              </ul>
                           </div>
                     </div>

                     <p class="mb-4"><?php the_excerpt(); ?></p>



                  <div class="wallet_btn_group flex-wrap flex-md-nowrap d-flex   gap-2   align-items-center justify-content-between ">
                  <?php
                        $terms = get_the_terms(get_the_ID(), 'discover-tag');
                        ?>

                        <?php if ($terms) : ?>
                           <ul class="dcard-tags">
                              <?php foreach ($terms as $term) : ?>
                                 <li class="post_tags d-inline-block list-unstyled"><?php echo $term->name; ?></li>
                              <?php endforeach; ?>
                           </ul>
                        <?php endif; ?>

                     <div>
                        <ul>
                           <li class="d-inline-block list-unstyled m-1 "><a href="<?php echo get_field('view_site_url');?>" class="btn btn-primary ">Visit site</a></li>
                           <li class="d-inline-block list-unstyled m-1 "><a href="<?php the_permalink(); ?>" class="btn btn-secondary ">Full review</a></li>
                        </ul>
                     </div>
                  </div>
                  </div>
                  <?php endwhile; else : ?>
          <p>No posts found for this category.</p>
                  <?php  endif;
                  
                  
                  wp_reset_postdata(); ?>

                  <div class="hype_loot_wallet text-center ">
                     
                           <a href=""><div class="d-inline-block d-md-flex text-center  justify-content-center align-items-center gap-2 ">
                              <div><img src="<?php echo get_template_directory_uri(); ?>/images/hypeloot-full.png" class="img-fluid hype_loots " alt=""></div>
                              <div>
                                 <p class="m-0 white "><span class="fw-bold yellow">Hypeloot</span> The Fastest Growing Crypto Casino! 💰</p>
                              </div>
                           </div></a>
                        
                  </div>
                  
               </div>
            </div>
         </div>
      </section>
      <!-- wallet listing section end -->


<?php get_footer(); ?>