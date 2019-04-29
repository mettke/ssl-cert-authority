<?php
if(!$active_user->admin) {
	require('views/error403.php');
	die;
}
if(isset($_POST['add_server'])) {
	$hostname = trim($_POST['hostname']);
	if(!preg_match('|.*\..*\..*|', $hostname)) {
		$content = new PageSection('invalid_hostname');
		$content->set('hostname', $hostname);
	} else {
		$server = new Server;
		$server->hostname = $hostname;
		$server->port = $_POST['port'];
		try {
			$server_dir->add_server($server);
			$alert = new UserAlert;
			$alert->content = 'Server \'<a href="'.rrurl('/servers/'.urlencode($hostname)).'" class="alert-link">'.hesc($hostname).'</a>\' successfully created.';
			$alert->escaping = ESC_NONE;
			$active_user->add_alert($alert);
		} catch(ServerAlreadyExistsException $e) {
			$alert = new UserAlert;
			$alert->content = 'Server \'<a href="'.rrurl('/servers/'.urlencode($hostname)).'" class="alert-link">'.hesc($hostname).'</a>\' is already known by SSL Cert Authority.';
			$alert->escaping = ESC_NONE;
			$alert->class = 'danger';
			$active_user->add_alert($alert);
		}
		redirect('#add');
	}
} else {
	$defaults = array();
	$defaults['sync_status'] = array('sync success', 'sync warning', 'sync failure', 'not synced yet', 'proposed');
	$defaults['hostname'] = '';
	$defaults['ip_address'] = '';
	$filter = simplify_search($defaults, $_GET);
	try {
		$servers = $server_dir->list_servers(array(), $filter);
	} catch(InvalidRegexpException $e) {
		$servers = array();
		$alert = new UserAlert;
		$alert->content = "The hostname search pattern '".$filter['hostname']."' is invalid.";
		$alert->class = 'danger';
		$active_user->add_alert($alert);
	}
	if(isset($_POST['sync'])) {
		$content = new PageSection('server_deployment');
		$content->set('servers', $servers);
	} elseif(isset($_POST['sync_confirm'])) {
		foreach($servers as $server) {
			$server->trigger_sync();
		}
		redirect();
	} elseif(isset($router->vars['format']) && $router->vars['format'] == 'json') {
		$page = new PageSection('servers_json');
		$page->set('servers', $servers);
		header('Content-type: application/json; charset=utf-8');
		echo $page->generate();
		exit;
	} else {
		$content = new PageSection('servers');
		$content->set('filter', $filter);
		$content->set('servers', $servers);
		$head = '<link rel="alternate" type="application/json" href="servers.json" title="JSON for this page">'."\n";
	}
}

$page = new PageSection('base');
$page->set('title', 'Servers');
if(isset($head)) {
	$page->set('head', $head);
}
$page->set('content', $content);
$page->set('alerts', $active_user->pop_alerts());
echo $page->generate();
