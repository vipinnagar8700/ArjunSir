<?php get_header();
/*
Template Name: Research
*/
?>
      <!-- hype loot section  -->
      <section class="hype_loot text-center ">
         <div class="container">
            <div class="row">
               <div class="col-md-12">
                  <div class="d-inline-block d-md-flex text-center  justify-content-center align-items-center gap-2 ">
                     <div><img src="<?php echo get_template_directory_uri(); ?>/images/hypeloot-full.png" class="img-fluid hype_loots " alt=""></div>
                     <div>
                        <p class="m-0 "> Join the First-Ever AI Powered Casino and Get Profits Daily! 💰</p>
                     </div>
                  </div>
               </div>
            </div>
         </div>
      </section>
  

<!-- banner section  -->
      <section class="banner_background">
         <div class="container">
            <div class="row">
               <div class="col-md-8">
                  <h2 class="global_heading white">Guides</h2>
                  <p class="fw-bold fs-20 white">Gain insight into the latest trends in the world of crypto. News and in-depth analysis of Bitcoin, Ethereum, DeFi, NFT, and other hot trends will help you stay ahead of the game.</p>
                  <p class="white">Explore top-level reviews and in-depth analysis of the key cryptocurrency trends and projects performed by our experts.</p>
               </div>
            </div>
         </div>
      </section>

      <!-- banner section end  -->
   



      <!-- research listing  -->
      <section class="resarch_listing py-5">
         <div class="container">
            <div class="row">
               <div class="col-md-12">
                     <!-- filter_main_container (Center website) -->
      <div class="filter_main_container" >
         <div id="filter_btn_container" class="mb-3">
            <button class="filter_btn active" onclick="filterSelection('all')"> All</button>
			 <?php $rows = get_field('tabs'); 
			 foreach($rows as $row) {
			 ?>
            <button class="filter_btn" onclick="filterSelection('<?php echo $row['tab_name'];?>')"> <?php echo $row['tab_name'];?></button>
           <?php } ?>
         </div>
         <!-- Portfolio Gallery Grid -->
         <div class="row">
            <?php  $parents  = get_field('tabs');
			 foreach($parents as $parent){
			 foreach($parent['tab_content'] as $child){
			 ?>
            <div class="col-md-12 <?php echo $parent['tab_name'];?> filter_column">
               <div class="research_listing_card">
                     <div class="row ">
                        <div class="col-md-7 col-lg-6">
                          <div class="resarch_left">
                           <div class="mb-3">
                              <h3 class="fw-bold white"><?php echo $child['title'];?></h3>
                           <p class="white"><?php echo $child['content'];?></p>
                           </div>
                           <div class="mb-4">
                              <a href="<?php echo $child['link_url'];?>" class="borderless_btn"><span><?php echo $child['link_text'];?></span><i class="bi bi-arrow-right ps-1"></i></a>
                           </div>
                           <div class="mb-3">
                              <p class="white">Available on</p>
                              <div class="d-flex flex-wrap align-items-center gap-2 ">
								<?php 
				       foreach($child['logos'] as $logo){ ?>
                        <div><img src="<?php echo $logo['image'];?>" class="resarch_logos" alt=""></div>
 <?php } ?>
                              </div>
                           </div>
                          </div>
                        </div>
                        <div class="col-md-5 col-lg-6">
                           <img src="<?php echo  $child['image']; ?>" class="img-fluid metavese " alt="">
                        </div>
                     </div>
               </div>
            </div>
			 
         <?php }  } ?>
       
            
            
            <!-- END GRID -->
         </div>
         <!-- END filter_main_container -->
      </div>
               </div>
            </div>
         </div>
      </section>
      <!-- research listing end -->
  <?php get_footer();?>