<?php
if(!$active_user->admin) {
	require('views/error403.php');
	die;
}
try {
	$profile = $profile_dir->get_profile_by_name($router->vars['name']);
} catch(ProfileNotFoundException $e) {
	require('views/error404.php');
	die;
}
if(isset($_POST['delete_profile'])) {
	$profile->delete();
	$alert = new UserAlert;
	$alert->content = "Profile deleted.";
	$active_user->add_alert($alert);
	redirect('/profiles');
} elseif(isset($_POST['edit_profile'])) {
    $profile->name = getParameterOrDie($_POST, 'name');

	try {
        $profile->certificate_id = $certificate_dir->get_certificate_by_id(getParameterOrDie($_POST, 'certificate_id'))->id;
        try {
			$profile->update();
			$alert = new UserAlert;
			$alert->content = "Profile saved.";
			$active_user->add_alert($alert);
			redirect('/profiles/'.urlencode($profile->name).'#edit');
		} catch(UniqueKeyViolationException $e) {
			$content = new PageSection('unique_key_violation');
			$content->set('exception', $e);
		}
    } catch(CertificateNotFoundException $e) {
        $content = new PageSection('certificate_not_found');
    }
} elseif(isset($_POST['add_servers'])) {
	$servers_names = preg_split('/[\s,]+/', getOptParameter($_POST, 'servers'), null, PREG_SPLIT_NO_EMPTY);
    $servers = array();
    foreach($servers_names as $server_name) {
        $server_name = trim($server_name);
        try {
            $new_server = null;
            $new_server = $server_dir->get_server_by_hostname($server_name);
            if(isset($new_server)) {
                $servers[] = $new_server;
            }
        } catch(ServerNotFoundException $e) {
            $content = new PageSection('server_not_found');
        }
    }

    if(count($servers) == count($servers_names)) {
		try {
			foreach($servers as $server) {
				$profile->add_server($server);
			}
			$alert = new UserAlert;
			$alert->content = "Profile saved.";
			$alert->escaping = ESC_NONE;
			$active_user->add_alert($alert);
		} catch(UniqueKeyViolationException $e) {
			$content = new PageSection('unique_key_violation');
			$content->set('exception', $e);
		}
		redirect('#servers');
    }
} elseif(isset($_POST['delete_server'])) {
	try {
		$server = $server_dir->get_server_by_hostname(getParameterOrDie($_POST, 'server'));	
		$profile->delete_server($server);
		$alert = new UserAlert;
		$alert->content = "Profile saved.";
		$alert->escaping = ESC_NONE;
		$active_user->add_alert($alert);
		redirect('#servers');
	} catch(ServerNotFoundException $e) {
		$content = new PageSection('server_not_found');
	}
} elseif(isset($_POST['add_services'])) {
    $service_names = preg_split('/[\s,]+/', getOptParameter($_POST, 'services'), null, PREG_SPLIT_NO_EMPTY);
    $services = array();
    foreach($service_names as $service_name) {
        $service_name = trim($service_name);
        try {
            $new_service = null;
            $new_service = $service_dir->get_service_by_name($service_name);
            if(isset($new_service)) {
                $services[] = $new_service;
            }
        } catch(ServiceNotFoundException $e) {
            $content = new PageSection('service_not_found');
        }
    }

    if(count($services) == count($service_names)) {
		try {
			foreach($services as $service) {
				$profile->add_service($service);
			}
			$alert = new UserAlert;
			$alert->content = "Profile saved.";
			$alert->escaping = ESC_NONE;
			$active_user->add_alert($alert);
		} catch(UniqueKeyViolationException $e) {
			$content = new PageSection('unique_key_violation');
			$content->set('exception', $e);
		}
		redirect('#services');
    }
} elseif(isset($_POST['delete_service'])) {
	try {
		$service = $service_dir->get_service_by_name(getParameterOrDie($_POST, 'service'));	
		$profile->delete_service($service);
		$alert = new UserAlert;
		$alert->content = "Profile saved.";
		$alert->escaping = ESC_NONE;
		$active_user->add_alert($alert);
		redirect('#services');
	} catch(ServerNotFoundException $e) {
		$content = new PageSection('service_not_found');
	}
} else {
	if(isset($router->vars['format']) && $router->vars['format'] == 'json') {
		$page = new PageSection('profile_json');
		$page->set('profile', $profile);
		$page->set('servers', $profile->list_servers());
		$page->set('services', $profile->list_services());
		header('Content-type: application/json; charset=utf-8');
		echo $page->generate();
		exit;
	} else {
        $defaults = array();
        $defaults['name'] = '';
		$filter = simplify_search($defaults, $_GET);
		try {
			$servers = $profile->list_servers(array(), $filter);
			$services = $profile->list_services(array(), $filter);
		} catch(InvalidRegexpException $e) {
			$servers = array();
			$services = array();
			$alert = new UserAlert;
			$alert->content = "The name search pattern '".$filter['name']."' is invalid.";
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
		} else {
			$content = new PageSection('profile');
			$content->set('filter', $filter);
			$content->set('profile', $profile);
			$content->set('all_certificates', $certificate_dir->list_certificates(array(), array("signing_request" => 0)));
			$content->set('all_servers', $server_dir->list_servers());
			$content->set('all_services', $service_dir->list_services());
			$content->set('servers', $servers);
			$content->set('services', $services);
			$content->set('log', $profile->get_log());
			$head = '<link rel="alternate" type="application/json" href="'.urlencode($router->vars['name']).'/format.json" title="JSON for this page">'."\n";
		}
	}
}

$page = new PageSection('base');
$page->set('title', $profile->name);
if(isset($head)) {
	$page->set('head', $head);
}
$page->set('content', $content);
$page->set('alerts', $active_user->pop_alerts());
echo $page->generate();
