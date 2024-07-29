<?php
 
get_header(); ?>

<div id="primary" class="content-area">
    <main id="main" class="site-main" role="main">

        <?php
        // Get the author's ID from the URL
        $author_name = get_query_var('author_name');
        $user = get_user_by('slug', $author_name);
        
        if ($user) {
            // Display author information
            echo '<h1>' . $user->display_name . '</h1>';
            echo '<p>' . $user->user_description . '</p>';
            echo '<img src="' . get_avatar_url($user->ID) . '" />';
            
            // You can display more information here
        } else {
            echo '<p>Author not found.</p>';
        }
        ?>

    </main><!-- #main -->
</div><!-- #primary -->

<?php get_footer(); ?>
