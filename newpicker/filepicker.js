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
          alert ("inside picker") 
			var picker = new FilePicker({
                                
				apiKey: 'AIzaSyD2MXMpx_c6H38-wk3z097UbVPgg-FakaU',
				clientId: '32802320039-coq0vn95n2btdltq1c15rgj0kj6l45cd',
				buttonEl: document.getElementById('pick'),
                                
				onSelect: function(file) {
					console.log(file);
					alert('Selected ' + file.id);
                                        //window.location = "index.php?id=" + file.id;  
                                        uploaddoc(file);
                                        //console.log(document.location.protocol);
                                        //console.log(document.location.host);
                                       // window.location = document.location.protocol+'//'+document.location.host+'/wp-content/plugins/newpicker/file.php?id=' + file.id;
                                    }                 
			});
                        
                //alert("before post")
                //console.log(picker);
               /* $.ajax(document.location.protocol+'//'+document.location.host+'/wp-admin/admin-ajax.php', picker, function(response) {
			alert('Got this from the server: ' + response);
                        console.log(response);
		});*/    
           }
                
function uploaddoc(fileInfo) {
  var data = {
    'action': 'google_picker_handle',
    'file_id': fileInfo.id
  };
     alert(fileInfo.id);
  jQuery.post(ajaxurl, data, function(response) {
     alert (response);
  });
}
      