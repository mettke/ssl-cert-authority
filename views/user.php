<?php
if(!$active_user->admin) {
	require('views/error403.php');
	die;
}
try {
	$user = $user_dir->get_user_by_uid($router->vars['username']);
} catch(UserNotFoundException $e) {
	require('views/error404.php');
	die;
}
if(isset($_POST['edit_user'])) {
	if($active_user->auth_realm == 'LDAP' ) {
		$user->force_disable = getParameterOrDie($_POST, 'force_disable');
		$user->get_details_from_ldap();
	} elseif($active_user->auth_realm == 'local' ) {
		$user->uid = getParameterOrDie($_POST, 'uid');
		$user->name = getParameterOrDie($_POST, 'name');
		$user->email = getParameterOrDie($_POST, 'email');
    if (isset($_POST['admin']) && $_POST['admin'] === 'admin') {
        $user->admin = 1;
    } else {
        $user->admin = 0;
    }
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
			$defaults = array();
			$defaults['name'] = '';
			$defaults['serial'] = '';
			$filter = simplify_search($defaults, $_GET);

			$content = new PageSection('user');
			$content->set('user', $user);
			$content->set('filter', $filter);
			$content->set('log', $user->get_log());
			$content->set('certificates', $user->list_owned_certificates(array(), $filter));
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
