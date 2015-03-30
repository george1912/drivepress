<link rel="stylesheet" href="http://code.jquery.com/ui/1.10.3/themes/smoothness/jquery-ui.css" />
<script src="http://code.jquery.com/jquery-1.9.1.js"></script>
<script src="http://code.jquery.com/ui/1.10.3/jquery-ui.js"></script>

<?php
/**
 * Displays the google drive display page.
 * Author: Xin Wang
 */
function cexdrive_settings_page() 
{
    //adding in Picture
    //$image = "/image/convert.png";
    //$width = 150;
    //$height = 150;




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
                    $message = "an error occurred" . $e->getMessage()."\n please wait and refresh the page!";
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
                /*
                print "Title: " . $file->getTitle();
                print "Description: " . $file->getDescription();
                print "MIME type: " . $file->getMimeType();
                */
              } 
        catch (Exception $e) {
                print "An error occurred: " . $e->getMessage();
              }

        $downloadUrl = $file->getExportLinks()['text/html'];
        #echo $downloadUrl;

        if ($downloadUrl) {
            $request = new Google_Http_Request($downloadUrl, 'GET', null, null);
            //$rest=new Google_Http_REST();
            //$content=$rest->doExecute($client,$request);
            $curl=new Google_IO_Curl($client);
            $result=$curl->executeRequest($request);
            $content=$result[0];
        }           
        //var_dump($content);
        $message = "Converting Document-- Title :{$file->getTitle()}, ID: {$_GET['DocId']} ";
        //return cleaned css(array format) and body html
        $clean_doc= get_clean_doc($content);
        publish_to_WordPress($file->title,$clean_doc);

    } 
    
       
?>
<div class="wrap">
	<h2>Wordpress x Google Drive</h2>
    <h3>How To Use:</h3>
    <h4>Click on a Google Drive Document to open it in your Google Drive account to edit it.</h4>
    <h4>Click on the <img src='DrivePluginIcons/convert.png'> icon to convert your document into a Wordpress draft.</h4>







    <?php
    $array  = array('First');
    $array1  = array(122 => 'Files: gcu201@nyu.edu');
    $array2  = array('Wordpress Presentation one' , 'Appendix' , 'Files for Database', 'DWDCSVFiles','Is this weird or not?');

    ?>
    <div id="accordion">

        <?php foreach ($array  as $key=>$val) { ?>

            <h3><?php echo $val; ?></h3>

            <div style="min-height:200px;">

                <div id="accordion<?php echo $key; ?>" class="sub-container-menu">





                    <?php foreach($array1 as $key1=>$val1) { ?>

                        <h3><?php echo $val1; ?></h3>

                        <div style="min-height:200px;">

                            <div id="accordion<?php echo $key.'_'.$key1; ?>" class="sub-menu">

                                <?php foreach($array2 as $key2=>$val2) {  ?>

                                    <h3><?php echo $val2; ?></h3>

                                    <div>This is a test doc in side the wordpress presentation folder  </div>

                                <?php } ?>

                            </div>

                        </div>
                    <?php } ?>
                </div>



            </div>
        <?php } ?>
    </div>

    <script src="external/jquery/jquery.js"></script>
    <script src="jquery-ui.js"></script>
    <script>

        $( "#accordion" ).accordion();

        $(".sub-container-menu").each(function() {
            $( "#"+$(this).attr('id') ).accordion();
        });

        $(".sub-menu").each(function() {
            $( "#"+$(this).attr('id') ).accordion();
        });


    </script>













    <?php if( isset($message) ): ?>
    <div class="updated"><p><?php echo $message; ?></p></div>
<?php elseif( isset($error) ): ?>
    <div class="error"><p><strong><?php echo $error; ?></strong></p></div>    
<?php endif; ?>	
<?php if($settings === FALSE or empty($settings) ): ?>
	<p>You have not set up any accounts yet.</p><a href="<?php echo $client->createAuthUrl(); ?>">Add a new account</a></p>
<?php else: ?>
	<h3>Logged In Drive Account:</h3>
    <ol>
    <li><?php echo $settings->email; ?><a href="<?php echo $url; ?>&remove=<?php echo urlencode( $settings->email);?>">(Remove)</a></li>
    </ol>





    <h3> Here are the documents on your drive:</h3>
        <?php  $files_list = $service->files->listFiles(array())->getItems(); ?>
              <ol>
                 <?php foreach($files_list as $item):?> 
                    <?php if ($item['mimeType']== "application/vnd.google-apps.folder" or $item['mimeType']=='application/vnd.google-apps.document'): 
                        if (!empty($item['parents'])):
                             echo '<li><span><img src="' . $item['iconLink'] . '" alt="Icon"> <a href="' . $item['embedLink'] . '" target=_self"'  . '">' . $item->title . '</a>';
                             //echo "<form action=./cexdrive.php' method='get'>";
                             // echo '<input type="hidden" name="DocId" value=$item["id"]>';
                            //edited here /image/google_drive_icon.png
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
