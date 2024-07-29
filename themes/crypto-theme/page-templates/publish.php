<?php get_header();
/*
Template Name: Publish
*/
 
?>
      <!-- hype loot section  -->
      <section class="hype_loot text-center ">
         <div class="container">
            <div class="row">
               <div class="col-md-12">
				   <a href="<?php the_field('link'); ?>">
                  <div class="d-inline-block d-md-flex text-center  justify-content-center align-items-center gap-2 ">
                     <div><img src="<?php the_field('image'); ?>" class="img-fluid hype_loots " alt=""></div>
                     <div>
                        <p class="m-0 "><?php the_field('text'); ?></p>
                     </div>
                  </div>
					   </a>
               </div>
            </div>
         </div>
      </section>
  

 
 

      <!-- publish main container  -->
      <div class="comunity_main py-5">
         <div class="container">
            <div class="row publish_row">
               <div class="col-md-2 ">
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
               <div class="col-md-10">
                   <!-- publish section  -->
 <section class="publish_your_posts pb-5">
   
      <div class="row justify-content-center text-center ">
         <div class="col-md-12">
            <div class="publish_nows">
                  <div class="mb-4">
                     <h1 class="fw-bold"><span class="yellow"><?php the_field('yellow_heading'); ?></span> <?php the_field('black_heading'); ?></h1>
                  <p><?php the_field('publishing_text'); ?>

                  </p>
                  </div>
                  <div>
                    <div class="d-flex gap-2 justify-content-center ">
                     <a href="<?php the_field('publishing_first_btn_link'); ?>" class="btn btn-primary "><?php the_field('publishing_first_btn_text'); ?></a>
                     <a href="<?php the_field('publishing_second_btn_link'); ?>" class="btn btn-secondary  "><?php the_field('publishing_second_btn_text'); ?></a>
                    </div>
                  </div>
            </div>
         </div>
      </div>
   
</section>
 <!-- publish section end -->
  <!-- post article  -->
  <section class="pb-5">
    
      <div class="row">
         <div class="col-md-12">
            <div class="wrap_community mb-4">
               <div class="row align-items-center ">
                  <div class="col-md-6">
                     <h2 class="fw-bold "><?php the_field('community_heading'); ?></h2>
                     <p><?php the_field('community_text'); ?></p>
                  </div>
                  <div class="col-md-6">
                     <img src="<?php the_field('community_right_image'); ?>" class="img-fluid " alt="">
                  </div>
               </div>
            </div>
         </div>

         <div class="col-md-12">
<div class="row">
	 <?php if( have_rows('community_types') ):  
            while( have_rows('community_types') ): the_row();  
                  $image = get_sub_field('image');
	$heading = get_sub_field('heading');
	$text = get_sub_field('text');
          ?>
            <div class="col-md-4 mb-25">
               <div class="wrap_community">
                  <img src="<?php echo $image; ?>" class="img-fluid mb-3 same_height" alt="">
                  <h5 class="fw-bold "><?php echo $heading; ?></h5>
                  <p><?php echo $text; ?></p>
               </div>
            </div>
	<?php endwhile;   endif; ?>

<!--             <div class="col-md-4 mb-25">
               <div class="wrap_community">
                  <img src="<?php echo get_template_directory_uri(); ?>/images/backlinks.svg" class="img-fluid mb-3 same_height" alt="">
                  <h5 class="fw-bold ">Backlinks & SEO
                  </h5>
                  <p>Online presence is key. Get your content trending to boost awareness and visibility among the target audience.

                  </p>
               </div>
            </div>
            <div class="col-md-4 mb-25">
               <div class="wrap_community">
                  <img src="<?php echo get_template_directory_uri(); ?>/images/reach.svg" class="img-fluid mb-3 same_height" alt="">
                  <h5 class="fw-bold ">Reach to 1M followers
                  </h5>
                  <p>Online presence is key. Get your content trending to boost awareness and visibility among the target audience.

                  </p>
               </div>
            </div> -->
         </div>
         </div>
      </div>
    
 </section>
 <!-- post article end -->




 <!-- how it works  -->
 <div class="how_it_works">
   <div class="row">
      <div class="col-md-12 text-center pb-4">
         <h2 class="fw-bold "><span class="yellow"><?php the_field('how_it_works_yellow_heading'); ?></span> <?php the_field('how_it_works_black_heading'); ?></h2>
      </div>
    </div>
    <div class="row stepper">
      <?php
      $hiws = get_field('how_it_works');
      if($hiws){
         foreach($hiws as $key => $hiw){ ?>
      <div class="col-md-6">
           <div class="stepper_box">
            <span class="step_counter"><?php echo $key + 1 ; ?></span>
            <div class="d-flex align-items-center gap-3 ">
               <div class="col-3 text-center">
                  <img src="<?php echo $hiw['image']; ?>" class="img-fluid same_height m-auto" alt="<?php echo $hiw['title']; ?>">
               </div>
               <div class="col-9">
                  <h4 class="mb-1"><?php echo $hiw['title']; ?></h4>
                  <p class="mb-1"><?php echo $hiw['content']; ?></p>
                  <!-- <a href="" class="yellow"><span>Register Here</span><i class="bi bi-arrow-right ps-1"></i></a> -->
               </div>
            </div>
           </div>
      </div>
<?php }} ?>
      

    </div>
 </div>
 <!-- how it works end -->





 <!-- package slider  -->
 <section class="py-5">
   
      <div class="row">
         <div class="col-md-12 mb-3">
            <h2 class="fw-bold "><?php the_field('heading'); ?></h2>
         </div>
      </div>


      <div class="row">
      <div class="package_slider">
      <?php
$args = array(
    'post_type' => 'product',
    'posts_per_page' => -1,
    'tax_query' => array(
        array(
            'taxonomy' => 'product_cat',
            'field'    => 'slug',
            'terms'    => 'packages', // Replace 'your_category_slug' with the slug of your product category
        ),
    ),
);

$products = new WP_Query( $args );

if ( $products->have_posts() ) {
    while ( $products->have_posts() ) {
        $products->the_post();
        $product_tags = get_the_terms( get_the_ID(), 'product_tag' );
        $add_to_cart_url = esc_url( wc_get_cart_url() ) . '?add-to-cart=' . get_the_ID();
        ?>
        
            <div>
                <div class="cart_card">
                    <div class="mb-2"> 
                    <?php 
                        if ($product_tags) {
                            foreach ($product_tags as $tag) {
                                echo '<a href="' . esc_url( get_term_link( $tag ) ) . '" class="cart_card_btn tags">' . esc_html( $tag->name ) . '</a>';
                            }
                        }
                        ?>
                  </div>
                    <h4 class="mb-2 article_title"><?php the_title(); ?><span>/credits</span></h4>
                    <h2 class="big_price"><?php echo wc_price( get_post_meta( get_the_ID(), '_regular_price', true ) ); ?></h2>
                    <hr style="background-color: #efefef;">
                    <p class="fs-14 mb-4 white"><?php echo get_the_content(); ?></p>
                    <?php if ( 'yes' === get_option( 'woocommerce_enable_ajax_add_to_cart' ) ) : ?>
                        <a href="javascript:void(0);" data-product_id="<?php echo get_the_ID(); ?>" data-quantity="1" class="button product_type_simple add_to_cart_btn add_to_cart_button ajax_add_to_cart">Add to cart</a>
                    <?php else : ?>
                        <a href="<?php echo esc_url( $add_to_cart_url ); ?>" class="button product_type_simple add_to_cart_btn">Add to cart</a>
                    <?php endif; ?>
                </div>
            
        </div>
        <?php
    }
    wp_reset_postdata();
} else {
    echo 'No products found';
}
?>
</div>
      </div>
  
 </section>
 <!-- package slider end -->






 <!-- extra option section  -->
 <section class="extra_option pb-5">
   <div class="row">
      <div class="col-md-12 mb-3">
         <h2 class="fw-bold "><?php the_field('extra_options_heading'); ?></h2>
      </div>
   </div>



   <div class="row">
   <?php
$args = array(
    'post_type' => 'product',
    'posts_per_page' => -1,
    'tax_query' => array(
        array(
            'taxonomy' => 'product_cat',
            'field'    => 'slug',
            'terms'    => 'extra-options', // Replace 'your_category_slug' with the slug of your product category
        ),
    ),
);

$products = new WP_Query( $args );

if ( $products->have_posts() ) {
    while ( $products->have_posts() ) {
        $products->the_post();
        $product_tags = get_the_terms( get_the_ID(), 'product_tag' );
        $add_to_cart_url = esc_url( wc_get_cart_url() ) . '?add-to-cart=' . get_the_ID();
        ?>
      <div class="col-sm-6 mb-lg-25 col-md-4 col-lg-3">
         <div class="extra_option_grid">
            <h4 class="white mb-2 fw-bold extra_opt_title "><?php the_title(); ?></h4>
            <h4 class="green fw-bold "><?php echo wc_price( get_post_meta( get_the_ID(), '_regular_price', true ) ); ?>/article</h4>
            <hr style="background-color: #efefef;">
            <p class="fs-13 mb-4 white extra_content"><?php echo get_the_content(); ?></p>
            <div class="mb-3"><p class="fs-13 mb-0 yellow extra_content"><?php echo get_the_excerpt(); ?></p></div>
            <?php if ( 'yes' === get_option( 'woocommerce_enable_ajax_add_to_cart' ) ) : ?>
                        <a href="javascript:void(0);" data-product_id="<?php echo get_the_ID(); ?>" data-quantity="1" class="button product_type_simple add_to_cart_btn add_to_cart_button ajax_add_to_cart">Add to cart</a>
                    <?php else : ?>
                        <a href="<?php echo esc_url( $add_to_cart_url ); ?>" class="button product_type_simple add_to_cart_btn">Add to cart</a>
                    <?php endif; ?>
         </div>
      </div>
     
      <?php
    }
    wp_reset_postdata();
} else {
    echo 'No products found';
}
?>
    
   </div>
 </section>
 <!-- extra option section end -->



 <!-- playing big section  -->
 <div class="pb-5">
   <div class="row align-items-center">
      <div class="col-md-7">
         <h2 class="fw-bold "><span class="yellow"><?php the_field('playing_yellow_heading'); ?></span> <?php the_field('playing_black_heading'); ?></h2>
         <p><?php the_field('playing_text'); ?> </p>
         <a href="<?php the_field('playing_button_link'); ?>" class="btn btn-primary "><?php the_field('playing_button_text'); ?></a>
      </div>
      <div class="col-md-5">
         <img src="<?php the_field('playing_right_image'); ?>" class="img-fluid " alt="">
      </div>
   </div>
 </div>
 <!-- playing big section end -->


 <div class="pb-5">
   <div class="row">
      <div class="col-md-12">
         <h2 class="fw-bold pb-3 "><?php the_field('faq_heading'); ?></h2>
         <div class="accordion" id="accordionExample">
 <?php $rows = get_field('faq'); 
 foreach($rows as $key => $data) {
 ?>
            <div class="accordion-item">
              <h2 class="accordion-header" id="heading<?php echo $key; ?>">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?php echo $key; ?>" aria-expanded="true" aria-controls="collapseOne">
                  <?php echo $data['question']; ?>
                </button>
              </h2>
              <div id="collapse<?php echo $key; ?>" class="accordion-collapse collapse  <?php ($key==0) ? 'show' : '' ?>" aria-labelledby="heading<?php echo $key; ?>" data-bs-parent="#accordionExample">
                <div class="accordion-body">
                <p><?php echo $data['answer']; ?></p>
                </div>
              </div>
            </div>

<?php } ?>
    
          </div>
      </div>
   </div>
 </div>



 <!-- apply for blue tick  -->
 <div class="blue_tick">
   <div class="row align-items-center">
      <div class="col-md-7">
         <h2 class="fw-bold "><?php the_field('blue_tick_heading'); ?></h2>
         <p><?php the_field('blue_tick_text'); ?></p>
         <a href="<?php the_field('blue_tick_button_link'); ?>" class="btn btn-primary "><?php the_field('blue_tick_button_text'); ?></a>
      </div>
      <div class="col-md-5">
         <img src="<?php the_field('blue_tick_right_image'); ?>" class="img-fluid " alt="">
      </div>
   </div>
 </div>
 <!-- apply for blue tick end -->
 


               </div>
            </div>
         </div>
      </div>
      <!-- publish main container end -->






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