<?php
/**
 * Custom CSS and JS
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * CustomCSSandJS_RoleManager
 */
class CustomCSSandJS_RoleManager {

	var $default;

	/**
	 * Constructor
	 */
	public function __construct() {

		// Set the default variable
		$this->default = array(
			'administrator'     => 'full',
			'editor'            => 'none',
			'author'            => 'none',
			'contributor'       => 'none',
			'user_ids_full'     => '',
			'user_ids_partial'  => '',
			'user_ids_none'     => '',
			'usernames_full'    => '',
			'usernames_partial' => '',
			'usernames_none'    => '',
		);

		add_action( 'ccj_settings_form', array( $this, 'ccj_roles_form' ), 12 );
		add_filter( 'ccj_settings_default', array( $this, 'ccj_roles_default' ) );
		add_filter( 'ccj_settings_save', array( $this, 'ccj_roles_save' ) );
	}


	/**
	 * Add the default for the theme
	 */
	function ccj_roles_default( $defaults ) {
		return array_merge( $defaults, array( 'ccj_roles' => $this->default ) );
	}


	/**
	 * Add the 'ccj_roles' value to the $_POST for the Settings page
	 */
	function ccj_roles_save( $data ) {

		$rights  = array( 'full', 'partial', 'none' );
		$default = $this->default;

		// Gather the rights for editor, author and contributor
		foreach ( array( 'editor', 'author', 'contributor' ) as $_role ) {
			if ( ! isset( $_POST[ 'ccj_role-' . $_role ] ) ) {
				continue;
			}
			$this_right = $_POST[ 'ccj_role-' . $_role ];
			if ( ! in_array( $this_right, $rights ) ) {
				continue;
			}

			$role = get_role( $_role );
			$role->add_cap( 'publish_ccj', 'edit_ccj', 'edit_others_ccj', 'read_private_ccj' );

			$default[ $_role ] = $this_right;
		}

		// Find all the users for the WP installation
		$get_users = get_users();
		$all_users = array();
		foreach ( $get_users as $_user ) {
			$all_users[ $_user->data->user_login ] = $_user->data->ID;
		}

		// Gather the restricted user ids
		foreach ( array( 'usernames_full', 'usernames_partial', 'usernames_none' ) as $_part ) {
			if ( ! isset( $_POST[ 'ccj_role-' . $_part ] ) ) {
				continue;
			}
			$usernames = $_POST[ 'ccj_role-' . $_part ];

			// Get an array of usernames
			if ( strpos( $usernames, ',' ) ) {
				$usernames = explode( ',', $usernames );
				$usernames = array_unique( array_map( 'trim', $usernames ) );
			} else {
				$usernames = array( trim( $usernames ) );
			}

			// Find the ids
			$ids = array();
			foreach ( $usernames as $_username ) {
				if ( isset( $all_users[ $_username ] ) ) {
					$ids[] = $all_users[ $_username ];
				}
			}

			// Set the data
			$default[ $_part ] = implode( ', ', $usernames );
			$default[ str_replace( 'usernames', 'user_ids', $_part ) ] = $ids;
		}

		return array_merge( $data, array( 'ccj_roles' => $default ) );
	}


	/**
	 * Form for "Role Management" field
	 */
	function ccj_roles_form() {

		// Get the setting
		$settings = get_option( 'ccj_settings' );
		$data     = isset( $settings['ccj_roles'] ) ? $settings['ccj_roles'] : $this->default;

		$current_user     = wp_get_current_user();
		$current_username = isset( $current_user->data->user_login ) ? $current_user->data->user_login : '';

		?>
		<tr>
		<th scope="row"><label for="ccj_role_manager">Manage Rights</label></th>
		<td>
			<?php $this->role_manager_table( $data ); ?>
			<?php $this->role_manager_table_ids( $data ); ?>
			<input type="hidden" name="ccj_role-current_user" id="ccj_role-current_user" value="<?php echo $current_username; ?>" />
		</td>
		</tr>
		<?php
	}

	function role_manager_table( $data ) {
		$permissions = array( 'full', 'partial', 'none' );
		$wp_roles    = array(
			'administrator' => 'Administrator',
			'editor'        => 'Editor',
			'author'        => 'Author',
			'contributor'   => 'Contributor',
		);
		$help        = array(
			'full-rights'    => 'The right to manage/modify codes, as well as the right to manage the Settings page',
			'partial-rights' => 'The right to manage/modify codes',
			'none-rights'    => 'The user will have no access to the plugin\'s pages',
		);
		?>
		<table class="wp-list-table role-permissions">
			<thead>
			<tr>
				<th class="manage-column"> &nbsp; </th>
				<th class="manage-column">Full rights <?php echo $this->help_icon( $help['full-rights'] ); ?></th>
				<th>Partial rights <?php echo $this->help_icon( $help['partial-rights'] ); ?></th>
				<th>No rights <?php echo $this->help_icon( $help['none-rights'] ); ?></th>
			</tr>
			</thead>
		<?php
		$counter = 0;
		foreach ( $wp_roles as $_role => $_title ) {
			$classes  = '';
			$disabled = '';
			$help     = '';
			if ( $_role == 'administrator' ) {
				$classes .= ' disabled';
				$disabled = ' disabled="disabled"';
				$help     = $this->help_icon( 'The Administrator always has full rights. You can remove the rights of a particular administrator by using the \'Usernames with partial rights\' input field below.' );
			}
			$alternate = ( $counter % 2 == 0 ) ? $classes .= ' alternate' : '';
			if ( ! empty( $classes ) ) {
				$classes = ' class="' . $classes . '"';
			}

			echo '<tr' . $classes . '>' . PHP_EOL;
			echo "\t" . '<td class="desc">' . $_title . ' ' . $help . '</td>' . PHP_EOL;
			foreach ( $permissions as $_perm ) {
				$name    = 'ccj_role-' . $_role;
				$checked = '';
				if ( $data[ $_role ] == $_perm ) {
					$checked = ' checked="checked"';
				}
				echo "\t" . '<td><input type="radio" name="' . $name . '" value="' . $_perm . '" ' . $checked . $disabled . ' /></td>' . PHP_EOL;
			}
			echo '</tr>' . PHP_EOL;
			$counter ++;
		}
		echo '</table>';
	}

	function help_icon( $text ) {
		return '<span class="dashicons dashicons-editor-help" rel="tipsy" title="' . $text . '"></span>';
	}

	function role_manager_table_ids( $data ) {
		$permissions = array(
			'full'    => 'full',
			'partial' => 'partial',
			'none'    => 'no',
		);

		$help = array(
			'full'    => 'A comma separated list of usernames of users with full rights. This will overwrite the settings above',
			'partial' => 'A comma separated list of usernames of users with partial rights. This will overwrite the settings',
			'none'    => 'A comma separated list of usernames of users with no rights. This will overwrite the settings above',
		);
		?>
		<table class="wp-list-table fixed role-permissions2">
		<?php
		$counter = 0;
		foreach ( $permissions as $_perm => $_title ) {
			$alternate = ( $counter % 2 == 0 ) ? ' class="alternate"' : '';
			$name      = 'ccj_role-usernames_' . $_perm;
			$value     = $data[ 'usernames_' . $_perm ];
			echo '<tr' . $alternate . '>' . PHP_EOL;
			echo "\t" . '<td class="desc">Usernames with ' . $_title . ' rights ' . $this->help_icon( $help[ $_perm ] ) . ' </td>' . PHP_EOL;
			echo "\t" . '<td><input type="text" name="' . $name . '" id="' . $name . '" value="' . $value . '" class="regular-text" /></td>' . PHP_EOL;

			echo '</tr>' . PHP_EOL;
			$counter ++;
		}
		echo '</table>';

	}



	/**
	 * Add the default for the theme
	 */
	function ccj_settings_default( $defaults ) {
		$defaults['ccj_role_manager'] = $this->default;
	}



}

return new CustomCSSandJS_RoleManager();
