#!/usr/bin/env php
<?php
chdir(__DIR__);
require('../core.php');

$users = $user_dir->list_users();

// Use 'cert-sync' user as the active user (create if it does not yet exist)
try {
	$active_user = $user_dir->get_user_by_uid('cert-sync');
} catch(UserNotFoundException $e) {
	$active_user = new User;
	$active_user->uid = 'cert-sync';
	$active_user->name = 'Synchronization script';
	$active_user->email = '';
	$active_user->auth_realm = 'local';
	$active_user->active = 1;
	$active_user->developer = 0;
	$user_dir->add_user($active_user);
}

$ldap_enabled = $config['ldap']['enabled'];

foreach($users as $user) {
	if($user->auth_realm == 'LDAP') {
		if($ldap_enabled == 1) {
			$active = $user->active;
			try {
				$user->get_details_from_ldap();
				$user->update();
			} catch(UserNotFoundException $e) {
				$user->active = 0;
			}
		} else {
			$user->active = 0;
		}
	}
	$user->update();
}
