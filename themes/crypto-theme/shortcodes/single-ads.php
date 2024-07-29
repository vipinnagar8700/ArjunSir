<?php
function hype_loot_box_shortcode()
{
    ob_start();
    $adsTitle = get_field('title', 'option');
    $adsBanner = get_field('banner', 'option');
	$ads_link = get_field('ads_link', 'option');
    if ($adsBanner && $adsTitle) {
?>
    <a href="<?php echo $ads_link; ?>">
        <div class="hype_loot_box">
            <div class="hype_loot_image text-center">
                <img src="<?php echo $adsBanner; ?>" class="img-fluid" alt="<?php echo $adsTitle; ?>">
            </div>
            <div class="hype_loot_content">
                <p class="mb-0 fs-18 fw-bold"><?php echo $adsTitle; ?></p>
            </div>
        </div>
	</a>
<?php
    }
    return ob_get_clean();
}
add_shortcode('single-ads-box', 'hype_loot_box_shortcode');
