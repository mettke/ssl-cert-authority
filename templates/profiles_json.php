<?php
$profiles = $this->get('profiles');
$json = array();
foreach($profiles as $profile) {
	$json_obj = new StdClass;
	$json_obj->name = $profile->name;
	$json_obj->certificate = $profile->certificate->name;
	$json[] = $json_obj;
}
out(json_encode($json), ESC_NONE);
