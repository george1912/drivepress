/*
# Picker docs
# https://developers.google.com/picker/docs/
 
# Client docs
# https://developers.google.com/api-client-library/javascript/reference/referencedocs
 
# Authorization params
#
#          immediate: true, checks in the background.
#                     false, launches a sign-in pop-up.
#
#          authuser:  Which user to sign in as when logged in to multiple accounts. Indexed at 0.
#                     Setting this to false prevents google from picking the first.
#
#           user_id: ID of the user to sign in as. Paired best with authuser: false
#
#   approval_prompt: "auto", determine if the user needs to approve access to this app.
#                    "force", always display the approve access page. More importantly,
#                     this forces the sign-in page when we want to switch accounts.
#
#      max_auth_age: When set to 0, the user is forced to enter their password so the
#                    sign in page is always shown. This sign in page has a more obvious
#                    UI for switching accounts.
 */


GOOGLEPICKER_CONF = {
    original: (WP_GP_PARAMS.original === 'true'),
    ajaxurl: WP_GP_PARAMS.ajaxurl,
    Client_ID: WP_GP_PARAMS.Client_ID
};

//alert(ajaxurl);
//alert(WP_GP_PARAMS.Client_ID);
//alert ("nothing");

jQuery(document).ready(function($){
var e,
t,
n,
r,
i,
s,
u,
f,
l,
c,
h,
d,
v,
m,
y,
b,
w,
E,
S,
x,
T,
N,
C,
L;
n = WP_GP_PARAMS.Client_ID,
//alert(Client_ID),
                r = {
                    client_id: n,
                    scope: 'https://www.googleapis.com/auth/drive.readonly https://www.googleapis.com/auth/userinfo.email'
                    },
                b = null,
           
                i = function (e, t) {
                    var n,
                    i;
                    return n = _.extend({
                        immediate: e
                        }, r),
                        e ? (i = L().id) && _.extend(n, {
                        user_id: i,
                        authuser: !1
                        })  : _.extend(n, {
                        approval_prompt: 'force',
                        max_auth_age: 0,
                        user_id: !1
                        }),     
                    gapi.auth.authorize(n, function (e) {
                    return N(),
                    typeof t == 'function' ? t()  : void 0
                    })
                },
                
                f = function () {
                    var e;
                    console.log(e);
                    if (e = d()) w(e),
                    gapi.auth.setToken(null);
                    return b != null && b.setVisible(!1),
                    b = null
                },
                
                d = function () {   
                    var e;
                    return (e = gapi.auth.getToken()) != null ? e.access_token : void 0
                },
                
                w = function (e) {
                    return $.getJSON('https://accounts.google.com/o/oauth2/revoke?token=' + e)
                },
                
                L = function (e) {
                    var t,
                    n;
                    //console.log(e)
                    e? data  =  {'id':e.id,'email':e.email} : data = {};
                    return t = 'google:userinfo',
                    e ? localStorage.setItem(t,JSON.stringify(data))  : e === null ? localStorage.removeItem(t)  : (n = localStorage.getItem(t)) != null ? n : {
                    }
                },
                
                c = function (e) {
                    return gapi.client.load('oauth2', 'v2', function () {
                    return gapi.client.oauth2.userinfo.get().execute(function (t) {
                    if (t != null) return e(t)
                    })
                    })
                },
                
                N = function () {
                    data = {'id':0 , 'email':null};
                    localStorage.setItem('google:userinfo',JSON.stringify(data))   
                    return d() ? c(function (e) {
                    if (JSON.parse(L()).id !== e.id) {
                        L({
                        id: e.id,
                        email: e.email
                        });
                    if (JSON.parse(L()).id) return $(document
                    ).trigger('google:userchange')
                    }
                    })  : L(null)
                },
                
                u = function () {  
                    return d() ? gapi.load('picker', function () {
                    return $('div.picker, iframe.picker').remove(),
                    b = s(),
                    b.setVisible(!0),
                    t(),
                    h()
                    })  : i(!1, function () {
                    return d() ? u()  : x()
                    })
                },
                
                C = function () {
                    return f(),
                    u()
                },
                y = function (t) {
                    var n,
                    r,
                    i,
                    s;
                    switch (t.action) {
                    case google.picker.Action.PICKED:
                    s = t.docs;
                    for (r = 0, i = s.length; r < i; r++) n = s[r],
                    n.fileId = n.id;
                        downloadFile(n,function(e){
                            return e == null ? alert ("Error in downloading file"):  $(document).trigger('progress:popup') ;
                        })
                    }
                },
                
                s = function () {
                return (new google.picker.PickerBuilder).setAppId(n).setOAuthToken(gapi.auth.getToken().access_token)
                        .addView((new google.picker.DocsView).setMimeTypes('application/vnd.google-apps.document'))
                        .setCallback(y).build()
                },
                
                l = function (e, t) {
                    return gapi.client.load('drive', 'v2', function () {
                    return gapi.client.drive.files.get({
                    fileId: e
                    }).execute(function (e) {
                    return t(e)
                    })
                    })
                },

                h = function () {
                    var e;
                    e = $('div.picker-dialog:visible');
                    if (e.offset().top + e.outerHeight() - $(window).height() + $(window).scrollTop() > 0) return e.css({
                    top: $(window).scrollTop() + ($(window).height() - e.outerHeight()) / 2
                })
                },
                
                S = function () {
                    return $('div.picker-dialog:visible [data-role~=picker_account_email]').html(JSON.parse(L()).email)
                },
                
                t = function () {
                    var e;
                    return e = 'picker_account_switcher',
                    $('div.picker-dialog:visible').prepend($('[data-behavior~=' + e + ']').clone()),
                    S()
                },
                
                T = function () {
                    return d() ? u()  : $('[data-behavior~=google_connector]').show().addClass('active')
                },
                
                e = null,
                $(document).on('click', '[data-behavior~=create_google_file_picker]', function () {
                return u(),
                !1
                }),
                $(document).on('click', '[data-behavior~=google_account_switcher]', function (e) {  
                return C(),
                !1
                }),
                $(document).on('google:userchange', function () {
                return S(),
                $('[data-behavior~=google_doc_preview]').trigger('reset')
                }),

                $(document).on('progress:popup', function () {
                   $( "#my-content-id" ).dialog({  
                                          closeOnEscape: false,
                                          position: { my: "right+600 top+400", at: "left bottom-250", of: "#wp-content-editor-tools" },
                                          width: 280,
                                          height: 218 });

                }),
                


                v = function () {
                    typeof gapi != 'undefined' && gapi !== null && googleClientLoaded();
                    if (!E) return E = !0,
                    $('<script>').attr('src', 'https://apis.google.com/js/client.js?onload=googleClientLoaded').appendTo('head')
                    },
                    this.googleClientLoaded = function () {
                    var e;
                    return e = function () {
                    var e;
                    m = !0,
                    e = [
                    ];
                    }
                    }

                });

                /**
                 * Upload the file's content to wordpress.
                 * @param {Fileinfo/content} file Drive File contents..
                 */                

                function uploaddoc(fileInfo,title) {
                  var data = {
                    'action': 'google_picker_handle',
                    'file_id': fileInfo,
                    'title':title
                  };

                  //alert("Documentin processing");
                  jQuery.ajax({
                    type: "POST",
                    url: ajaxurl,
                    data: data,
                    success: function(response){
                      window.location = response;
                    }
                  });
                }





                /**
                 * Download a file's content.
                 * @param {File} file Drive File instance
                 * @param {Function} callback Function to call when the request is complete.
                 */
                function downloadFile(file, callback) {
                  file.downloadUrl = 'https://docs.google.com/feeds/download/documents/export/Export?id='+file.id+'&exportFormat=html' ; 
                  if (file.downloadUrl) { 
                    var accessToken = gapi.auth.getToken().access_token;
                    var xhr = new XMLHttpRequest();
                    xhr.open('GET', file.downloadUrl);
                    xhr.setRequestHeader('Authorization', 'Bearer ' + accessToken);
                    xhr.onload = function() {
                      uploaddoc(xhr.responseText,file.name);
                      callback("success")
                    };
                    xhr.onerror = function() {
                      callback(null);
                    };
                    xhr.send();
                  } else {
                    callback(null);
                  }
                } 



