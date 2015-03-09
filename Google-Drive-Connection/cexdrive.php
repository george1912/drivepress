<?php
/*
Plugin Name: Google Drive Folder Display
Plugin URI: 
Description: A plugin to authenticate with Google Drive in order to display files and folders.
Author: Xin Wang
Version: 1.2
Author URI:
*/

// The Admin pages
require "cexadmin.php";


/**
 * Creates the menu item on the dashborad.
 */
add_action( 'admin_menu', 'create_drive_menu' );
        function create_drive_menu() {
            // create menu item on the dashoboard
                add_menu_page( 
                'Google Drive Display', 
                'Google Drive Display', 
                'manage_options', 
                __FILE__, 
                'cexdrive_settings_page',
                plugins_url( '/image/google_drive_icon.png', __FILE__ )
        );
    }


// when plugin is activated, create a new data table in database
register_activation_hook(__FILE__, 'plugin_activation_cretable');
function plugin_activation_cretable() {
    global $wpdb;
    $table_name = $wpdb->prefix."Google_Drive_Account";

    /*
     * We'll set the default character set and collation for this table.
     * If we don't do this, some characters could end up being converted 
     * to just ?'s when saved in our table.
     */
    $charset_collate = '';

    if (!empty($wpdb->charset)) {
      $charset_collate = "DEFAULT CHARACTER SET {$wpdb->charset}";
    }

    if (!empty( $wpdb->collate)) {
      $charset_collate .= " COLLATE {$wpdb->collate}";
    }

    $sql = "CREATE TABLE " . $table_name . " (
        id bigint(20) NOT NULL,
        email varchar(100) NOT NULL,
        access_token varchar(100) NOT NULL,
        token_type varchar(100) NOT NULL,
        refresh_token varchar(100),
        expires_in int(11),
        created int (11),
        UNIQUE KEY id (id)
    ) $charset_collate;";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );
}


/**
 * Parses the registered options.
 * 
 * @return array|bool An array of options, or FALSE if not set up.
 */
function cexdrive_get_config()
{

	global $wpdb;
	$table_name = $wpdb->prefix."Google_Drive_Account";
	$ID=wp_get_current_user()->ID;
	$sql='select * from '. $table_name . " where id=" .$ID;
	$data = $wpdb->get_row($sql);
	if (empty($data))
		return FALSE;
	else
		return $data;
}

function cexdrive_update_config($token)
{
	global $wpdb;
    $table_name = $wpdb->prefix."Google_Drive_Account";
    $ID=wp_get_current_user()->ID;
    //get current account records of user
    $current=cexdrive_get_config();
    if ($current)
    {
		$wpdb->update( 
			$table_name, 
			array( 
				'access_token' =>$token['access_token'] ,	// string
				'expires_in' => $token['expires_in'],	// integer (number) 
				'created' => $token['created']
			), 
			array( 'id' => $ID ), 
			array( 
				'%s',	// value1
				'%d',
				'%d'	// value2
			), 
			array( '%d' ) 
		);

    }
    //get the most update records
    $current=cexdrive_get_config();

    return $current;
}

/**
 * Sets a config option.
 * 
 * @param array An array that will be merged in the existing options.
 * @param bool Whether to merge the data or override.
 * @return array The new data array.
 */
function cexdrive_insert_config($data)
{

    global $wpdb;
    $table_name = $wpdb->prefix."Google_Drive_Account";
    //check if update or insert
    $ID=wp_get_current_user()->ID;
    if (! cexdrive_get_config())
    {
    	$rows_affected= $wpdb->insert($table_name,array( 'id' => $ID, 
								    	'email' => $data['user'], 
								    	'access_token' => $data['token']['access_token'],
								    	'token_type' =>$data['token']['token_type'],
                                        'refresh_token' => $data['token']['refresh_token'],
								    	'expires_in' => $data['token']['expires_in'],
								    	'created' => $data['token']['created'] ));

    }
    //get the most update records
    $current=cexdrive_get_config();

    return $current;
}
 

/**
 * delete a config option.
 * 
 * @param array An array that will be merged in the existing options.
 * @param bool Whether to merge the data or override.
 * @return array The new data array.
 */
function cexdrive_del_config(){
	global $wpdb;
	$ID=wp_get_current_user()->ID;
	$table_name = $wpdb->prefix."Google_Drive_Account";
	$wpdb->query(" DELETE FROM ".$table_name." WHERE id = ".$ID);
	//$wpdb->delete( $table_name, array( 'id' => 1 ), array( '%d' ) );
}

function get_clean_doc($contents) 
{

    $contents = apply_filters( 'pre_docs_to_wp_strip', $contents );
    //New domDocument and xPath to get the content
    $dom= new DOMDocument();
    $dom->loadHTML( $contents[ 'contents' ] );
    $xpath = new DOMXPath($dom);
    
    //Strip away the headers
    $body = $xpath->query('/html/body');

    
    //This is our dirty HTML
    $dirty_html = $dom->saveXml($body->item(0));
    
    $dirty_html = apply_filters( 'pre_docs_to_wp_purify', $dirty_html );
    
    //Run that through the purifier
    //$clean_html = $purifier->purify( $dirty_html );
    return $dirty_html;
}


function publish_to_WordPress ( $title, $content, $custom_fields = false ) {
            //If the username in gdocs matches the username in WordPress, it will automatically apply the correct username            
            $post_array = array(
                'post_title' => $title,
                'post_content' => $content,
                'custom_fields' => $custom_fields,
                'post_author' => wp_get_current_user()->display_name
            );
                   
            //If you want all posts to be auto-published, for example, you can add a filter here
            $post_array = apply_filters( 'pre_docs_to_wp_insert', $post_array );
            
            //Add
            $post_id = wp_insert_post( $post_array );           
    
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
    $client->setAccessType('offline');
    $client->setApprovalPrompt('force');
	return $client;

}
?>
