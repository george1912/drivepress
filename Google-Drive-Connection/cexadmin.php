<?php
/**
 * Displays the google drive dispaly page.
 * Author: Xin Wang
 */
function cexdrive_settings_page() 
{
    
	$settings = cexdrive_get_config(); // Gets the current config
    //var_dump($settings->access_token);
    $url = admin_url( '/options-general.php?page=' . basename( dirname(__FILE__) ) . '/cexdrive.php' ); // Sets the current URL
    $client = cexdrive_load_lib($url); // Initialize Google Drive Access   
    // Account authorization handler
    if( isset($_GET['code']) )
    {
        try
        {
            
            // Authenticate
            $token_json = $client->authenticate($_GET['code']);
            $client->setAccessToken($token_json);
            $token=json_decode($token_json,true);
            $service = new Google_Service_Drive($client);
            $about=$service->about->get(array());
            $user=$about->getUser();
                        
           //var_dump($token);
            
            // Store credentials
            if($user['emailAddress'])
            {
            // Data to insert
                $data = array(
                     //'user' => wp_get_current_user()->ID,
                     'token' => $token,
                     'user'  => $user['emailAddress'],
            );
                //$settings = cexdrive_set_config(array($user['emailAddress'] => $data));
                $settings=cexdrive_insert_config($data);
                $message = "User {$user['emailAddress']} added successfully.";
            }            
        }
        catch(exception $e)
        {
            $error = 'The provided token is invalid!';
        }
    }

        // Removing a user
    else if( isset($_GET['remove']) && $settings )
    {
        echo "start to remove";
        $name = $_GET['remove'];
        cexdrive_del_config();
        $settings=FALSE;
        //$client->revokeToken();  
        $message = "User {$name} was successfully removed.";

    } 

    //check if token has been stored, if nothing is recorded, indicate first time login
    if ($settings and !empty($settings))
    { 
        //try{
            //var_dump($settings);
            //check if token has been stored
            //if($client->isAccessTokenExpired()) {
            $expires_in=$settings->expires_in;
            $created=$settings->created;

            if( ( $expires_in + $created - time() ) > 0){
                echo "retrieve existing access_token";
                    $token=array(
                        'access_token'=> $settings->access_token,
                        'expires_in' => $settings->expires_in,
                        'created' => $settings->created
                        );
                    $token_json=json_encode($token);
                    $client->setAccessToken($token_json);
                    $service = new Google_Service_Drive($client);
            } 
            else{
                echo 'Try to refresh token'; // Debug
                //refresh token
                try{
                    if(isset($settings->access_token)){
                        //$client->refreshToken($settings->access_token);
                        $client->refreshToken($settings->refresh_token);
                        $token_json=$client->getAccessToken();
                        $token=json_decode($token_json,true);
                        $settings= cexdrive_update_config($token); 
                        $client->setAccessToken($token_json);
                        $service = new Google_Service_Drive($client);
                    }
                    else{
                        echo "error: token is not set!";
                    }
                }
                catch(exception $e){
                    $error = "an error occurred" . $e->getMessage()."\n please wait and refresh the page!";
                }
            }
                
    }

    //check if user select a doc to convert
    if( isset($_GET['DocId']) )
    {
        echo "start to convert";
        $fileId=$_GET['DocId'];
        try {
                $file = $service->files->get($fileId);
              } 
        catch (Exception $e) {
                print "An error occurred: " . $e->getMessage();
              }

        $downloadUrl = $file->getExportLinks()['text/html'];
        #echo $downloadUrl;

        if ($downloadUrl) {
            $request = new Google_Http_Request($downloadUrl, 'GET', null, null);
            $httpRequest = $service->getClient()->getAuth()->authenticatedRequest($request);
            if ($httpRequest->getResponseHttpCode() == 200) {
                  $content= $httpRequest->getResponseBody();
                } else {
                  // An error occurred.
                $error = "an error occurred when fetching teh file".$fileId;
                }
              } else {
                // The file doesn't have any content stored on Drive.
                $error = "file doesn't exist".$fileId;
            }    
        //var_dump($content);
        //return cleaned css(array format) and body html
        $clean_doc= get_clean_doc($content);
        $post_id=publish_to_WordPress($file->title,$clean_doc);
        //$post_id=publish_to_WordPress(getTitle($clean_doc),$clean_doc);
        $message = "Converting Document Completed-- Title :{$file->getTitle()}, ID: {$_GET['DocId']} ";
        $redirect=true;

        /*
        $errors = FALSE;
 
        //  Some input field checking
        if ($errors == FALSE) {
            //  Use the wp redirect function
            //wp_redirect("admin.php?page=".basename(__FILE__)."list");
            $location=admin_url('post.php?post='.$post_id.'&action=edit');
            wp_redirect($location, 302);
            exit;
        } 
        else {
            //  If errros found output the header since we are staying on this page
            if (isset($_GET['noheader'])) {
                require_once(ABSPATH . 'wp-admin/admin-header.php');
            }
        }*/

    } 
    
       
?>
<div class="wrap">
	<h2>Drivepress</h2>
        <h4>Click on the document link to open your google doc in drive</h4>
        <h4>Click on the <img src='DrivePluginIcons/convert.png'> icon to convert your document into a Wordpress draft.</h4>

<?php if( isset($message) ): ?>
    <div class="updated"><p><?php echo $message; ?></p></div>
<?php elseif( isset($error) ): ?>
    <div class="error"><p><strong><?php echo $error; ?></strong></p></div>    
<?php endif; ?>	

<?php if( isset($redirect) ): ?>
    <a href= "<?php echo admin_url('post.php?post='.$post_id.'&action=edit'); ?>">Click to Redirect to Post Page</a></p>
<?php endif; ?> 

<?php if($settings === FALSE or empty($settings) ): ?>
	<p>You have not set up any accounts yet.</p><a href="<?php echo $client->createAuthUrl(); ?>">Add a new account</a></p>
<?php else: ?>
	<h3>Current Accounts:</h3>
    <ol>
    <li><?php echo $settings->email; ?><a href="<?php echo $url; ?>&remove=<?php echo urlencode( $settings->email);?>">(Remove)</a></li>
    </ol>
    <h3> Here is the document file list on your drive</h3>

        <?php $parameters['corpus']="DOMAIN";
        $parameters['maxResults']=1000;
         $files_list = $service->files->listFiles($parameters)->getItems(); ?>
              <ol>
                 <?php foreach($files_list as $item):?> 
                    <?php if ($item['mimeType']=='application/vnd.google-apps.document'): 
                        if (!empty($item['parents'])):
                             echo '<li><span><img src="' . $item['iconLink'] . '" alt="Icon"> <a href="' . $item['embedLink'] . '" target=_self"'  . '">' . $item->title . '</a>';
                             //echo "<form action=./cexdrive.php' method='get'>";
                             // echo '<input type="hidden" name="DocId" value=$item["id"]>';
                             //echo "<a href=".$url."&DocId=".$item['id'].">(convert)</a></span></li>";
                            echo "<a href=".$url."&DocId=".$item['id'].">   <img src='DrivePluginIcons/convert.png'>   </a></span></li>";?>
                    <?php endif; ?>
                 <?php endif; ?>
                 <?php endforeach; ?>
                 </ol>
                <br>
<?php endif; ?>
</div>
<?php
}
?>
