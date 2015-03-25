<?php
/*
Plugin Name: Google Drive Folder Display
Plugin URI: 
Description: A plugin to authenticate with Google Drive in order to display files and folders, and eaisly move post from Google Docs to WordPress
Author: Xin Wang
Version: 1.2
Author URI:
*/

// The Admin pages
require "cexadmin.php";

/**
 * Creates the menu item on the dashborad.
 * Author Xin Wang
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



/**
 * when plugin is activated, create a new data table in database if it is not exists
 * Author Xin Wang
 */
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
    # The dbDelta function examines the current table structure, 
    # compares it to the desired table structure, 
    # and either adds or modifies the table as necessary,
    dbDelta( $sql );
}


/**
 * when plugin is deactivated, drop the data table in database if it is exists
 * Author Xin Wang
 */
register_deactivation_hook( __FILE__, 'pluginUninstall' );
function pluginUninstall() {

    global $wpdb;
    $table_name = $wpdb->prefix."Google_Drive_Account";

    //drop the table if it exists

    $wpdb->query("DROP TABLE IF EXISTS $table_name");
}

/**
 * retreive the registered account informarion of current user.
 * Author Xin Wang
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


/**
 * update the registered account informarion.
 * Author Xin Wang
 *
 * @param array An array holds information about newly refreshed tokens.
 *
 * @return array|bool An array of options, or FALSE if not set up.
 */
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
 * insert a new registration account information in the database table.
 * Author Xin Wang
 * 
 * @param array An array that holdes information about user and tokens.
 *
 * @return array The new registration information.
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
 * delete the registration account information for current user.
 * Author Xin Wang
 * 
 */
function cexdrive_del_config(){
	global $wpdb;
	$ID=wp_get_current_user()->ID;
	$table_name = $wpdb->prefix."Google_Drive_Account";
	$wpdb->query(" DELETE FROM ".$table_name." WHERE id = ".$ID);
	//$wpdb->delete( $table_name, array( 'id' => 1 ), array( '%d' ) );
}


/**
 * parse the raw html content of google doc.
 * 
 * @param array An array that holdes information about author and contents.
 * @return array An array that holdes information about author and clean html contents.
 */
function get_clean_doc($contents) 
{

    //Xin: the css in top of raw html file is not well formatted, so we need to first parse the head info
    //and extract the style info, modify the html with right style before further parsing
    //New domDocument and xPath to get the content
    $dom= new DOMDocument();
    $dom->loadHTML( $contents);
    $xpath = new DOMXPath($dom);
    
    //Strip the headers and body respectively
    $body = $xpath->query('/html/body');
    $header=$xpath->query('/html/head');

    //This is our dirty HTML
    $dirty_html = $dom->saveHTML($body->item(0));
    $dirty_head=$dom->saveHTML($header->item(0));

    $dirty_html=extract_styles($dirty_head,$dirty_html);

    
    //Run that through the purifier
    //$clean_html = $purifier->purify( $dirty_html );
    return $dirty_html;
}

/**
 * create a draft post in wordpress
 * 
 * @param string title of google doc
 * @param array content of google doc in html
 * @param array custom field
 */
function publish_to_WordPress ( $title, $content ) {
            //If the username in gdocs matches the username in WordPress, it will automatically apply the correct username            
            $post_array = array(
                'post_title' => $title,
                'post_content' => $content,
                'post_author' => wp_get_current_user()->display_name
            );
                   
            //If you want all posts to be auto-published, for example, you can add a filter here
            //$post_array = apply_filters( 'wp_insert_post_data ', $post_array );
            echo $post_array['post_author'];
            $post_array['post_content']=clean($post_array['post_content']);
            //Add
            $post_id = wp_insert_post( $post_array );
            return $post_id;      
    
}
/**
 * Initializes the Google Client.
 * Author Xin Wang
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

function extract_styles( $head,$contents ) {

        //PHP doesn't honor lazy matches very well, apparently, so add newlines
        $head = str_replace( '}', "}\r\n", $head );


        preg_match_all( '#.c(?P<digit>\d+){(.*?)font-weight:bold(.*?)}#', $head, $boldmatches );
        preg_match_all('#.c(?P<digit>\d+){(.*?)font-style:italic(.*?)}#', $head, $italicmatches);
        //xin: find the tag for python code
        preg_match_all('#.c(?P<digit>\d+){(.*?)background-color:\#ff0000(.*?)}#', $head, $pythonmatches);

        
        if( !empty( $boldmatches[ 'digit' ] ) ) {
        
            foreach( $boldmatches[ 'digit' ] as $boldclass ) {
                $contents = preg_replace( '#<span class="(.*?)c' . $boldclass . '(.*?)">(.*?)</span>#s', '<span class="$1c' . $boldclass . '$2"><strong>$3</strong></span>', $contents );
            }
        
        }

        
        
        if( !empty( $italicmatches[ 'digit' ] ) ) {
        
            foreach( $italicmatches[ 'digit' ] as $italicclass ) {
                $contents = preg_replace( '#<span class="(.*?)c' . $italicclass . '(.*?)">(.*?)</span>#s', '<span class="$1c' . $italicclass . '$2"><em>$3</em></span>', $contents );
            }
        
        }

        //xin: modify the raw html in order to distinguish python code from text, wrap in tag <code class="python">
        if( !empty( $pythonmatches[ 'digit' ] ) ) {
        
            foreach( $pythonmatches[ 'digit' ] as $pythonclass ) {
                $contents =preg_replace( '#<span class="c'.$pythonclass.'">(.*?)</span>#s', '<code class="python">'.'$1'.'</code>', $contents );
            }
        
        }

        
        return $contents;

}


function clean($post_content) {
    
    
        $post_content = str_replace( array( "\r\n", "\n\n", "\r\r", "\n\r" ), "\n", $post_content );
        $post_content = preg_replace('/<div(.*?)>/', '<div>', $post_content);
        
        $post_content = preg_replace('/<p(.*?)>/', '<p>', $post_content);
        $post_content = preg_replace('/<li(.*?)>/', '<li>', $post_content);
        //xin: fix the margin of bulleted list
        $post_content = preg_replace('/<ul(.*?)>/', '<ul style="padding-left: 36px";>', $post_content);
        //xin: fix the head link bug
        $post_content = preg_replace('/<h1(.*?)>(<a(.*?)>)?/', '<h1>', $post_content);

        $post_content = str_replace( '<div>','<p>',$post_content );
        $post_content = str_replace( '</div>', '</p>',$post_content );
        //adding <img> to keep the images info
        $post_content = strip_tags($post_content, '<strong><b><i><em><a><u><br><p><ol><ul><li><h1><h2><h3><h4><h5><h6><img><code><pre>' );
        $post_content = str_replace( '--','&mdash;',$post_content );
        $post_content = str_replace( '<br><br>','<p>',$post_content );
        $post_content = str_replace( '<br>&nbsp;&nbsp;&nbsp;', '\n\n', $post_content );
        $post_content = str_replace( '<br>&nbsp;&nbsp;&nbsp;','\n\n',$post_content);
        $post_content = str_replace( '<br><br>', '\n\n', $post_content );
        $post_content = trim( $post_content );
        $pees = explode( '<p>', $post_content );
        $trimmed = array();
        foreach( $pees as $p )
            $trimmed[] = trim( $p );
        $post_content = implode( '<p>', $trimmed );
        
        //xin: atumatically adding <pre> and </pre> for codes in backquotes 
        $post_content = str_replace('<p>`', '<p><pre>`', $post_content);
        $post_content = str_replace('`</p>', '`</pre></p>', $post_content);
        $post_content = preg_replace( "/<p><\/p>/", '', $post_content );
        //xin: fix empty line in code snippets
        $post_content=preg_replace('#</code></p><p><code class="([^>]*)">#', "\n", $post_content);
        $post_content=preg_replace('#</code><code class="([^>]*)">#', "", $post_content);

        //xin: highlight in crayon format
        $post_content = str_replace('<code class="python">', '<pre class="lang:python decode:true ">', $post_content);
        $post_content = str_replace('</code>', '</pre>', $post_content);


        return $post_content;
}
?>
