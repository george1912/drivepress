<?php
/*
  Plugin Name: Google Picker new
  Description: Interface to pick google drive files
  Author: shubha pai
  License: GPL V3
 */

//require_once 'cexdrive.php';
//$service = new Google_Service_Drive($client);
//add a button to the content editor, next to the media button
//this button will show a popup that contains inline content
add_action('media_buttons_context', 'add_my_custom_button');

add_action('init', 'googlepicker_plugin_init');

function googlepicker_plugin_init() {
    $apikey = 'AIzaSyD2MXMpx_c6H38-wk3z097UbVPgg-FakaU';
    $init = 'initPicker';
    $urltext = sprintf('https://www.google.com/jsapi?key=%s',$apikey,$apikey);        
    $urltextsecond = sprintf('https://apis.google.com/js/client.js?onload=%s',$init,$init);        
    //wp_register_script( 'filepicker', plugins_url( '/filepicker.js', __FILE__ ));   
    wp_register_script( 'filepicker', plugins_url( '/filepicker.js', __FILE__ ),array('jquery'));   
    wp_register_script( 'client', $urltextsecond );   
    wp_register_script( 'pickerscript', plugins_url( '/pickerscript.js', __FILE__ ) ); 
    wp_register_script( 'jsapi', $urltext ); 
    wp_enqueue_script( 'filepicker' );
    wp_enqueue_script( 'pickerscript' );
    wp_enqueue_script( 'client' );
    wp_enqueue_script( 'jsapi' );
    
     wp_localize_script('filepicker', 'WP_GP_PARAMS', _googlepicker_get_js_cfg());
}

function _googlepicker_get_js_cfg() {
    return array(
        //'public_key' => get_option('uploadcare_public'),
        'ajaxurl' => admin_url('admin-ajax.php'),
        //'tabs' => $tabs,
    );
}

/*
 * Get file from google drive
 */
function google_picker_attach($content) {
    
                //echo "start to convert";
                //var_dump($content);
                $clean_doc= get_clean_doc($content);
                // echo "Converting Document";
                $post_id=publish_to_WordPress($file->title,$clean_doc);  
                //$message = "Converting Document-- Title :{$file->getTitle()}, ID: $fileID} ";
                //echo $post_id;
                if ( $post_id ) {
                  $permalink = get_permalink( $post_id );
                   wp_redirect( $permalink );
                   exit;
                  }
                //wp_redirect( 'http://localhost/wp-admin/post.php?post='.$post_id.'&action=edit', 301 );
                //echo $clean_doc;
                //publish_to_WordPress($file->title,$clean_doc);

                //return get_permalink( $post->$post_id );
    }



add_action('wp_ajax_google_picker_handle', 'google_picker_handle');
function google_picker_handle() {
/*if (!file_exists('token.json')) {
	if (!$_GET['code']) {
		header('Location:' . $client->createAuthUrl(array('https://www.googleapis.com/auth/drive.file')));
		exit();
	}

}

file_put_contents('token.json', $client->authenticate());
$client->setAccessToken(file_get_contents('token.json'));
*/
//$service = new apiDriveService($client);    
//$service = new Google_Service_Drive($client);
    $file_id = $_POST['file_id'];
    //var_dump($file_id);
     
    $attachment_id = google_picker_attach($file_id);
    //echo "{\"attach_id\": $attachment_id}";     
    die;
}


//action to add a custom button to the content editor
function add_my_custom_button($context) {
  
  //path to my icon
  $img = plugins_url( 'media/logo.png' , __FILE__ );
  
  //the id of the container I want to show in the popup
  //$container_id = 'popup_container';
  
  //our popup's title
  $title = 'Google Drive Popup!';

  //append the icon
  $context .= "<a class='button' title='{$title}' 
    id = 'pick' style='padding-right: 2px; vertical-align: text-bottom;'
    href='javascript:initPicker();'>
    <img src='{$img}' />Google Drive</a>";
  
  return $context;
}
