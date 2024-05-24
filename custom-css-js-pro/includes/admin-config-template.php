<div class="wrap">
<h1><?php _e( 'Custom CSS & JS Settings', 'custom-css-js-pro' ); ?></h1>

<form method="post" action="options.php" novalidate="novalidate">
<input type='hidden' name='option_page' value='general' /><input type="hidden" name="action" value="update" /><input type="hidden" id="_wpnonce" name="_wpnonce" value="4a36cc13a5" /><input type="hidden" name="_wp_http_referer" value="/sb-test/wp-admin/options-general.php" />
<table class="form-table">
<tr>
<th scope="row"><label for="blogname"><?php _e( 'Site Title', 'custom-css-js-pro' ); ?></label></th>
<td><input name="blogname" type="text" id="blogname" value="My Website" class="regular-text" /></td>
</tr>
<tr>
<th scope="row"><?php _e( 'Membership', 'custom-css-js-pro' ); ?></th>
<td> <fieldset><legend class="screen-reader-text"><span><?php _e( 'Membership', 'custom-css-js-pro' ); ?></span></legend><label for="users_can_register">
<input name="users_can_register" type="checkbox" id="users_can_register" value="1"  />
<?php _e( 'Anyone can register', 'custom-css-js-pro' ); ?></label>
</fieldset></td>
</tr>
<tr>
</table>
<p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary" value="<?php _e( 'Save Changes', 'custom-css-js-pro' ); ?>"  /></p></form>
</div>
