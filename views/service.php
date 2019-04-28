<?php
try {
	$service = $service_dir->get_service_by_name($router->vars['name']);
} catch(ServiceNotFoundException $e) {
	require('views/error404.php');
	die;
}
if(isset($_POST['add_variable'])) {
    $name = trim($_POST['name']);
	$value = trim($_POST['value']);
	$description = trim($_POST['description']);

    $variable = new ServiceVariable;
    $variable->name = $name;
	$variable->value = $value;
	if(isset($description)) {
		$variable->description = $description;
	} else {
		$variable->description = "";
	}
	try {
		$service->add_variable($variable);
		$alert = new UserAlert;
		$alert->content = 'Variable \'<a href="'.rrurl('/services/'.urlencode($service->name).'/variables/'.urlencode($variable->name)).'" class="alert-link">'.hesc($variable->name).'</a>\' successfully created.';
		$alert->escaping = ESC_NONE;
		$active_user->add_alert($alert);
	} catch(VariableAlreadyExistsException $e) {
		$alert = new UserAlert;
		$alert->content = 'Variable \'<a href="'.rrurl('/services/'.urlencode($service->name).'/variables/'.urlencode($variable->name)).'" class="alert-link">'.hesc($variable->name).'</a>\' is already known by SSL Cert Authority.';
		$alert->escaping = ESC_NONE;
		$alert->class = 'danger';
		$active_user->add_alert($alert);
	}
	redirect('#var');
} elseif(isset($_POST['delete_service'])) {
	$service->delete();
	$alert = new UserAlert;
	$alert->content = "Service deleted.";
	$active_user->add_alert($alert);
	redirect('/services');
} elseif(isset($_POST['edit_service'])) {
    $service->name = trim($_POST['name']);
    $restart_script_id = trim($_POST['restart_script']);
    $status_script_id = trim($_POST['status_script']);
	$check_script_id = trim($_POST['check_script']);
	
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
			$service->update();
			$alert = new UserAlert;
			$alert->content = "Service saved.";
			$active_user->add_alert($alert);
			redirect('/services/'.urlencode($service->name).'#edit');
		} catch(UniqueKeyViolationException $e) {
			$content = new PageSection('unique_key_violation');
			$content->set('exception', $e);
		}
    } catch(ScriptNotFoundException $e) {
        $content = new PageSection('script_not_found');
    }
} else {
	if(isset($router->vars['format']) && $router->vars['format'] == 'json') {
		$page = new PageSection('service_json');
		$page->set('service', $service);
		header('Content-type: application/json; charset=utf-8');
		echo $page->generate();
		exit;
	} else {
        $defaults = array();
        $defaults['name'] = '';
		$filter = simplify_search($defaults, $_GET);
		try {
			$variables = $service->list_variables(array(), $filter);
			$profiles = $service->list_dependent_profiles(array(), $filter);
		} catch(InvalidRegexpException $e) {
			$variables = array();
			$profiles = array();
			$alert = new UserAlert;
			$alert->content = "The name search pattern '".$filter['name']."' is invalid.";
			$alert->class = 'danger';
			$active_user->add_alert($alert);
		}
		
		$content = new PageSection('service');
		$content->set('filter', $filter);
		$content->set('service', $service);
		$content->set('all_scripts', $script_dir->list_scripts());
		$content->set('all_variables', $variables);
		$content->set('profiles', $profiles);
		$content->set('log', $service->get_log());
		$head = '<link rel="alternate" type="application/json" href="'.urlencode($router->vars['name']).'/format.json" title="JSON for this page">'."\n";
	}
}

$page = new PageSection('base');
$page->set('title', $service->name);
if(isset($head)) {
	$page->set('head', $head);
}
$page->set('content', $content);
$page->set('alerts', $active_user->pop_alerts());
echo $page->generate();
