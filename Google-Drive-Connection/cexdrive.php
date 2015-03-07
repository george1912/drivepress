<?php
/*
Plugin Name: Google Drive Folder Display
Plugin URI: 
Description: A plugin to authenticate with Google Drive in order to display files and folders.
Author: Xin Wang
Version: 1.2
Author URI:
*/

//start a session
add_action('init', 'myStartSession', 1);
add_action('wp_logout', 'myEndSession');
add_action('wp_login', 'myEndSession');

function myStartSession() {
    if(!session_id()) {
        session_start();

    }
}

function myEndSession() {
    session_destroy ();
}
// The Admin pages
require "cexadmin.php";


/**
 * Creates the menu page.
 */
function cexdrive_create_menu() 
{
	// Creates a new menu under settings
	add_options_page('Google Drive Folder Display Settings', 'Google Drive Display', 'administrator', __FILE__, 'cexdrive_settings_page');
}

add_action('admin_menu', 'cexdrive_create_menu');



/**
 * Parses the registered options.
 * 
 * @return array|bool An array of options, or FALSE if not set up.
 */
function cexdrive_get_config()
{
	$settings = unserialize( get_option('cexdrive-config') );
	if(empty($settings) || count($settings) == 0)
	{
		return FALSE;
	}
	return $settings;
}


/**
 * Sets a config option.
 * 
 * @param array An array that will be merged in the existing options.
 * @param bool Whether to merge the data or override.
 * @return array The new data array.
 */
function cexdrive_set_config($data)
{
	/*
	$current = cexdrive_get_config();
	if($current !== FALSE && $merge === False)
	{
		$data = array_merge($current, $data);
	}
	*/
    update_option('cexdrive-config', serialize($data));
	return $data;
}
 

/**
 * Initializes the Google SDK.
 * 
 * @param string $url The URL to redirect to.
 * @return object The Google Client object.
 */
function cexdrive_load_lib($url)
{
	require_once dirname(__FILE__). '/google-api-php-client/src/Google/autoload.php';

	
	$client = new Google_Client();
	$client->setScopes(array('https://www.googleapis.com/auth/drive.readonly','https://www.googleapis.com/auth/drive'));
    $client->setRedirectUri($url);
	
	return $client;

}
?>
