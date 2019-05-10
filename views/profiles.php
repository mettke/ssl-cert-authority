<?php
if(!$active_user->admin) {
	require('views/error403.php');
	die;
}
if(isset($_POST['add_profile'])) {
    $name = getParameterOrDie($_POST, 'name');
    $certificate_id = getParameterOrDie($_POST, 'certificate_id');

    $profile = new Profile;
    $profile->name = $name;

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

    if(count($servers) == count($servers_names) &&
        count($services) == count($service_names)) {
        try {
            $profile->certificate_id = $certificate_dir->get_certificate_by_id($certificate_id)->id;
            try {
                $profile_dir->add_profile($profile);
                foreach($servers as $server) {
                    $profile->add_server($server);
                }
                foreach($services as $service) {
                    $profile->add_service($service);
                }
                $alert = new UserAlert;
                $alert->content = 'Profile \'<a href="'.rrurl('/profiles/'.urlencode($profile->name)).'" class="alert-link">'.hesc($profile->name).'</a>\' successfully created.';
                $alert->escaping = ESC_NONE;
                $active_user->add_alert($alert);
            } catch(UserAlreadyExistsException $e) {
                $alert = new UserAlert;
                $alert->content = 'Profile \'<a href="'.rrurl('/profiles/'.urlencode($profile->name)).'" class="alert-link">'.hesc($profile->name).'</a>\' is already known by SSL Cert Authority.';
                $alert->escaping = ESC_NONE;
                $alert->class = 'danger';
                $active_user->add_alert($alert);
            }
            redirect('#add');
        } catch(CertificateNotFoundException $e) {
            $content = new PageSection('certificate_not_found');
        }
    }
} else {
    $defaults = array();
    $defaults['name'] = '';
    $filter = simplify_search($defaults, $_GET);
    try {
        $profiles = $profile_dir->list_profiles(array(), $filter);
    } catch(InvalidRegexpException $e) {
        $profiles = array();
        $alert = new UserAlert;
        $alert->content = "The name search pattern '".$filter['name']."' is invalid.";
        $alert->class = 'danger';
        $active_user->add_alert($alert);
    }
    if(isset($router->vars['format']) && $router->vars['format'] == 'json') {
		$page = new PageSection('profiles_json');
        $page->set('profiles', $profiles);
		header('Content-type: application/json; charset=utf-8');
		echo $page->generate();
		exit;
    } else {
        $content = new PageSection('profiles');
        $content->set('filter', $filter);
        $content->set('profiles', $profiles);
        $content->set('all_certificates', $certificate_dir->list_certificates(array(), array("signing_request" => 0)));
        $content->set('all_servers', $server_dir->list_servers());
        $content->set('all_services', $service_dir->list_services());
		$head = '<link rel="alternate" type="application/json" href="profiles.json" title="JSON for this page">'."\n";
    }
}
    
$page = new PageSection('base');
$page->set('title', 'Profiles');
if(isset($head)) {
	$page->set('head', $head);
}
$page->set('content', $content);
$page->set('alerts', $active_user->pop_alerts());
echo $page->generate();
