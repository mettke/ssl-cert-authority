<?php
if(!$active_user->admin) {
	require('views/error403.php');
	die;
}
if(isset($_POST['add_service'])) {
    $name = getParameterOrDie($_POST, 'name');
    $restart_script_id = getParameterOrDie($_POST, 'restart_script');
    $status_script_id = getParameterOrDie($_POST, 'status_script');
    $check_script_id = getParameterOrDie($_POST, 'check_script');

    $service = new Service;
    $service->name = $name;

    try {
        if(is_numeric($restart_script_id)) {
            $service->restart_script_id = $script_dir->get_script_by_id($restart_script_id)->id;
        } else {
            $service->restart_script_id = null;
        }
        if(is_numeric($status_script_id)) {
            $service->status_script_id = $script_dir->get_script_by_id($status_script_id)->id;
        } else {
            $service->status_script_id = null;
        }
        if(is_numeric($check_script_id)) {
            $service->check_script_id = $script_dir->get_script_by_id($check_script_id)->id;
        } else {
            $service->check_script_id = null;
        }
        try {
            $service_dir->add_service($service);
            $alert = new UserAlert;
            $alert->content = 'Service \'<a href="'.rrurl('/services/'.urlencode($service->name)).'" class="alert-link">'.hesc($service->name).'</a>\' successfully created.';
            $alert->escaping = ESC_NONE;
            $active_user->add_alert($alert);
        } catch(ServiceAlreadyExistsException $e) {
            $alert = new UserAlert;
            $alert->content = 'Service \'<a href="'.rrurl('/services/'.urlencode($service->name)).'" class="alert-link">'.hesc($service->name).'</a>\' is already known by SSL Cert Authority.';
            $alert->escaping = ESC_NONE;
            $alert->class = 'danger';
            $active_user->add_alert($alert);
        }
        redirect('#add');
    } catch(ScriptNotFoundException $e) {
        $content = new PageSection('script_not_found');
    }
} else {
    $defaults = array();
    $defaults['name'] = '';
    $filter = simplify_search($defaults, $_GET);
    try {
        $services = $service_dir->list_services(array(), $filter);
    } catch(InvalidRegexpException $e) {
        $services = array();
        $alert = new UserAlert;
        $alert->content = "The name search pattern '".$filter['name']."' is invalid.";
        $alert->class = 'danger';
        $active_user->add_alert($alert);
    }
    if(isset($router->vars['format']) && $router->vars['format'] == 'json') {
		$page = new PageSection('services_json');
        $page->set('services', $services);
		header('Content-type: application/json; charset=utf-8');
		echo $page->generate();
		exit;
    } else {
        $content = new PageSection('services');
        $content->set('filter', $filter);
        $content->set('services', $services);
        $content->set('all_scripts', $script_dir->list_scripts());
		$head = '<link rel="alternate" type="application/json" href="services.json" title="JSON for this page">'."\n";
    }
}
    
$page = new PageSection('base');
$page->set('title', 'Services');
if(isset($head)) {
	$page->set('head', $head);
}
$page->set('content', $content);
$page->set('alerts', $active_user->pop_alerts());
echo $page->generate();
