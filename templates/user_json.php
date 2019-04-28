<?php
$user = $this->get('user');
$json_user = new StdClass;
$json_user->uid = $user->uid;
$json_user->name = $user->name;
$json_user->email = $user->email;
$json_user->auth_realm = $user->auth_realm;
$json_user->active = $user->active;
$json_user->force_disable = $user->force_disable;
out(json_encode($json_user), ESC_NONE);
