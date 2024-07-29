      <!-- footer start  -->
      <section class="footer_area">
         <div class="container">
            <div class="row">
               <div class="col-12 col-sm-6 col-md-3">
                  <div class="footer_logo mb-3">
                     
                     <img src="<?php echo $field_value = get_field('logo', 'option'); ?>" class="img-fluid " alt="">
                  </div>
                  <p class="mb-3"><?php echo $field_value = get_field('description', 'option'); ?></p>
                  <div class="mb-3"> <a href="<?php echo $field_value = get_field('learn_more_url', 'option'); ?>" class="read_more yellow"><span>Learn More</span><i class="bi bi-arrow-right ps-1"></i></a></div>
                  <ul class="social_media mb-3 ms-0 p-0">
                  <?php echo $field_values = get_field('social_links', 'option'); 
                  foreach($field_values as $data){ ?>
                     <li class="d-inline list-unstyled  pe-2"><a class="white fs-20" href="<?php echo $data['url'];?>"><?php echo $data['icon'];?></a></li>
                  <?php } ?>
                  </ul>
                  <h5 class="white"><?php echo $field_value = get_field('join_text', 'option'); ?></h5>
               </div>
               <div class="col-12 col-sm-6 col-md-3">
                  <h4 class="footer_title">
                     Advertise with us
                  </h4>
                  <p>
                  <?php
                  $theme_locations = get_nav_menu_locations();
                  $menu_id = $theme_locations['advertise'];
                  $menu_object = wp_get_nav_menu_object($menu_id);
                  $menu_description =  get_field('description', $menu_object);
                  echo $menu_description;
                  ?>
                  </p>
                  <?php
                  wp_nav_menu(array(
                  'theme_location' => 'advertise',
                  'container' => '',
                  'menu_class' => 'footer_menus p-0 ms-0 mb-1',
                  'items_wrap' => '<ul id="%1$s" class="%2$s">%3$s</ul>',
                  ));
                  ?>
                  <div class="mb-3"> <a href="" class="read_more yellow"><span>Learn More</span><i class="bi bi-arrow-right ps-1"></i></a></div>
               </div>
               <div class="col-12 col-sm-6 col-md-3">
                  <h4 class="footer_title">
                     Top picks
                  </h4>
                  <?php
                  wp_nav_menu(array(
                  'theme_location' => 'top-picks',
                  'container' => '',
                  'menu_class' => 'footer_menus p-0 ms-0 mb-1',
                  'items_wrap' => '<ul id="%1$s" class="%2$s">%3$s</ul>',
                  ));
                  ?>
                  <div class="mb-3"> <a href="" class="read_more yellow"><span>Learn More</span><i class="bi bi-arrow-right ps-1"></i></a></div>
               </div>
               <div class="col-12 col-sm-6 col-md-3">
                  <h4 class="footer_title">
                     Community
                  </h4>
                  <p>
                  <?php
                  $theme_locations = get_nav_menu_locations();
                  $menu_id = $theme_locations['community'];
                  $menu_object = wp_get_nav_menu_object($menu_id);
                  $menu_description =  get_field('description', $menu_object);
                  echo $menu_description;
                  ?>
                  </p>
                  <?php
                  wp_nav_menu(array(
                  'theme_location' => 'community',
                  'container' => '',
                  'menu_class' => 'footer_menus p-0 ms-0 mb-1',
                  'items_wrap' => '<ul id="%1$s" class="%2$s">%3$s</ul>',
                  ));
                  ?>
                  <div class="mb-3"> <a href="" class="read_more yellow"><span>Learn More</span><i class="bi bi-arrow-right ps-1"></i></a></div>
               </div>
               <div class="col-md-12 text-center pt-4">
                  <p class="white mb-0 fs-15">@ 2018 - <?php echo date('Y'); ?> <?php bloginfo('name'); ?></p>
               </div>
            </div>
         </div>
      </section>
      <!-- footer start end -->
      <!-- js  -->
      <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
 <script src="<?php echo get_template_directory_uri(); ?>/js/select2.min.js"></script>
      <script src="<?php echo get_template_directory_uri(); ?>/js/slick.js"></script>
      <script src="<?php echo get_template_directory_uri(); ?>/js/bootstrap.bundle.min.js"></script>
      <script src="<?php echo get_template_directory_uri(); ?>/js/menu.js"></script>
    <script src="<?php echo get_template_directory_uri(); ?>/js/charts.js"></script>
      <script src="<?php echo get_template_directory_uri(); ?>/js/custom.js"></script>
      <!-- js end -->
      <?php wp_footer(); ?>
   </body>
</html>