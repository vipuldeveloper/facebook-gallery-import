<?php
require('init.php');
header('Content-Type: text/html; charset=utf-8');

if (isset($_POST['gallery_export_url'])) {
	setcookie('gallery_url', $_POST['gallery_export_url'], time()+360*24*60*60);

	$_SESSION['gallery_export_url'] = $_POST['gallery_export_url'];
	if (strstr( $_POST['gallery_export_url'], '/fb/export_fb.php') === FALSE) {
		die("Error: the export_fb.php script needs to be in a 'fb' subdirectory of your Gallery installation. Example: http://www.domain.com/gallery/fb/export_fb.php");
	}
	$_SESSION['gallery_url'] = str_replace('/fb/export_fb.php', '', $_POST['gallery_export_url']);
	$url = $_SESSION['gallery_export_url'] . '?a=albums';
	$html = file_get_contents($url);
	
	$albums_infos = explode("\n", $html);

	$template = 'step2.html';

} else if (isset($_POST['gallery_album']) || isset($_GET['gallery_album'])) {
	if (isset($_GET['gallery_album'])) {
		$_POST['gallery_album'] = $_GET['gallery_album'];
	}
	$albumId = array_shift(explode(';', $_POST['gallery_album']));
	$_SESSION['gallery_name'] = str_replace("$albumId;", "", $_POST['gallery_album']);

	$url = $_SESSION['gallery_export_url'] . '?a=photos&id=' . $albumId;
	$html = file_get_contents($url);
	
	$photos_infos = explode("\n", $html);

	$num_photos = sizeof($photos_infos) - 1;

	$template = 'step3.html';

} else if (isset($_POST['gallery_photos'])) {
	$_SESSION['gallery_selected_photos'] = $_POST['gallery_photos'];
	$_SESSION['gallery_redirects'] = 1;
	$_SESSION['gallery_uploads'] = 0;
	$_SESSION['gallery_skips'] = 0;
	$facebook->redirect("http://gallery.danslereseau.com/fb/?gallery_import=".time());

} else if (isset($_GET['gallery_import'])) {

	while (TRUE) {
		if (empty($_SESSION['gallery_selected_photos'])) {
			// Done. Redirecting to new album so you can approve newly uploaded photos
			$facebook->redirect($_SESSION['gallery_album']['link']);
		}

		// Create a new album to continue the import
		if (($_SESSION['gallery_uploads'] + $_SESSION['gallery_skips']) % 60 == 0) {
			if ($_SESSION['gallery_uploads'] == 0 && $_SESSION['gallery_skips'] == 0) {
				$gallery_name = $_SESSION['gallery_name'];
			} else {
				$gallery_name = $_SESSION['gallery_name'] ." (". ((($_SESSION['gallery_uploads'] + $_SESSION['gallery_skips']) / 60) + 1) .")";
			}

			$existing_album = FALSE;
		    $albums = $facebook->api_client->photos_getAlbums(null, null, null);
			if ($albums && is_array($albums)) {
				foreach ($albums as $album) {
					if ($album['name'] == $gallery_name) {
						$existing_album = TRUE;
						break;
					}
				}
			}
			if (!$existing_album) {
			    $album = $facebook->api_client->photos_createAlbum($gallery_name);
			}
			$_SESSION['gallery_album'] = $album;
			$_SESSION['gallery_existing_photos'] = $facebook->api_client->photos_get(null, $_SESSION['gallery_album']['aid'], null);
		}

		$photo_url = array_shift($_SESSION['gallery_selected_photos']);
		$photo = $_SESSION['gallery_photos'][$photo_url];

		// Try not to re-upload a photo that is already there.
		$existing_photo = FALSE;
		if (!empty($_SESSION['gallery_existing_photos'])) {
			foreach ($_SESSION['gallery_existing_photos'] as $old_photo) {
				if ($old_photo['caption'] == $photo->caption) {
					$existing_photo = TRUE;
					break;
				}
			}
		}
		if (!$existing_photo) {
			$photo_url = $_SESSION['gallery_url'] . $photo->url;
		    $photo = $facebook->api_client->photos_upload($photo_url, $_SESSION['gallery_album']['aid'], $photo->caption);
			$_SESSION['gallery_uploads']++;
			break;
		}
		$_SESSION['gallery_skips']++;
	}

	$_SESSION['gallery_redirects']++;
	if ($_SESSION['gallery_redirects'] <= 15) {
		$facebook->redirect("http://gallery.danslereseau.com/fb/?gallery_import=".time());
	} else {
		$_SESSION['gallery_redirects'] = 0;
		$template = 'step4.html';
	}

} else {
	$template = 'step1.html';
}

include('template.html');

?>