<?php get_header(); ?>
  <section class="woocomerce_section py-5">
    <div class="container">
      <div class="row">
        <div class="col-md-12">
        <?php
    if (have_posts()) :
        while (have_posts()) :
            the_post(); ?>
            <div class="mb-4"><h2 class="global_heading"><?php the_title(); ?></h2></div>
            <?php  the_content();  
        endwhile;
 

    endif;
    ?>
        </div>
      </div>
    </div>
  </section>
<?php get_footer();
