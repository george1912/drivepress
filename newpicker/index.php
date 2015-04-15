<?php
/*
  Plugin Name: Google Picker new
  Description: Interface to pick google drive files
  Author: shubha pai
  License: GPL V3
 */


//add a button to the content editor, next to the media button
add_action('media_buttons_context', 'add_my_custom_button');
// Enqueue the javascripts for google picker
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


function fetch_img_contents($url) {
     if ( function_exists("curl_init") ) {
         return curl_fetch_img_from_url($url);
    } elseif ( ini_get("allow_url_fopen") ) {
      return fopen_fetch_img_from_url($url);
    }
}
        
function curl_fetch_img_from_url($URL) {
    $c = curl_init();
    curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($c, CURLOPT_URL, $URL);
    $contents = curl_exec($c);
    curl_close($c);
    if ($contents) {
        return $contents;
    }
    return FALSE;
}
function fopen_fetch_img_from_url($url) {
    $image = file_get_contents($url, false, $context);
           if ($contents) {
        return $contents;
    }
    return FALSE;
}
    

function medialib_upload_form_url($imageUrl)
{

    $uploads = wp_upload_dir();
    $post_id = isset($_GET['post_id'])? (int) $_GET['post_id'] : 0;
    $filename = substr($imageUrl, (strrpos($imageUrl, '/'))+1);
    //Xin: add extension png
    $filename=$filename.".png";
    $ext = pathinfo( basename($imageUrl) , PATHINFO_EXTENSION);
    $wp_filetype = wp_check_filetype($filename, null );
    // Generate unique file name
    $filename = wp_unique_filename( $uploads['path'], $filename );
    // Move the file to the uploads dir
    $fullpathfilename = $uploads['path'] . "/$filename";
    //Xin new change
    $newUrl=$uploads['url']."/$filename";
    //echo "filename:$fullpathfilename<br>";    
    
    try {
            
    $image_string = fetch_img_contents($imageUrl);
    $fileSaved = file_put_contents($uploads['path'] . "/" . $filename, $image_string);
                
        $attachment = array(
             //'post_mime_type' => $wp_filetype['type'],
             //Xin: set type to be image/png
             'post_mime_type' =>'image/png',
             'post_title' => preg_replace('/\.[^.]+$/', '', $filename),
             'post_content' => '',
             'post_status' => 'inherit',
             'guid' => $uploads['url'] . "/" . $filename
            );
        $attach_id = wp_insert_attachment( $attachment, $fullpathfilename, $post_id );
        if ( !$attach_id ) {
            throw new Exception("Failed to save record into database.");
            }
            require_once(ABSPATH . "wp-admin" . '/includes/image.php');
            $attach_data = wp_generate_attachment_metadata( $attach_id, $fullpathfilename );
            wp_update_attachment_metadata( $attach_id,  $attach_data );
            
          } catch (Exception $e) {
        echo $e->getMessage();
            
            }

    return $newUrl;    
    
    }
/* shubha changes to get image links ends here */


function get_clean_dom_doc($contents) 
{
    //Xin: the css in top of raw html file is not well formatted, so we need to first parse the head info
    //and extract the style info, modify the html with right style before further parsing
    //New domDocument and xPath to get the content
    $dom= new DOMDocument();
    $dom->loadHTML( $contents);
 
    $xpath = new DOMXPath($dom);
    /* shubha changes to get links starts here */
    $images = $dom->getElementsByTagName('img');
    foreach($images as $img)
    {
        $url = $img->getAttribute('src');   
        $alt = $img->getAttribute('alt');
        $alt = strip_tags($alt);
        $url = stripslashes($url);
        $url = trim($url,'"');
        //echo $url;
            $finalurl = medialib_upload_form_url($url);
            $img->setAttribute ('src',$finalurl);
            $img->setAttribute ('alt',$alt);
    }
    
    $links = $dom->getElementsByTagName('a');
    foreach ($links as $link)
    {
        $hyperlink = $link->getAttribute('href');
        $hyperlink = stripslashes($hyperlink);
        $hyperlink = trim($hyperlink,'"');
        $link->setAttribute ('href',$hyperlink);
        echo $hyperlink;
    }   
    /* shubha changes to get links ends here */   
    
    
    //Strip the headers and body respectively
    $body = $xpath->query('/html/body');
    $header=$xpath->query('/html/head');
    //This is our dirty HTML
    $dirty_html = $dom->saveHTML($body->item(0));
    $dirty_head=$dom->saveHTML($header->item(0));
    $dirty_html=extract_post_styles($dirty_head,$dirty_html);
    
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
function publish_doc_to_WordPress ( $title, $content ) {
            //If the username in gdocs matches the username in WordPress, it will automatically apply the correct username            
            $post_array = array(
                'post_title' => $title,
                'post_content' => $content,
                'post_author' => wp_get_current_user()->display_name
            );
                   
            //If you want all posts to be auto-published, for example, you can add a filter here
            //$post_array = apply_filters( 'wp_insert_post_data ', $post_array );
            echo $post_array['post_author'];
            $post_array['post_content']=clean_post($post_array['post_content']);
            //Add
            $post_id = wp_insert_post( $post_array );
            return $post_id;      
    
}

function extract_post_styles( $head,$contents ) {
        //PHP doesn't honor lazy matches very well, apparently, so add newlines
        $head = str_replace( '}', "}\r\n", $head );
        preg_match_all( '#.c(?P<digit>\d+){(.*?)font-weight:bold(.*?)}#', $head, $boldmatches );
        preg_match_all('#.c(?P<digit>\d+){(.*?)font-style:italic(.*?)}#', $head, $italicmatches);
        //xin: find the tag for python code
        preg_match_all('#.c(?P<digit>\d+){(.*?)background-color:\#ff0000(.*?)}#', $head, $pythonmatches);
        preg_match_all('#.c(?P<digit>\d+){(.*?)background-color:\#ff9900(.*?)}#', $head, $phpmatches);
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
        //xin: modify the raw html in order to distinguish php code from text, wrap in tag <code class="php">
        if( !empty( $phpmatches[ 'digit' ] ) ) {
        
            foreach( $phpmatches[ 'digit' ] as $phpclass ) {
                $contents =preg_replace( '#<span class="c'.$phpclass.'">(.*?)</span>#s', '<code class="php">'.'$1'.'</code>', $contents );
            }
        
        }
        
        return $contents;
}
function clean_post($post_content) {
    
    
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
        //$post_content = str_replace('<code class="python">', '<pre class="lang:python decode:true ">', $post_content);
        //$post_content = str_replace('</code>', '</pre>', $post_content);
        $post_content = preg_replace('#<code class="(.*)">#', '<pre class="lang:'."$1".' decode:true ">', $post_content);
        $post_content = str_replace('</code>', '</pre>', $post_content);
        //Xin: convert the code tag ... ...
        $post_content=preg_replace('#</p><p>@@@end@@@</p>#s', '</pre>', $post_content);
        $post_content=preg_replace('#<p>@@@(.*?)@@@</p><p>#s', '<pre class="lang:'."$1".' decode:true ">', $post_content);
        $post_content=preg_replace( "#<\/p><p>#", "\n", $post_content );
        //Xin: embed youtube video link
        $post_content=preg_replace('#<p>http(.*?)www.youtube.com/watch.v=(.*?)</p>#',
        '<iframe width="560" height="315" src="https://www.youtube.com/embed/'.'$2'.'?rel=0&amp;controls=1&amp;showinfo=1" frameborder="1" allowfullscreen="allowfullscreen"></iframe>', $post_content);
        return $post_content;
}
/*
 * Get file from google drive
 */
function google_picker_attach($content) {
                echo "start to convert";
                //var_dump($content);
                $clean_doc= get_clean_dom_doc($content);
                // echo "Converting Document";
                $post_id=publish_doc_to_WordPress($file->title,$clean_doc);  
                //$message = "Converting Document-- Title :{$file->getTitle()}, ID: $fileID} ";
                echo $post_id;
//                if ( $post_id ) {
//                  $permalink = get_permalink( $post_id );
//                   wp_redirect( $permalink );
//                   exit;
//                  }
                //wp_redirect( 'http://localhost/wp-admin/post.php?post='.$post_id.'&action=edit', 301 );
                //echo $clean_doc;
                //publish_to_WordPress($file->title,$clean_doc);

                //return get_permalink( $post->$post_id );
    }



add_action('wp_ajax_google_picker_handle', 'google_picker_handle');
function google_picker_handle() {
    $file_id = $_POST['file_id'];
    //var_dump($file_id);
     
    google_picker_attach($file_id);
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
