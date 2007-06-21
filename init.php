<?php
require('config.php');

// Propagate the PHP session using the FB session_key
$prefix = ($_REQUEST['fb_sig_user']) ? 'fb_sig' : $api_key;
if (isset($_REQUEST[$prefix.'_session_key'])) {
   session_name($_REQUEST[$prefix.'_session_key']);
   session_start();
} else {
   // Just so there *is* a session for times when there is no fb session
   session_start();
}

// using library to upload photos found here: http://wiki.eyermonkey.com/Facebook_Photo_Uploads
include_once 'client/facebook_php5_photoslib.php';
$facebook = new FacebookPhotos($api_key, $secret);

$user = $facebook->require_login();

// catch the exception that gets thrown if the cookie has an invalid session_key in it
try {
	if (!$facebook->api_client->users_isAppAdded()) {
		$facebook->redirect($facebook->get_add_url());
	}
} catch (Exception $ex) {
	// this will clear cookies for your application and redirect them to a login prompt
	$facebook->set_user(null, null);
	$facebook->redirect($callbackurl);
}
?>