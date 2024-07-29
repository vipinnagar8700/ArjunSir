<?php get_header();
/*
Template Name: People
*/
?>
      <!-- hype loot section  -->
      <section class="hype_loot">
         <div class="container">
            <div class="row">
               <div class="col-md-12">
                  <div class="d-flex justify-content-center align-items-center gap-2 ">
                     <div><img src="images/hypeloot-full.png" class="img-fluid hype_loots " alt=""></div>
                     <div>
                        <p class="m-0 "> Join the First-Ever AI Powered Casino and Get Profits Daily! 💰</p>
                     </div>
                  </div>
               </div>
            </div>
         </div>
      </section>
      <!-- hype loot section end -->
      <!-- hot section  -->
      <section class="hots py-5">
         <div class="container">
            <div class="row pb-5">
               <div class="col-md-12">
                  <h2 class="global_heading mb-4">People</h2>
                  <div class="what_are">
                     <input type="search" id="search-input" autocomplete="off" placeholder="Search People" class="what_are_search">
                  </div>
               </div>
            </div>
            <div class="row">
            <div class="col-md-9 mb-25" id="people-list">

<?php
$people_query = new WP_Query(array(
    'post_type' => 'people',
    'posts_per_page' => -1 // Display all people posts
));

if ($people_query->have_posts()) :
    while ($people_query->have_posts()) : $people_query->the_post();
?>
        <!-- people listing  -->
        <div class="written_by_box mb-4">
            <div class="blog_users d-flex gap-2 flex-wrap flex-md-nowrap align-items-center justify-content-between mb-4">
                <div class="d-flex align-items-center gap-2">
                    <div>
                    <?php if (has_post_thumbnail()) : ?>
                                <img src="<?php the_post_thumbnail_url('thumbnail'); ?>" class="people_avatar" alt="<?php the_title(); ?>">
                            <?php else : ?>
                                <img src="<?php echo get_template_directory_uri(); ?>/images/default-avatar.jpg" class="people_avatar" alt="<?php the_title(); ?>">
                            <?php endif; ?>
                  </div>
                    <div>
                        <h5 class="fw-bold mb-0 "><?php the_title(); ?></h5>
                        <p class="mb-0"><?php echo get_field('desgination'); ?></p>
                    </div>
                </div>
            </div>

            <div>
                <?php the_excerpt(); ?>
                <a href="<?php echo get_field('site_url'); ?>" class="btn btn-primary">Visit site</a>
            </div>
        </div>
        <!-- people listing end -->
<?php
    endwhile;
    wp_reset_postdata(); // Reset the query
else :
    // No posts found
endif;
?>

<div class="hypeloot_casino mb-4 text-center">
    <div class="row">
        <div class="col-md-12">
            <a href="" class="white">
                <div class="d-inline-block d-md-flex text-center justify-content-center align-items-center gap-2 ">
                    <div><img src="<?php echo get_template_directory_uri(); ?>/images/hypeloot-full.png" class="img-fluid hype_loots " alt=""></div>
                    <div>
                        <p class="m-0 ">Hypeloot The Fastest Growing Crypto Casino! 💰</p>
                    </div>
                </div>
            </a>
        </div>
    </div>
</div>
</div>

               <div class="col-md-3">
                  <div class="join_crypto mb-4">
                     <h4 class="white fw-bold mb-3">
                        Join the crypto
                        adventure
                     </h4>
                     <ul class="social_media mb-3 ms-0 p-0">
                        <li class="d-inline list-unstyled  pe-2"><a class="white fs-20" href=""><i class="bi bi-reddit"></i></a></li>
                        <li class="d-inline list-unstyled  pe-2"><a class="white fs-20" href=""><i class="bi bi-telegram"></i></a></li>
                        <li class="d-inline list-unstyled  pe-2"><a class="white fs-20" href=""><i class="bi bi-twitter"></i></a></li>
                        <li class="d-inline list-unstyled  pe-2"><a class="white fs-20" href=""><i class="bi bi-instagram"></i></a></li>
                     </ul>
                  </div>
                  <div class="hype_loot_box">
                     <div class="hype_loot_image text-center  ">
                        <img src="images/hypeloot.gif" class="img-fluid " alt="">
                     </div>
                     <div class="hype_loot_content">
                        <p class="mb-0 fs-18 fw-bold ">Join the First-Ever AI Powered Casino and Get Profits Daily! 💰</p>
                     </div>
                  </div>
               </div>
            </div>
         </div>
      </section>
      <!-- hot section end -->
      <script>
    document.addEventListener('DOMContentLoaded', function () {
        const searchInput = document.getElementById('search-input');
        const peopleList = document.getElementById('people-list');

        searchInput.addEventListener('input', function () {
            const searchValue = this.value.toLowerCase();
            const peopleItems = peopleList.getElementsByClassName('written_by_box');

            Array.from(peopleItems).forEach(function (item) {
                const personName = item.querySelector('h5').innerText.toLowerCase();
                if (personName.includes(searchValue)) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
        });
    });
</script>
 <?php get_footer() ;?>