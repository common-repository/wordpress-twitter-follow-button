<?php 
/*
	Plugin Name: Wordpress Twitter Follow Button
	Plugin URI: http://andreapernici.com/wordpress/twitter-follow-button/
	Description: Add Twitter Follow Button to Wordpress Posts.
	Version: 1.0.3
	Author: Andrea Pernici
	Author URI: http://www.andreapernici.com/
	
	Copyright 2009 Andrea Pernici (andreapernici@gmail.com)
	
	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation; either version 2 of the License, or
	(at your option) any later version.
	
	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.
	
	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

	*/

define( 'TWB_VERSION', '1.0.3' );

$pluginurl = plugin_dir_url(__FILE__);
if ( preg_match( '/^https/', $pluginurl ) && !preg_match( '/^https/', get_bloginfo('url') ) )
	$pluginurl = preg_replace( '/^https/', 'http', $pluginurl );
define( 'TWB__FRONT_URL', $pluginurl );

define( 'TWB__URL', plugin_dir_url(__FILE__) );
define( 'TWB__PATH', plugin_dir_path(__FILE__) );
define( 'TWB__BASENAME', plugin_basename( __FILE__ ) );

if (!class_exists("AndreaTwitterFollow")) {

	class AndreaTwitterFollow {
		/**
		 * Class Constructor
		 */
		function AndreaTwitterFollow(){
		
		}
		
		/**
		 * Enabled the AndreaTwitterFollow plugin with registering all required hooks
		 */
		function Enable() {

			add_action('admin_menu', array("AndreaTwitterFollow",'TwitterFollowMenu'));
			//add_action("wp_insert_post",array("AndreaFacebookSend","SetFacebookSendCode"));
			$options_after = get_option( 'tw_follow_after_content' );
			$options_before = get_option( 'tw_follow_before_title' );
			if ($options_after) {
				add_filter("the_content", array("AndreaTwitterFollow","SetTwitterFollowCodeFilter"));
			}
			if ($options_before) {
				add_action("loop_start",array("AndreaTwitterFollow","SetTwitterFollowCode"));
			}	
			
		}
		
		/**
		 * Set the Admin editor to set options
		 */
		 
		function SetAdminConfiguration() {
			add_action('admin_menu', array("AndreaTwitterFollow",'TwitterFollowMenu'));
			return true;			
		}
		
		function TwitterFollowMenu() {
			add_options_page('Twitter Follow Options', 'Twitter Follow Button', 'manage_options', 'tw-follow-options', array("AndreaTwitterFollow",'TwitterFollowOptions'));
		}
		
		function TwitterFollowOptions() {
			if (!current_user_can('manage_options'))  {
				wp_die( __('You do not have sufficient permissions to access this page.') );
			}
			
		    // variables for the field and option names 
		    $tw_follow_before_title = 'tw_follow_before_title';
		    $tw_follow_after_content = 'tw_follow_after_content';
		    $tw_follow_twitter_name = 'tw_follow_twitter_name';
		    
		    $hidden_field_name = 'mt_submit_hidden';
		    $data_field_name_before = 'tw_follow_before_title';
		    $data_field_name_after = 'tw_follow_after_content';
		    $data_field_twitter_name = 'tw_follow_twitter_name';
		
		    // Read in existing option value from database
		    $opt_val_before = get_option( $tw_follow_before_title );
		    $opt_val_after = get_option( $tw_follow_after_content );
		    $opt_val_twitter_name = get_option( $tw_follow_twitter_name );
		    
		    // See if the user has posted us some information
		    // If they did, this hidden field will be set to 'Y'
		    if( isset($_POST[ $hidden_field_name ]) && $_POST[ $hidden_field_name ] == 'Y' ) {
		        // Read their posted value
		        $opt_val_before = $_POST[ $data_field_name_before ];
		    	$opt_val_after = $_POST[ $data_field_name_after ];
		    	$opt_val_twitter_name = $_POST[ $data_field_twitter_name ];
		
		        // Save the posted value in the database
		        update_option( $tw_follow_before_title, $opt_val_before );
		        update_option( $tw_follow_after_content, $opt_val_after );
		        update_option( $tw_follow_twitter_name, $opt_val_twitter_name );
		
		        // Put an settings updated message on the screen
		
		?>
		<div class="updated"><p><strong><?php _e('settings saved.', 'menu-tw-follow' ); ?></strong></p></div>
		<?php
		
		    }
		    // Now display the settings editing screen
		    echo '<div class="wrap">';
		    // header
		    echo "<h2>" . __( 'Twitter Follow Button Options', 'menu-tw-follow' ) . "</h2>";
		    // settings form
		    
		    ?>
		
		<form name="form1" method="post" action="">
		<input type="hidden" name="<?php echo $hidden_field_name; ?>" value="Y">
		
		<?php $options_after = get_option( 'tw_follow_before_title' ); ?>
		<p><?php _e("Show Before Title:", 'menu-tw-follow' ); ?> 
		<input type="checkbox" name="tw_follow_before_title" value="1"<?php checked( 1 == $options_after ); ?> />
		
		<?php $options_before = get_option( 'tw_follow_after_content' ); ?>
		<p><?php _e("Show After Content:", 'menu-tw-follow' ); ?> 
		<input type="checkbox" name="tw_follow_after_content" value="1"<?php checked( 1 == $options_before ); ?> />
		
		<?php $twitter_username = get_option( 'tw_follow_twitter_name' ); ?>
		<p><?php _e("Your Twitter Username:", 'menu-tw-follow' ); ?> 
		<input type="text" name="tw_follow_twitter_name" value="<?php echo $twitter_username; ?>" />

		<p class="submit">
		<input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e('Save Changes') ?>" />
		</p>
		
		</form>
		<?php echo "<h2>" . __( 'Put Function in Your Theme', 'menu-tw-follow' ) . "</h2>"; ?>
		<p>If you want to put the box anywhere in your theme or you have problem showing the box simply use this function:</p>
		<p>if (function_exists('andrea_tw_follow')) { andrea_tw_follow(); }</p>
		</div>
		
		<?php

		}
		
		/**
		 * Setup Iframe Buttons for actions
		 */
		
		function SetTwitterFollowCode() {
			
			$twitter_username = get_option( 'tw_follow_twitter_name' );
			
			$button = '<div id="tw_follow_like">';
			$button.= '<iframe allowtransparency="true" frameborder="0" scrolling="no" src="http://platform.twitter.com/widgets/follow_button.html?screen_name='.$twitter_username.'&show_count=false&lang=it" style="width:300px; height:20px;"></iframe>';
			//$button.= '<iframe src="http://www.facebook.com/plugins/like.php?href='.urlencode($permalink).'&amp;send=true&amp;layout=standard&amp;width=450&amp;show_faces=true&amp;action=like&amp;colorscheme=light&amp;font&amp;height=80" scrolling="no" frameborder="0" style="border:none; overflow:hidden; width:450px; height:80px;" allowTransparency="true"></iframe>';
			$button.= '</div>';
			
			echo $button;
		}		
		
		/**
		 * Setup Iframe Buttons for Filter
		 */
		
		function SetTwitterFollowCodeFilter($content) {
			
			$twitter_username = get_option( 'tw_follow_twitter_name' );
			
			$content.= '<div id="fb_send_like">';
			$button.= '<iframe allowtransparency="true" frameborder="0" scrolling="no" src="http://platform.twitter.com/widgets/follow_button.html?screen_name='.$twitter_username.'&show_count=false&lang=it" style="width:300px; height:20px;"></iframe>';
			//$content.= '<iframe src="http://www.facebook.com/plugins/like.php?href='.urlencode($permalink).'&amp;send=true&amp;layout=standard&amp;width=450&amp;show_faces=true&amp;action=like&amp;colorscheme=light&amp;font&amp;height=80" scrolling="no" frameborder="0" style="border:none; overflow:hidden; width:450px; height:80px;" allowTransparency="true"></iframe>';
			$content.= '</div>';
			
			return $content;
		}	
		
		/**
		 * Returns the plugin version
		 *
		 * Uses the WP API to get the meta data from the top of this file (comment)
		 *
		 * @return string The version like 1.0.1
		 */
		function GetVersion() {
			if(!function_exists('get_plugin_data')) {
				if(file_exists(ABSPATH . 'wp-admin/includes/plugin.php')) require_once(ABSPATH . 'wp-admin/includes/plugin.php'); //2.3+
				else if(file_exists(ABSPATH . 'wp-admin/admin-functions.php')) require_once(ABSPATH . 'wp-admin/admin-functions.php'); //2.1
				else return "0.ERROR";
			}
			$data = get_plugin_data(__FILE__);
			return $data['Version'];
		}
	
	}
}

/*
 * Plugin activation
 */
 
if (class_exists("AndreaTwitterFollow")) {
	$afs = new AndreaTwitterFollow();
}


if (isset($afs)) {
	add_action("init",array("AndreaTwitterFollow","Enable"),1000,0);
	//add_action("wp_insert_post",array("AndreaFacebookSend","SetFacebookSendCode"));
}

if (!function_exists('andrea_tw_follow')) {
	function andrea_tw_follow() {
		$tw_follow = new AndreaTwitterFollow();
		return $tw_follow->SetTwitterFollowCode();
	}	
}
