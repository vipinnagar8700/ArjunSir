<?php
$sidebar_menus = get_field('sidebar_menus');
$sidebar_banner = get_field('sidebar_small_banner');
$sidebar_small_banner_link = get_field('sidebar_small_banner_link');
?>
<div class="myStickyDiv">
                    <div class="mb-4 ">
                    <ul class="p-0 m-0 borderd_menu">
                             <?php foreach ($sidebar_menus as $key => $menu) { ?>
                                 
                              
                            <li class="list-unstyled "><a href="<?php echo $menu['link'];?>" class="borderd-nav"><?php echo $menu['title'];?></a></li>
                         <?php } ?>

                        </ul>
                    </div>
	<a href="<?php echo $sidebar_small_banner_link; ?>">
                    <div class="join_crypto mb-4" style="background-image: url(<?php echo $sidebar_banner; ?>);">
                       
                    </div>
		</a>
                </div>