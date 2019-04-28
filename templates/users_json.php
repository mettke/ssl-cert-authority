<?php
$users = $this->get('users');
$json = array();
foreach($users as $user) {
	$json_user = new StdClass;
	$json_user->uid = $user->uid;
	$json_user->name = $user->name;
	$json[] = $json_user;
}
out(json_encode($json), ESC_NONE);
