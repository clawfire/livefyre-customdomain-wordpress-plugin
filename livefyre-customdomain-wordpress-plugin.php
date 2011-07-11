<?php
/*
Plugin Name: LiveFyre Custom Domain Wordpress Plugin (LF CDWP)
Plugin URI: https://github.com/clawfire/livefyre-customdomain-wordpress-plugin
Description: Sync your users with your Livefyre Remote Profile Managment.
Version: 0.1
Author: Thibault Milan
Author URI: http://thibaultmilan.com
License: MIT
*/

/*
Copyright (c) 2011 Thibault Milan

Permission is hereby granted, free of charge, to any person obtaining
a copy of this software and associated documentation files (the
"Software"), to deal in the Software without restriction, including
without limitation the rights to use, copy, modify, merge, publish,
distribute, sublicense, and/or sell copies of the Software, and to
permit persons to whom the Software is furnished to do so, subject to
the following conditions:

The above copyright notice and this permission notice shall be included
in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT.
IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY
CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT,
TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE
SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
-*/

/**
 * install hook
 */
function my_activation(){
	if(!wp_next_scheduled('my_daily_sync')){
		wp_schedule_event(time(),'daily','my_daily_sync');
	}
	add_option('domain_url');
	add_option('domain_api');
	add_option('domain_owner');
	add_option('site_id');
	add_option('api_secret');
}
function my_deactivation(){
	wp_clear_scheduled_hook('my_daily_sync');
	delete_option('domain_url');
	delete_option('domain_api');
	delete_option('domain_owner');
	delete_option('site_id');
	delete_option('api_secret');
}
register_activation_hook(__FILE__,'my_activation');
register_deactivation_hook(__FILE__,'my_deactivation');

/**
 * Sync wordpress users with livefyre rpm
 */
function daily_sync(){
	$users = get_users();
	foreach($users as $user){
		$user = get_userdata($user->ID);
		$user_json = array(
			'id' => $user->ID,
			'display_name' => $user->display_name,
			'nickname' => $user->user_login,
			'name' => array(
				'formatted' => $user->user_identity,
				'first' => $user->user_firstname,
				'middle' => '',
				'last' => $user->user_lastname
				),
			'email' => $user->user_email,
			'image_url' => get_gravatar($user->user_email),
			'profile_url' => '',
			'settings_url' => '',
			'websites' => array(
				$user->user_url
				),
			'location' => $user->location,
			'bio' => $user->user_description,
			'email_notification' => 'often',
			'moderator' => is_moderator($user->ID)
			);
		$ch = curl_init();
		$url = get_option('domain_url');
		
		json_encode($user_json);
	}
}

/**
 * Admin Panel
 */
// create custom plugin settings menu
add_action('admin_menu', 'lfwp_create_menu');

function lfwp_create_menu() {

	//create new top-level menu
	add_menu_page('Livefyre Settings', 'Livefyre Settings', 'administrator', __FILE__, 'lfwp_settings_page',plugins_url('/images/icon.png', __FILE__));
	add_submenu_page( 'options-general.php', 'Livefyre Settings', 'Livefyre Settings', 'administrator', __FILE__, 'lfwp_settings_page');

	//call register settings function
	add_action( 'admin_init', 'register_mysettings' );
}


function register_mysettings() {
	//register our settings
	register_setting( 'lfwp-settings-group', 'domain_url' );
	register_setting( 'lfwp-settings-group', 'domain_api' );
	register_setting( 'lfwp-settings-group', 'domain_owner' );
	register_setting( 'lfwp-settings-group', 'site_id' );
	register_setting( 'lfwp-settings-group', 'api_secret' );
}

function lfwp_settings_page() {
?>
<div class="wrap">
<h2>Livefyre for custom domain - Settings</h2>

<form method="post" action="options.php">
    <?php settings_fields( 'lfwp-settings-group' ); ?>
    <?php //do_settings( 'lfwp-settings-group' ); ?>
	<h3>1. Enter your account details (received by mail from livefyre)</h3>
		<p>
			<label>Domain URL</label><input type="text" name="domain_url" value="<?php echo get_option('domain_url'); ?>" />
		</p>

		<p>
        	<label>Domain API Key</label><input type="password" name="domain_api" value="<?php echo get_option('domain_api'); ?>" />
    	</p>

		<p>
        	<label>Domain Owner</label><input type="text" name="domain_owner" value="<?php echo get_option('domain_owner'); ?>" />
        </p>
		
		<p class="submit">
	    <input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
	    </p>
	<h3>2. Register your website</h3>
		<p>
        	<label>Site ID</label><input type="text" name="site_id" value="<?php echo get_option('site_id'); ?>" />
        </p>

		<p>
        	<label>API Secret</label><input type="password" name="api_secret" value="<?php echo get_option('api_secret'); ?>" />
        </p>
    	<p class="submit">
	    <input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
	    </p>
</form>
<h3>3. Test your connection</h3>
	<p id="livefyre_test"><button>Test</button></p>
	<p id="livefyre_forceSync"><button>Force User Sync</button></p>
</div>
<?php } ?>