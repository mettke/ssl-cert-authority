<?php
try {
	$user = $user_dir->get_user_by_uid($router->vars['username']);
} catch(UserNotFoundException $e) {
	require('views/error404.php');
	die;
}
if(isset($_POST['edit_user'])) {
	if($active_user->auth_realm == 'LDAP' ) {
		$user->force_disable = $_POST['force_disable'];
		$user->get_details_from_ldap();
	} elseif($active_user->auth_realm == 'local' ) {
		$user->uid = trim($_POST['uid']);
		$user->name = trim($_POST['name']);
		$user->email = trim($_POST['email']);
	}
	try {
		$user->update();
		$alert = new UserAlert;
		$alert->content = "User saved.";
		$active_user->add_alert($alert);
		redirect('/users/'.urlencode($user->uid).'#settings');
	} catch(UniqueKeyViolationException $e) {
		$content = new PageSection('unique_key_violation');
		$content->set('exception', $e);
	}
} elseif(isset($_POST['delete_user'])) {
	if($user->auth_realm == 'local' && $user->uid != 'cert-sync' ) {
		$user->delete();
		redirect('/users');
	}
	redirect('#settings');
} else {
    if(isset($router->vars['format']) && $router->vars['format'] == 'json') {
		$page = new PageSection('user_json');
		$page->set('user', $user);
		header('Content-type: application/json; charset=utf-8');
		echo $page->generate();
		exit;
    } else {
		$content = new PageSection('user');
		$content->set('user', $user);
		$content->set('log', $user->get_log());
		$head = '<link rel="alternate" type="application/json" href="'.urlencode($router->vars['username']).'/format.json" title="JSON for this page">'."\n";
	}
}

$page = new PageSection('base');
$page->set('title', $user->name);
if(isset($head)) {
	$page->set('head', $head);
}
$page->set('content', $content);
$page->set('alerts', $active_user->pop_alerts());
echo $page->generate();
