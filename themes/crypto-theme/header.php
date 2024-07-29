<!DOCTYPE html>
<html <?php language_attributes(); ?>>

<head>
   <meta charset="<?php bloginfo('charset'); ?>">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title><?php wp_title('|', true, 'right'); ?><?php bloginfo('name'); ?></title>

   <!-- css  -->
   <link rel="stylesheet" href="<?php echo get_template_directory_uri(); ?>/css/bootstrap.min.css">
   <link rel="stylesheet" href="<?php echo get_template_directory_uri(); ?>/css/style.css">
   <link rel="stylesheet" href="<?php echo get_template_directory_uri(); ?>/css/menu.css">
   <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
   <!-- slick css -->
   <link rel="stylesheet" href="<?php echo get_template_directory_uri(); ?>/css/slick-theme.css">
   <link rel="stylesheet" href="<?php echo get_template_directory_uri(); ?>/css/slick.css">
   <link rel="stylesheet" href="<?php echo get_template_directory_uri(); ?>/css/woocommerce.css">
   <!-- css end -->
	 <!-- select 2 css and js  -->
      <link rel="stylesheet" href="<?php echo get_template_directory_uri(); ?>/css/select2.min.css">

      <!-- select 2 css and js end -->
	
   <!-- fonts  -->
	   <link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Noto+Sans:ital,wght@0,100..900;1,100..900&family=Noto+Serif:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
   <link rel="preconnect" href="https://fonts.googleapis.com">
   <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
   <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,200..800;1,200..800&display=swap" rel="stylesheet">
   <!-- fonts end -->
   <?php wp_head(); ?>
	
	  <!-- canvas  -->
     <!-- <script src="https://code.jquery.com/jquery-1.11.1.min.js"></script> -->
     <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
      <script src="https://canvasjs.com/assets/script/jquery.canvasjs.min.js"></script>
       
      <!-- canvas  -->
</head>

<body <?php body_class(); ?>>


   <!-- main header  -->
   <header class="header">
      <div class="container">
         <div class="wrapper">
            <div class="header-item-left">

               <a href="<?php echo site_url(); ?>" class="navbar-brand"><?php
                                                                        $custom_logo_id = get_theme_mod('custom_logo');
                                                                        $logo = wp_get_attachment_image_src($custom_logo_id, 'full');
                                                                        if (has_custom_logo()) {
                                                                           echo '<img src="' . esc_url($logo[0]) . '" alt="' . get_bloginfo('name') . '" class="img-fluid ">';
                                                                        } else {
                                                                           echo '<h1>' . get_bloginfo('name') . '</h1>';
                                                                        }
                                                                        ?></a>

            </div>
            <!-- Section: Navbar Menu -->
            <div class="header-item-center">
               <div class="overlay"></div>
               <nav class="menu">
                  <div class="menu-mobile-header">
                     <button type="button" class="menu-mobile-arrow"><i class="bi bi-arrow-left"></i></button>
                     <div class="menu-mobile-title"></div>
                     <button type="button" class="menu-mobile-close"><i class="bi bi-x"></i></button>
                  </div>
                  <ul class="menu-section">

                     <li class="menu-item-has-children">
                        <a href="<?php echo $field_values = get_field('discover_url', 'option'); ?>"><?php echo $field_values = get_field('discover_text', 'option'); ?>  <i class="fa fa-angle-down"></i></a>
                        <div class="menu-subs menu-mega menu-column-4">
                           <div class="container">
                              <div class="row">
                                 <div class="col-md-12">
                                    <h5 class="white fw-600">Live Price</h5>

                                    <div class="prices_grid">

                                      <div id="live_price">
										    
										</div>
                                     
										
										
                                       <div class="live_price d-flex align-items-center gap-2">
                                          <div>
                                             <a href="<?php echo $field_values = get_field('discover_all_url', 'option'); ?>" class="yellow fw-bold fs-14"><span><?php echo $field_values = get_field('discover_all_text', 'option'); ?> </span><i class="bi bi-arrow-right ps-1"></i></a>

                                          </div>
                                       </div>

                                    </div>
                                 </div>
                              </div>


                              <div class="row">
                                 <div class="col-md-12">
                                    <ul class="mega_menu_listing">
                                       <?php
                                       // Get all categories in the 'discover-category' taxonomy
                                       $categories = get_categories(array(
                                          'taxonomy' => 'discover-category',
                                          'hide_empty' => false, // Show categories even if they have no posts
                                       ));

                                       // Loop through each category
                                       foreach ($categories as $category) {
                                       ?>
                                          <li>
                                             <a href="<?php echo get_term_link($category); ?>" class="yellow fw-bold mb-2 d-inline-block">
                                                <span><?php echo esc_html($category->name); ?></span>
                                                <i class="bi bi-arrow-right ps-1"></i>
                                             </a>
                                             <p><?php echo wp_trim_words( $category->description, 15); ?></p>
                                          </li>
                                       <?php } ?>
                                    </ul>

                                 </div>
                              </div>
                           </div>



                        </div>
                     </li>
                     <li class="menu-item-has-children">
                        <a href="<?php echo $field_values = get_field('topics_url', 'option'); ?>"><?php echo $field_values = get_field('topics_text', 'option'); ?> <i class="fa fa-angle-down"></i></a>
                        <div class="menu-subs menu-mega menu-column-4">



                           <div class="container">
                              <div class="row topics_row">
                                 <?php
                                 // Get the parent taxonomy terms
                                 $parent_terms = get_terms(array(
                                    'taxonomy' => 'topics-category',
                                    'parent' => 0, // Get only the top-level terms
                                    'hide_empty' => false, // Show empty terms
                                 ));

                                 foreach ($parent_terms as $parent_term) {
                                    // Output parent term link
                                    echo '<div class="' . (($parent_term->slug == 'guides') ? 'col-md-4' : 'col-md-2') . ' w_100">';
                                    echo '<a href="' . site_url($parent_term->slug) . '" class="mega_menu_title">' . $parent_term->name . '</a>';

                                    // Get the child taxonomy terms
                                    $child_terms = get_terms(array(
                                       'taxonomy' => 'topics-category',
                                       'parent' => $parent_term->term_id,
                                       'hide_empty' => false, // Show empty terms
                                    ));

                                    // Output child term links if available
                                    if (!empty($child_terms)) {
                                       echo '<ul class="mega_inner_menu ' . (($parent_term->slug == 'guides') ? 'two_col' : '') . '">';
                                       foreach ($child_terms as $child_term) {
                                          echo '<li><a href="' . get_term_link($child_term) . '">' . $child_term->name . '</a></li>';
                                       }
                                       echo '</ul>';
                                    }

                                    echo '</div>';
                                 }
                                 ?>

                                 <div class="col-md-3 w_100">
                                    <a href="" class="mega_menu_title">Press Release</a>
                                    <?php
                                    // Query the press-release custom post type
                                    $press_release_posts = get_posts(array(
                                       'post_type' => 'press-release', // Custom post type
                                       'posts_per_page' => 3, // Retrieve all posts
                                    ));

                                    // Check if there are any posts
                                    if ($press_release_posts) {
                                       echo '<ul class="press_post">';
                                       // Loop through each post
                                       foreach ($press_release_posts as $post) {
                                          setup_postdata($post);
                                          // Output post title wrapped in an <li> tag
                                          echo '<li><a href="' . esc_url(get_permalink()) . '">' . esc_html(get_the_title()) . '</a></li>';
                                       }
                                       echo '</ul>';
                                       // Reset post data
                                       wp_reset_postdata();
                                    } else {
                                       // If no posts found
                                       echo 'No press releases found.';
                                    }
                                    ?>

                                 </div>
                                 <div class="col-md-3 w_100">
<!--                                     <div class="mb-3">
                                       <a href="" class="mega_menu_title">Research</a>
                                       <p>Gain insight into the latest trends in the world of crypto. Explore in-depth analyses.</p>
                                    </div>
                                    <div class="mb-3">
                                       <a href="" class="mega_menu_title">Glossary</a>
                                       <p>Gain insight into the latest trends in the world of crypto. Explore in-depth analyses.</p>
                                    </div>
                                    <div class="mb-3">
                                       <a href="<?php echo site_url('people');?>" class="mega_menu_title">People</a>
                                       <p>Gain insight into the latest trends in the world of crypto. Explore in-depth analyses.</p>
                                    </div> -->
                                 
<?php
wp_nav_menu(array(
    'theme_location' => 'topics-right-menu',
    'container' => '<div class="col-md-3 w_100">',
    'menu_class' => 'right-menu',
    'menu_id' => '',
    'walker' => new Custom_Walker_Nav_Menu(),
    'add_li_class_has_child'  => 'right-menu-menu-item-has-children arrow',
    'add_class_has_child_box' => 'right-menu-subs'
));
?>
									 </div>
                              </div>
                           </div>

                        </div>
                     </li>
                     
                      
                     
                  </ul>
                  <!-- <ul class="right-menu">
                        <li class="right-menu-menu-item-has-children">
									<a href="#">News <i class="fa fa-angle-down toggle_in_mobile"></i></a>								 
										<ul class="right-menu-subs">
											<li><a href="#">Article One</a></li>
											<li><a href="#">Article Two</a></li>											 
										</ul>
									 
								</li>
                        <li><a href="#">Data</a></li>
								<li><a href="#">Community</a></li>
                     </ul> -->
                     <?php
 
 wp_nav_menu(array(
     'theme_location' => 'header',
     'container' => 'ul',
     'menu_class' => 'right-menu',
     'menu_id' => '',
     'add_li_class_has_child'  => 'right-menu-menu-item-has-children arrow',
     'add_class_has_child_box' => 'right-menu-subs'
 ));
 ?>
               </nav>
            </div>

            <div class="">
               <!-- <ul class="header-item-right">
                  <li><a href="" class="advertise_btn me-2 hide_on_mobile ">Advertise</a></li>
                  <li><a href="" class="publish_btn me-2 hide_on_mobile">Publish</a></li>
                  <li><a href="#" class="menu-icon"><i class="bi bi-search"></i></a></li>
               </ul> -->
				<div class="right_area_menu">
					 <?php
 
 wp_nav_menu(array(
     'theme_location' => 'right-menu',
     'container' => 'ul',
     'menu_class' => 'header-item-right'
 ));
 ?>
					
					<div class="search_top">
						   <a href="javascript:void(0)" class="menu-icon white" data-bs-toggle="modal" data-bs-target="#searchModal"><i class="bi bi-search"></i></a> 
					</div>
				</div>
              
               <!-- <a href="" class="advertise_btn me-2 hide_on_mobile ">Advertise</a>
               <a href="" class="publish_btn me-2 hide_on_mobile">Publish</a> -->
            

               <button type="button" class="menu-mobile-trigger">
                  <span></span>
                  <span></span>
                  <span></span>
                  <span></span>
               </button>
            </div>

         </div>
      </div>
   </header>
   <!-- main header end -->






    <!-- Search Modal -->
 <div class="modal fade search_modal" id="searchModal" tabindex="-1" aria-labelledby="searchModalLabel" aria-hidden="true">
   <div class="modal-dialog modal-xl">
     <div class="modal-content">
       <div class="modal-header dark_bg">
         <a class="navbar-brand" href="#">
            <img src="images/footer-logo.svg" class="img-fluid " alt="">
            </a>
         <button type="button" class="btn-close white" data-bs-dismiss="modal" aria-label="Close"></button>
       </div>
       <div class="modal-body">
         <section class="py-5">
            <div class="container">
               <div class="row pb-5">
                  <div class="col-md-12">                  
                     <div class="what_are">
                       <input type="search" id="search-input" autocomplete="off" placeholder="What are you looking for?" class="what_are_search">
                         <div id="search-results">
						 
						 </div>
                     </div>
                  </div>
               </div>
   
   
               <div class="row" id="have-serach-data">
                  <div class="col-md-12 mb-4">
                     <div class="d-inline-block d-md-flex  justify-content-between align-items-center mb-3 ">
                        <h4 class=" mb-0 mb-25 fw-bold ">Live prices</h4>
                        <div>
                           <a href="" class="btn btn-secondary ">See More</a>
                        </div>
                     </div>
                     <div class="crypto_table mb-4" style="overflow-x: auto;">
                        <table class="table ">
                           <thead>
                              <tr class="table-head">
                                 <td>Rank</td>
                                 <td>Cryptocurrency</td>
                                 <td>Price</td>
                                 <td>7 days change</td>
                                 <td>24h trading volume</td>
                                 <td>Marketcap</td>
                              </tr>
                           </thead>
                           <tbody id="crypto-table-body">
                             
                             
                           </tbody>
                        </table>
                     </div>
                  </div>

                  <div class="col-md-12">
                     <div class="row">

                        <div class="col-md-12">
                           <h4 class=" mb-0 mb-4 fw-bold ">Topics</h4>
                            
                        </div>
                        <!-- filter columns  -->
                         
                        <div class="search_slider">


                        <?php
    $args = array(
        'post_type' => 'topics', // Specify the post type
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
                        $post_tags =  get_the_terms(get_the_ID(), 'topics-tag');
                        if ($post_tags) {
                        foreach ($post_tags as $tag) {
                        echo '<li class="list-unstyled d-inline-block fs-13"><a href="' . get_tag_link($tag->term_id) . '">' . $tag->name . '</a></li>';
                        }
                        }
                        ?>
                        </ul>
                        <?php if (has_post_thumbnail()) : ?>
                            <img src="<?php the_post_thumbnail_url('full'); ?>" class="img-fluid" alt="">
                        <?php endif; ?>
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
                       
                        
                        <!-- filter columns end -->
                        
                         
                         
                        
                     </div>
                  </div>
               </div>
            </div>
         </section>
       </div>
        
     </div>
   </div>
 </div>
	<script>
	function topFourCoins() {
		 const cryptoApiUrl = 'https://api.coingecko.com/api/v3/coins/markets?vs_currency=usd&order=market_cap_desc&per_page=100&page=1&sparkline=false';
		        const options = {
            method: 'GET',
            headers: {
                accept: 'application/json',
                'x-cg-demo-api-key': 'CG-aJuTDgTp1nYxkH8hQ6EGrYjj'
            }
        };
    $.ajax({
        url: cryptoApiUrl,
        type: options.method,
        headers: options.headers,
        success: function(data) { 
// 			console.log(data);
            let htmlContent = '';
            // Iterate only up to the first 4 coins
            for (let index = 0; index < Math.min(data.length, 4); index++) {
                const coin = data[index];
                const priceChange24h = coin.price_change_percentage_24h || 0;

                htmlContent += `
                    <a href="/data/cryptocurrency-single?id=${coin.id}" class="live_price d-flex align-items-center gap-2">
                        <div><img src="${coin.image}" class="img-fluid live_price_logo" alt="${coin.name}"></div>
                        <div>
                            <p class="mb-0">${coin.name} ${coin.symbol.toUpperCase()}</p>
                            <p><span class="fw-500">$${coin.current_price}</span> &nbsp <span class="${priceChange24h >= 0 ? 'green' : 'red'}">${priceChange24h.toFixed(2)}%</span></p>
                        </div>
                    </a>
                `;
            }
            $('#live_price').html(htmlContent);
             
        },
        error: function(error) {
            console.error('Error fetching data from CoinGecko API:', error);
        }
    });
}
topFourCoins();
	</script>
					  <script>
    function topFourCoin() {
        const cryptoApiUrl = 'https://api.coingecko.com/api/v3/coins/markets?vs_currency=usd&order=market_cap_desc&per_page=100&page=1&sparkline=false';
        const options = {
            method: 'GET',
            headers: {
                accept: 'application/json',
                'x-cg-demo-api-key': 'CG-aJuTDgTp1nYxkH8hQ6EGrYjj'
            }
        };
        
        $.ajax({
            url: cryptoApiUrl,
            type: options.method,
            headers: options.headers,
            success: function(data) {
                let tableContent = '';
                // Iterate only up to the first 4 coins
                for (let index = 0; index < Math.min(data.length, 4); index++) {
                    const coin = data[index];
                    const priceChange24h = coin.price_change_percentage_24h || 0;
                    const priceChangeClass = priceChange24h >= 0 ? 'green' : 'red';
                    const priceChangeIcon = priceChange24h >= 0 ? 'fa-caret-up' : 'fa-caret-down';

                    tableContent += `
                        <tr>
                            <td><span class="crypto_rank">${coin.market_cap_rank}</span></td>
                            <td>
                                <div class="d-flex align-items-center gap-1">
                                    <img src="${coin.image}" class="coin_img" alt="${coin.name}">
                                    <span class="coin_name fw-bold">${coin.name}</span>
                                    <span class="coin_symbol">${coin.symbol.toUpperCase()}</span>
                                </div>
                            </td>
                            <td><span class="coin-price-dp">$${coin.current_price.toLocaleString()}</span></td>
                            <td>
                                <div class="coin_price_range d-flex align-items-center gap-1">
                                    <i class="fa ${priceChangeIcon} ${priceChangeClass}"></i> 
                                    <span class="${priceChangeClass}">${priceChange24h.toFixed(2)}%</span>
                                </div>
                            </td>
                            <td><span>$${(coin.total_volume / 1e9).toFixed(1)} B</span></td>
                            <td><span>$${(coin.market_cap / 1e12).toFixed(1)} T</span></td>
                        </tr>
                    `;
                }
                $('#crypto-table-body').html(tableContent);
            },
            error: function(error) {
                console.error('Error fetching data from CoinGecko API:', error);
            }
        });
    }

    $(document).ready(function() {
        topFourCoin();
    });
</script>



      <!-- search modal end -->