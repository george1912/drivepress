/* s
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

UPLOADCARE_CONF = {
    original: (WP_GP_PARAMS.original === 'true'),
    ajaxurl: WP_GP_PARAMS.ajaxurl,
}

	function initPicker() {
         // alert ("inside picker") 
			var picker = new FilePicker({
                                
				apiKey: 'AIzaSyD2MXMpx_c6H38-wk3z097UbVPgg-FakaU',
				clientId: '32802320039-coq0vn95n2btdltq1c15rgj0kj6l45cd',
				buttonEl: document.getElementById('pick'),
                                
				onSelect: function(file) {
					console.log(file);
					//alert('Selected ' + file.id);
                                         downloadFile(file,function(Response){
                                             alert ("Error in downloading file")
                                         });
                                        //console.log(document.location.protocol);
                                        //console.log(document.location.host);
                                       // window.location = document.location.protocol+'//'+document.location.host+'/wp-content/plugins/newpicker/file.php?id=' + file.id;
                                    }                 
			});
  }
                
                
/**
 * Upload the file's content to wordpress.
 *
 * @param {Fileinfo/content} file Drive File contents..
 */                
                
function uploaddoc(fileInfo,title) {
  var data = {
    'action': 'google_picker_handle',
    'file_id': fileInfo,
    'title':title
  };
  //console.log(title);
  //window.send_to_editor(fileInfo);
  jQuery.post(ajaxurl, data, function(response) {
     //alert (response);
     window.location =response;
  });
}
      

      
/**
 * Download a file's content.
 *
 * @param {File} file Drive File instance.
 * @param {Function} callback Function to call when the request is complete.
 */
function downloadFile(file, callback) {
  file.downloadUrl = 'https://docs.google.com/feeds/download/documents/export/Export?id='+file.id+'&exportFormat=html' ;
  //console.log(file.title);
  //alert (file.downloadUrl); 
  if (file.downloadUrl) {
    //alert  ('inside downloadURL');  
    var accessToken = gapi.auth.getToken().access_token;
    var xhr = new XMLHttpRequest();
    xhr.open('GET', file.downloadUrl);
    xhr.setRequestHeader('Authorization', 'Bearer ' + accessToken);
    xhr.onload = function() {
      uploaddoc(xhr.responseText,file.title);
    };
    xhr.onerror = function() {
      callback(null);
    };
    xhr.send();
  } else {
    callback(null);
  }
}      
      