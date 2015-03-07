<?php
/**
 * Displays the settings page.
 */
function cexdrive_settings_page() 
{
    
	$settings = cexdrive_get_config(); // Gets the current config
    //var_dump($settings);
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
                $settings=cexdrive_set_config($data);
                $message = "User {$user['emailAddress']} added successfully.";
            }            
        }
        catch(exception $e)
        {
            $error = 'The provided token is invalid!';
        }
    }

        // Removing a user
    if( isset($_GET['remove']) && $settings )
    {
        echo "start to remove";
        $name = $_GET['remove'];
        //unset( $settings[ $_GET['remove'] ] );
        $settings= cexdrive_set_config(array());
        $message = "User {$name} was successfully removed.";

    }  
    
        //check if token has been stored, if nothing is recorded, indicate first time login
    else if ($settings)
    { 
        //try{
            //var_dump($settings);
            //check if token has been stored
            if($client->isAccessTokenExpired()) {
                echo 'Try to refresh token'; // Debug
                //refresh token
                if(isset($settings['token']['access_token'])){
                //$client->refreshToken('ya29.LwE5MytwiDr3qco-YCbJr8EuJbuDmZsCtvquJKJgoQ--TRuK-f711nn2IPxpZM5-1Sx35eEzHPCbNw');
                $client->refreshToken($settings['token']['access_token']);
                $token_json=$client->getAccessToken();
                $token=json_decode($token_json,true);
                $settings['token']=$token;
                $settings= cexdrive_set_config($settings);
                //create a new google service
                $client->setAccessToken($token_json);
                $service = new Google_Service_Drive($client);
            }
            else
            {
                echo "token is not set";
            }
                //$about=$service->about->get(array());
                //$user=$about->getUser();
            }

            //the token is still valid
            else{

            }

        
       // }
       // catch(exception $e)
       // {
       //     $error = 'The provided token is expired!';
       // }

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
    <li><?php echo $settings['user']; ?><a href="<?php echo $url; ?>&remove=<?php echo urlencode( $settings['user']);?>">(Remove)</a></li>
    </ol>
    <h3> Here is the document file list on your drive</h3>
        <?php  $files_list = $service->files->listFiles(array())->getItems(); ?>
              <ol>
                <?php foreach($files_list as $item):?> 
                    <?php if ($item['mimeType']== "application/vnd.google-apps.folder" or $item['mimeType']=='application/vnd.google-apps.document') 
                     echo '<li><img src="' . $item['iconLink'] . '" alt="Icon"> <a href="' . $item['embedLink'] . '" target=_self"'  . '">' . $item->title . '</a>'.'</li>'; ?>
                 <?php endforeach; ?>
                 </ol>
                <br>
<?php endif; ?>
</div>
<?php
}
