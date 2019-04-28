<?php
$routes = array(
	'/' => 'profiles',
	'/activity' => 'activity',
	'/certificates' => 'certificates',
	'/certificates.{format}' => 'certificates',
	'/certificates/{name}' => 'certificate',
	'/certificates/{name}/format.{format}' => 'certificate',
	'/help' => 'help',
	'/profiles' => 'profiles',
	'/profiles.{format}' => 'profiles',
	'/profiles/{name}' => 'profile',
	'/profiles/{name}/format.{format}' => 'profile',
	'/servers' => 'servers',
	'/servers.{format}' => 'servers',
	'/servers/{hostname}' => 'server',
	'/servers/{hostname}/format.{format}' => 'server',
	'/servers/{hostname}/sync_status' => 'server_sync_status',
	'/services' => 'services',
	'/services.{format}' => 'services',
	'/services/{name}' => 'service',
	'/services/{name}/format.{format}' => 'service',
	'/services/{service}/variables' => 'variables',
	'/services/{service}/variables.{format}' => 'variables',
	'/services/{service}/variables/{name}' => 'variable',
	'/services/{service}/variables/{name}/format.{format}' => 'variable',
	'/scripts' => 'scripts',
	'/scripts.{format}' => 'scripts',
	'/scripts/{name}' => 'script',
	'/scripts/{name}/format.{format}' => 'script',
	'/users' => 'users',
	'/users.{format}' => 'users',
	'/users/{username}' => 'user',
	'/users/{username}/format.{format}' => 'user',
);

$public_routes = array(
	
);
