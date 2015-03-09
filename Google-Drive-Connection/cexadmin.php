<?php
/**
 * Displays the settings page.
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
                        
           var_dump($token);
            
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
                echo "retrive existing access_token";

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
             //create a new google service
                    //check if convert
            if( isset($_GET['DocId']) )
            {
                echo "start to convert";
                $fileId=$_GET['DocId'];
                //require_once dirname(__FILE__). '/google-api-php-client/Google_Client.php';
                try {
                        $file = $service->files->get($fileId);
                        print "Title: " . $file->getTitle();
                        print "Description: " . $file->getDescription();
                        print "MIME type: " . $file->getMimeType();
                      } 
                catch (Exception $e) {
                        print "An error occurred: " . $e->getMessage();
                      }

                $downloadUrl = $file->getExportLinks()['text/html'];
                echo $downloadUrl;

                if ($downloadUrl) {
                    $request = new Google_Http_Request($downloadUrl, 'GET', null, null);
                    //$rest=new Google_Http_REST();
                    //$content=$rest->doExecute($client,$request);
                    $curl=new Google_IO_Curl($client);
                    $result=$curl->executeRequest($request);
                    $arr["filename"]= $file->getTitle();
                    $arr['contents']=$result[0];

                }           
                //var_dump($content);
                $message = "Converting Document {$_GET['DocId']} ";
                $clean_doc= get_clean_doc($arr);
                //echo $clean_doc;
                publish_to_WordPress($file->title,$clean_doc,false);
        
            } 
    }
    
       
?>
<div class="wrap">
	<h2>MET Google Drive</h2>
<?php if( isset($message) ): ?>
    <div class="updated"><p><?php echo $message; ?></p></div>
<?php elseif( isset($error) ): ?>
    <div class="error"><p><strong><?php echo $error; ?></strong></p></div>    
<?php endif; ?>	
<?php if($settings === FALSE or empty($settings) ): ?>
	<p>You have not set up any accounts yet.</p><a href="<?php echo $client->createAuthUrl(); ?>">Add a new account</a></p>
<?php else: ?>
	<h3>Current Accounts:</h3>
    <ol>
    <li><?php echo $settings->email; ?><a href="<?php echo $url; ?>&remove=<?php echo urlencode( $settings->email);?>">(Remove)</a></li>
    </ol>
    <h3> Here is the document file list on your drive</h3>
        <?php  $files_list = $service->files->listFiles(array())->getItems(); ?>
              <ol>
                 <?php foreach($files_list as $item):?> 
                    <?php if ($item['mimeType']== "application/vnd.google-apps.folder" or $item['mimeType']=='application/vnd.google-apps.document'): 
                     
                     echo '<li><img src="' . $item['iconLink'] . '" alt="Icon"> <a href="' . $item['embedLink'] . '" target=_self"'  . '">' . $item->title . '</a>'.'</li>';
                     echo "<form action=./cexdrive.php' method='get'>";
                     echo '<input type="hidden" name="DocId" value=$item["id"]>';
                     echo "<a href=".$url."&DocId=".$item['id'].">(convert)</a></li>";
                     echo "<input type='submit'>";?>
                     </form>
                 <?php endif; ?>
                 <?php endforeach; ?>
                 </ol>
                <br>
<?php endif; ?>
</div>
<?php
}
