<?php
if(!$active_user->admin) {
	require('views/error403.php');
	die;
}
try {
	$service = $service_dir->get_service_by_name($router->vars['service']);
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
	redirect('#add');
} else {
	$defaults = array();
	$defaults['name'] = '';
	$filter = simplify_search($defaults, $_GET);
	try {
		$variables = $service->list_variables(array(), $filter);
	} catch(InvalidRegexpException $e) {
		$variables = array();
		$alert = new UserAlert;
		$alert->content = "The name search pattern '".$filter['name']."' is invalid.";
		$alert->class = 'danger';
		$active_user->add_alert($alert);
	}
	if(isset($router->vars['format']) && $router->vars['format'] == 'json') {
		$page = new PageSection('variables_json');
		$page->set('variables', $variables);
		header('Content-type: application/json; charset=utf-8');
		echo $page->generate();
		exit;
	} else {
		
		$content = new PageSection('variables');
		$content->set('filter', $filter);
		$content->set('variables', $variables);
		$content->set('service', $service);
		$head = '<link rel="alternate" type="application/json" href="variables.json" title="JSON for this page">'."\n";
	}
}

$page = new PageSection('base');
$page->set('title', 'Variables');
if(isset($head)) {
	$page->set('head', $head);
}
$page->set('content', $content);
$page->set('alerts', $active_user->pop_alerts());
echo $page->generate();
