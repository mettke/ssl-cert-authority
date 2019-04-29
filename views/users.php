<?php
if(!$active_user->admin) {
	require('views/error403.php');
	die;
}
if(isset($_POST['add_user'])) {
    $uid = trim($_POST['uid']);
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    
    $user = new User;
    $user->uid = $uid;
    $user->name = $name;
    $user->email = $email;
    $user->auth_realm = 'local';
    $user->active = 1;
    if (isset($_POST['admin']) && $_POST['admin'] === 'admin') {
        $user->admin = 1;
    } else {
        $user->admin = 0;
    }

    try {
        $user_dir->add_user($user);
        $alert = new UserAlert;
        $alert->content = 'User \'<a href="'.rrurl('/users/'.urlencode($user->uid)).'" class="alert-link">'.hesc($user->uid).'</a>\' successfully created.';
        $alert->escaping = ESC_NONE;
        $active_user->add_alert($alert);
    } catch(UserAlreadyExistsException $e) {
        $alert = new UserAlert;
        $alert->content = 'User \'<a href="'.rrurl('/users/'.urlencode($user->uid)).'" class="alert-link">'.hesc($user->uid).'</a>\' is already known by SSL Cert Authority.';
        $alert->escaping = ESC_NONE;
        $alert->class = 'danger';
        $active_user->add_alert($alert);
    }
    redirect('#add');
} else {
    $defaults = array();
    $defaults['uid'] = '';
    $defaults['name'] = '';
    $filter = simplify_search($defaults, $_GET);
    try {
        $users = $user_dir->list_users(array(), $filter);
    } catch(InvalidRegexpException $e) {
        $users = array();
        $alert = new UserAlert;
        $alert->content = "The name search pattern '".$filter['name']."' is invalid.";
        $alert->class = 'danger';
        $active_user->add_alert($alert);
    }
    if(isset($router->vars['format']) && $router->vars['format'] == 'json') {
        $page = new PageSection('users_json');
        $page->set('users', $users);
        header('Content-type: application/json; charset=utf-8');
        echo $page->generate();
    } else {
        $content = new PageSection('users');
        $content->set('filter', $filter);
        $content->set('users', $users);
        $head = '<link rel="alternate" type="application/json" href="users.json" title="JSON for this page">'."\n";
    }
}

$page = new PageSection('base');
$page->set('title', 'Users');
if(isset($head)) {
	$page->set('head', $head);
}
$page->set('content', $content);
$page->set('alerts', $active_user->pop_alerts());
echo $page->generate();
