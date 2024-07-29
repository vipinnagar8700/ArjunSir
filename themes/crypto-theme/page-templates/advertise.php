<?php get_header();
/*
Template Name: Advertise
*/
?>
<style>
  .leaders p {
    color: #fff;
    font-size: 14px;
}
</style>
    <!-- hype loot section  -->
    <section class="hype_loot text-center ">
      <div class="container">
        <div class="row">
          <div class="col-md-12">
			  <a href="<?php the_field('hypeloop_link'); ?>">
            <div class="d-inline-block d-md-flex text-center  justify-content-center align-items-center gap-2 ">
              <div>
                <img src="<?php echo get_field('hypeloot_image'); ?>" class="img-fluid hype_loots " alt="">
              </div>
              <div>
                <?php echo get_field('hypeloop_text'); ?>
              </div>
            </div>
			  </a>
          </div>
        </div>
      </div>
    </section>
    <!-- banner section  -->
    <section class="banner_background" style="background-image: url(<?php echo get_field('banner_image'); ?>);">
      <div class="container">
        <div class="row">
          <div class="col-md-8 col-lg-6">
           <?php echo get_field('banner_title'); ?>
           <?php echo get_field('banner_content'); ?>
          </div>
        </div>
      </div>
    </section>
    <!-- banner section end  -->
    <!-- ready to reach section  -->
    <section class="py-5">
      <div class="container">
        <div class="row pb-4">
          <div class="col-md-12">
            <h2 class="global_heading white mb-0">
              <span class="yellow"><?php echo get_field('yellow_heading'); ?></span>
            </h2>
            <h2 class="global_heading "><?php echo get_field('black_heading'); ?></h2>
          </div>
        </div>
        <div class="row pb-0">
			 <?php $rows = get_field('count_and_text'); 
			foreach($rows as $row){
			?>
          <div class="col-6 col-sm-6 mb-25 col-md-3 col-lg-2">
            <h2 class="reach_title fw-bold "><?php echo $row['count_text'];?> +</h2>
            <p class="green fw-bold fs-18 mb-0"><?php echo $row['title'];?></p>
          </div>
      <?php } ?>
        </div>
      </div>
    </section>
    <!-- ready to reach section end -->
    <!-- our audience  -->
    <section class="our_audience">
      <div class="container">
        <div class="row pb-4">
          <div class="col-md-12">
            <h2 class="global_heading white ">
              <span class="yellow"><?php the_field('audience_yellow_heading'); ?></span> <?php the_field('audience_black_heading'); ?>
            </h2>
          </div>
        </div>
        <div class="row our_audiece_map">
          <div class="col-md-12">
            <div class="map_grid">
              <img src="<?php the_field('audience_image'); ?>" class="w-100" alt="">
            </div>
          </div>
        </div>
        <div class="row align-items-end audience_row">
			<?php if( have_rows('audience_image_copy') ):  
            while( have_rows('audience_image_copy') ): the_row();  
           
                  $image = get_sub_field('image');
			      $audience_percentage = get_sub_field('audience_percentage');
			      $audience_name_or_device_name = get_sub_field('audience_name_or_device_name');
                   
          ?>
          <div class="col-md-2 col-sm-4 col-6">
            <div class="audience_grid">
              <img src="<?php echo $image; ?>" class="img-fluid m-auto " alt="">
              <div class="audience_data">
                <h2 class="fw-bold white mb-0"><?php echo $audience_percentage; ?></h2>
                <p class="green fw-bold mb-0"><?php echo $audience_name_or_device_name; ?></p>
              </div>
            </div>
          </div>
			<?php endwhile;   endif; ?>
<!--           <div class="col-md-2 col-sm-4 col-6">
            <div class="audience_grid">
              <img src="<?php echo get_template_directory_uri(); ?>/images/female.svg" class="img-fluid m-auto " alt="">
              <div class="audience_data">
                <h2 class="fw-bold white mb-0">41,2%</h2>
                <p class="green fw-bold mb-0">Female</p>
              </div>
            </div>
          </div>
          <div class="col-md-2 col-sm-4 col-6">
            <div class="audience_grid">
              <img src="<?php echo get_template_directory_uri(); ?>/images/mobile.svg" class="img-fluid m-auto " alt="">
              <div class="audience_data">
                <h2 class="fw-bold white mb-0">54,7%</h2>
                <p class="green fw-bold mb-0">Mobile</p>
              </div>
            </div>
          </div>
          <div class="col-md-3 col-sm-6 col-6">
            <div class="audience_grid">
              <img src="<?php echo get_template_directory_uri(); ?>/images/desktop.svg" class="img-fluid m-auto " alt="">
              <div class="audience_data">
                <h2 class="fw-bold white mb-0">44,3%</h2>
                <p class="green fw-bold mb-0">Desktop</p>
              </div>
            </div>
          </div>
          <div class="col-md-3 col-sm-6 col-6">
            <div class="audience_grid">
              <img src="<?php echo get_template_directory_uri(); ?>/images/tablet.svg" class="img-fluid m-auto " alt="">
              <div class="audience_data">
                <h2 class="fw-bold white mb-0">1%</h2>
                <p class="green fw-bold mb-0">Tablet</p>
              </div>
            </div>
          </div> -->
        </div>
      </div>
    </section>
    <!-- our audience end -->
    <!-- we are working  -->
    <section class="py-5">
      <div class="container">
        <div class="row pb-4">
          <div class="col-md-6">
            <h2 class="global_heading ">
              <span class="yellow"><?php the_field('working_yellow_heading'); ?></span> <?php the_field('working_black_heading'); ?>
            </h2>
          </div>
        </div>
      </div>
      <div class="container">
        <div class="row">
          <div class="col-md-12">
            <div class="leaders_slider">
            <?php
					 
           $args = array(  
           'post_type' => 'leaders',
           'post_status' => 'publish',
           'order'		=>'ASC',
           'posts_per_page' => -1, 
 
           );
 
           $loop = new WP_Query( $args ); 
 
           while ( $loop->have_posts() ) :                   $loop->the_post(); 
           ?>
 

              <div>
                <div class="leaders">
                  <div class="mb-4">
                    <h3 class="fw-bold yellow mb-1"><?php echo the_title();?></h3>
                    <div class="white fs-14 leaders_para"><?php echo the_content(); ?> </div>
                  </div>
                  <div class="d-flex flex-wrap align-items-center gap-3 ">
                 <?php $rows  = get_field('logos');
                 foreach($rows as $row){ ?>
                    <div>
                      <a href="">
                        <img src="<?php echo $row['logo'];?>" class="leaders_logos" alt="">
                      </a>
                    </div>
                    <?php } ?>

                  </div>
                </div>
              </div>
              <?php endwhile;  wp_reset_postdata(); ?>

           
             
            </div>
          </div>
        </div>
      </div>
    </section>
    <!-- we are working end -->
    <!-- listing format  -->
    <section class="py-5">
      <div class="container">
        <div class="row pb-3">
          <div class="col-md-6">
            <h2 class="global_heading ">
              <span class="yellow"><?php the_field('banner_ads_yellow_heading'); ?></span> <?php the_field('banner_ads_black_heading'); ?>
            </h2>
            <h2 class="fw-bold green"><?php the_field('banner_ads_green_heading'); ?></h2>
            
          </div>
			<p><?php the_field('banner_ads_description'); ?></p>
        </div>
        <div class="row">
          <div class=" col-md-4 col-lg-3">
            <ul class="nav nav-pills mb-3 vertical_tabs" id="pills-tab" role="tablist">

            <?php $rows = get_field('banner_ads'); 
            foreach ($rows as $key => $tab) { ?>
               <li class="nav-item" role="presentation">
                <button class="nav-link <?php echo ($key==0) ? 'active' : 'x' ?>" id="ads-<?php echo $key; ?>-tab" data-bs-toggle="pill" data-bs-target="#ads-<?php echo $key; ?>" type="button" role="tab" aria-controls="ads-<?php echo $key; ?>" aria-selected="false"><?php echo $tab['tab_heading']; ?></button>
              </li>
           <?php  }  ?>

            </ul>
          </div>
          <div class="col-md-10   col-lg-9">
            <div class="tab-content" id="pills-tabContent">
            <?php $rows = get_field('banner_ads'); 
            foreach ($rows as $key => $tab) { ?>
             <div class="tab-pane fade <?php echo ($key==0) ?'show active' : '' ?>" id="ads-<?php echo $key; ?>" role="tabpanel" aria-labelledby="ads-<?php echo $key; ?>-tab" tabindex="0">
                <img src="<?php echo $tab['tab_content']; ?>" class="img-fluid " alt="">
              </div>
           <?php  }  ?>

              
            </div>
          </div>
        </div>
      </div>
    </section>
    <!-- listing format end -->
    <!-- pr section  -->
    <section class="py-5 grey_bg">
      <div class="container">
        <div class="row">
          <div class="col-md-6">
            <div class="mb-5">
              <h2 class="global_heading ">
                <span class="yellow"><?php the_field('pr_heading'); ?></span>
              </h2>
              <p><?php the_field('pr_text'); ?></p>
            </div>
            <div class="row pb-0">
				<?php if( have_rows('followers') ):  
            while( have_rows('followers') ): the_row();  
           
                  $counters = get_sub_field('counters');
			      $platform_name = get_sub_field('platform_name');
                   
          ?>
              <div class="col-md-6 col-6 col-lg-6 mb-4">
                <h2 class="reach_title fw-bold "><?php echo $counters; ?></h2>
                <p class="green fw-bold fs-18 mb-0"><?php echo $platform_name; ?></p>
              </div>
				 <?php endwhile;   endif; ?>
<!--               <div class="col-md-6 col-6 col-lg-6 mb-4">
                <h2 class="reach_title fw-bold ">10k+</h2>
                <p class="green fw-bold fs-18 mb-0">discord subscribers</p>
              </div>
              <div class="col-md-6 col-6 col-lg-6 mb-4">
                <h2 class="reach_title fw-bold ">8k+</h2>
                <p class="green fw-bold fs-18 mb-0">telegram followers</p>
              </div>
              <div class="col-md-6 col-6 col-lg-6 mb-4">
                <h2 class="reach_title fw-bold ">120k+</h2>
                <p class="green fw-bold fs-18 mb-0">email subscribers</p>
              </div> -->
            </div>
          </div>
          <div class="col-md-6">
            <img src="<?php the_field('pr_right_image'); ?>" class="img-fluid " alt="">
          </div>
        </div>
      </div>
    </section>
    <!-- pr section end -->
    <!-- email newslattor  -->
    <section class="py-5">
      <div class="container">
        <div class="row align-items-center ">
          <div class="col-md-5 mb-25">
            <img src="<?php the_field('email_newsletter_image'); ?>" class="img-fluid " alt="">
          </div>
          <div class="col-md-6">
            <h2 class="fw-bold"><?php the_field('email_newsletter_heading'); ?></h2>
            <p><?php the_field('email_newsletter_text'); ?></p>
          </div>
        </div>
      </div>
    </section>
    <!-- email newslattor end -->
    <!-- complex campaign  -->
    <section class="py-5">
      <div class="container">
        <div class="row pb-3">
          <div class="col-md-6">
            <h2 class="global_heading ">
              <span class="yellow"><?php the_field('complex_yellow_heading'); ?></span> <?php the_field('complex_black_heading'); ?>
            </h2>
          </div>
        </div>
        <div class="row pb-4">
          <div class="col-md-3">
            <ul class="nav nav-pills mb-3 vertical_tabs" id="pills-tab" role="tablist">

            <?php $rows = get_field('complex_campaign'); 
            foreach ($rows as $key => $tab) { ?>
               <li class="nav-item" role="presentation">
                <button class="nav-link <?php echo ($key==0) ? 'active' : 'x' ?>" id="pills-<?php echo $key; ?>-tab" data-bs-toggle="pill" data-bs-target="#pills-<?php echo $key; ?>" type="button" role="tab" aria-controls="pills-<?php echo $key; ?>" aria-selected="false"><?php echo $tab['tab_heading']; ?></button>
              </li>
           <?php  }  ?>
        
            </ul>
          </div>
          <div class="col-md-9">
            <div class="tab-content" id="pills-tabContent">
            
            <?php $rows = get_field('complex_campaign'); 
            foreach ($rows as $key => $tab) { ?>
             <div class="tab-pane fade <?php echo ($key==0) ?'show active' : '' ?>" id="pills-<?php echo $key; ?>" role="tabpanel" aria-labelledby="pills-<?php echo $key; ?>-tab" tabindex="0">
                <div class="campaign_grid">
                <?php echo $tab['tab_content']; ?>
                </div>
              </div>
           <?php  }  ?>
  
            </div>
          </div>
        </div>
        <div class="row">
          <div class="col-md-12">
            <div class="d-flex gap-2 justify-content-end  ">
              <div>
                <a href="<?php the_field('first_btn_link'); ?>" class="btn btn-primary "><?php the_field('first_btn_text'); ?></a>
              </div>
              <div>
                <a href="<?php the_field('second_btn_link'); ?>" class="btn btn-secondary  "><?php the_field('second_btn_text'); ?></a>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
    <!-- complex campaign end -->
  <?php get_footer(); ?>