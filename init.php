<?php
require('config.php');

// using library to upload photos found here: http://wiki.eyermonkey.com/Facebook_Photo_Uploads
include_once 'client/facebook_php5_photoslib.php';
$facebook = new FacebookPhotos($api_key, $secret, true);

$user = $facebook->require_login();

// catch the exception that gets thrown if the cookie has an invalid session_key in it
try {
	if (!$facebook->api_client->users_isAppAdded()) {
		$facebook->redirect($facebook->get_add_url());
	}
} catch (Exception $ex) {
	// this will clear cookies for your application and redirect them to a login prompt
	$facebook->set_user(null, null);
	$facebook->redirect($appurl);
}

session_id(md5($facebook->api_client->session_key));
session_start();
?>