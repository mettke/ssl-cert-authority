<?php
try {
	$server = $server_dir->get_server_by_hostname($router->vars['hostname']);
} catch(ServerNotFoundException $e) {
	try {
		$server = $server_dir->get_server_by_uuid($router->vars['hostname']);
		redirect('/servers/'.urlencode($server->hostname));
	} catch(ServerNotFoundException $e) {
		require('views/error404.php');
		die;
	}
}

if(isset($_POST['sync'])) {
	$content = new PageSection('server_deployment');
	$content->set('servers', array($server));
} elseif(isset($_POST['sync_confirm'])) {
	$server->trigger_sync();
	redirect();
} elseif(isset($_POST['edit_server'])) {
	$hostname = trim($_POST['hostname']);
	if(!preg_match('|.*\..*\..*|', $hostname)) {
		$content = new PageSection('invalid_hostname');
		$content->set('hostname', $hostname);
	} else {
		$options = array();
		$server->hostname = $hostname;
		$server->port = $_POST['port'];
		if($_POST['rsa_key_fingerprint'] == '') $server->rsa_key_fingerprint = null;
		try {
			$server->update();
			$alert = new UserAlert;
			$alert->content = "Settings saved.";
			$active_user->add_alert($alert);
			redirect('/servers/'.urlencode($hostname).'#settings');
		} catch(UniqueKeyViolationException $e) {
			$content = new PageSection('unique_key_violation');
			$content->set('exception', $e);
		}
	}
} elseif(isset($_POST['delete_server'])) {
	$server->delete();
	$alert = new UserAlert;
	$alert->content = "Server deleted.";
	$active_user->add_alert($alert);
	redirect('/servers');
} elseif(isset($_POST['add_note'])) {
	$note = new ServerNote();
	$note->note = $_POST['note'];
	$server->add_note($note);
	redirect('#notes');
} elseif(isset($_POST['delete_note'])) {
	$note = $server->get_note_by_id($_POST['delete_note']);
	$note->delete();
	redirect('#notes');
} else {
	if(isset($router->vars['format']) && $router->vars['format'] == 'json') {
		$page = new PageSection('server_json');
		$page->set('server', $server);
		$page->set('last_sync_event', $server->get_last_sync_event());
		header('Content-type: application/json; charset=utf-8');
		echo $page->generate();
		exit;
	} else {
        $defaults = array();
        $defaults['name'] = '';
		$filter = simplify_search($defaults, $_GET);
		try {
			$profiles = $server->list_dependent_profiles(array(), $filter);
		} catch(InvalidRegexpException $e) {
			$profiles = array();
			$alert = new UserAlert;
			$alert->content = "The name search pattern '".$filter['name']."' is invalid.";
			$alert->class = 'danger';
			$active_user->add_alert($alert);
		}

		$content = new PageSection('server');
		$content->set('server', $server);
		$content->set('log', $server->get_log());
		$content->set('server_notes', $server->list_notes());
		$content->set('last_sync', $server->get_last_sync_event());
		$content->set('sync_requests', $server->list_sync_requests());
		$content->set('matching_servers_by_ip', $server_dir->list_servers(array(), array('ip_address' => $server->ip_address)));
		$content->set('matching_servers_by_host_key', $server_dir->list_servers(array(), array('rsa_key_fingerprint' => $server->rsa_key_fingerprint)));
		$content->set('output_formatter', $output_formatter);
		$content->set('inventory_config', $config['inventory']);
		$content->set('profiles', $profiles);
		switch($server->sync_status) {
			case 'proposed': $content->set('sync_class', 'warning'); break;
			case 'sync success': $content->set('sync_class', 'success'); break;
			case 'sync warning': $content->set('sync_class', 'warning'); break;
			case 'sync failure': $content->set('sync_class', 'danger'); break;
		}
		$head = '<link rel="alternate" type="application/json" href="'.urlencode($router->vars['hostname']).'/format.json" title="JSON for this page">'."\n";
	}
}

$page = new PageSection('base');
$page->set('title', $server->hostname);
if(isset($head)) {
	$page->set('head', $head);
}
$page->set('content', $content);
$page->set('alerts', $active_user->pop_alerts());
echo $page->generate();
