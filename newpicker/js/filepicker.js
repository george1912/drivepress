
GOOGLEPICKER_CONF = {
    original: (WP_GP_PARAMS.original === 'true'),
    ajaxurl: WP_GP_PARAMS.ajaxurl,
}


jQuery(document).ready(function($){
var e,
t,
n,
r,
i,
s,
o,
u,
a,
f,
l,
c,
h,
p,
d,
v,
m,
g,
y,
b,
w,
E,
S,
x,
T,
N,
C,
k,
L,
A;
n = '32802320039-coq0vn95n2btdltq1c15rgj0kj6l45cd',
r = {
client_id: n,
scope: 'https://www.googleapis.com/auth/drive.readonly https://www.googleapis.com/auth/userinfo.email'
},
b = null,
k = 'document kix spreadsheet ritz drawing presentation punch form freebird'.split(' '),
g = _(k).map(function (e) {
return 'application/vnd.google-apps.' + e
}),
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
return t = 'google:userinfo',
e ? localStorage.set(t, e)  : e === null ? localStorage.unset(t)  : (n = localStorage.get(t)) != null ? n : {
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
return d() ? c(function (e) {
if (L().id !== e.id) {
L({
id: e.id,
email: e.email
});
if (L().id) return $(document
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
downloadFile(n,function(){
   alert ("Error in downloading file")
})
//return e.attachGoogleDocs(t.docs)
}
},
s = function () {
return (new google.picker.PickerBuilder).setAppId(n).setOAuthToken(gapi.auth.getToken().access_token)
        .addView(google.picker.ViewId.DOCUMENTS)
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
a = function (e) {
var t,
n,
r,
s;
return s = $(e).data(),
t = s.fileId,
n = s.fileUrl,
r = new localStorage.views.GoogleDocPreview({
el: e
}),
r.google = {
isAuthorized: d,
authorize: i,
deauthorize: f,
fetchMetadata: l,
fileId: t,
fileUrl: n
},
r.render()
},
h = function () {
var e;
e = $('div.picker-dialog:visible');
if (e.offset().top + e.outerHeight() - $(window).height() + $(window).scrollTop() > 0) return e.css({
top: $(window).scrollTop() + ($(window).height() - e.outerHeight()) / 2
})
},
S = function () {
return $('div.picker-dialog:visible [data-role~=picker_account_email]').html(L().email)
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
x = function () {
return $('[data-behavior~=google_connector_access_denied]').show().addClass('active')
},
p = function () {
return $('.google_connector').hide().removeClass('active')
},
e = null,
$(document).on('click', '[data-behavior~=google_file_picker]', function () {
return e = $(this),
T(),
!1
}),
$(document).on('click', '[data-behavior~=create_google_file_picker]', function () {
return u(),
p(),
!1
}),
$(document).on('click', '[data-behavior~=google_account_switcher]', function (e) {
return C(),
!1
}),
$(document).on('click', '[data-behavior~=cancel_google_connect]', function (e) {
return p(),
!1
}),
$(document).on('google:userchange', function () {
return S(),
$('[data-behavior~=google_doc_preview]').trigger('reset')
}),
localStorage.setItem('enlarger:install enlarger:activate', 'load google doc previews', function () {
return $('[data-behavior~=google_doc_preview]:visible').install('google doc views', function (e) {
return A(function () {
var t,
n,
r,
i;
i = [
];
for (n = 0, r = e.length; n < r; n++) t = e[n],
i.push(a(t));
return i
})
})
}),
localStorage.setItem('page:update', 'load google client', function () {
return $('[data-behavior~=load_google_client]').install('load google client', function (e) {
return A(function () {
return e.fadeIn('fast')
})
})
}),
E = !1,
m = !1,
o = [
],
A = function (e) {
return m ? e()  : (o.push(e), v())
},
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
  jQuery.post(ajaxurl, data, function(response) {
     window.location =response;
  });
}
      

      
/**
 * Download a file's content.
 * @param {File} file Drive File instance.
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
    };
    xhr.onerror = function() {
      callback(null);
    };
    xhr.send();
  } else {
    callback(null);
  }
}      
      