<?php
if(!$active_user->admin) {
	require('views/error403.php');
	die;
}
try {
	$service = $service_dir->get_service_by_name($router->vars['service']);
	$variable = $service->get_variable_by_name($router->vars['name']);
} catch (ServiceNotFoundException $e) {
	require('views/error404.php');
	die;
}
if (isset($_POST['delete_variable'])) {
	$variable->delete();
	$alert = new UserAlert;
	$alert->content = "Variable deleted.";
	$active_user->add_alert($alert);
	redirect('/services/'.urlencode($service->name));
} elseif (isset($_POST['edit_variable'])) {
    $variable->name = getParameterOrDie($_POST, 'name');
	$variable->value = getParameterOrDie($_POST, 'value');
	$description = getParameterOrDie($_POST, 'description');
	if(isset($description)) {
		$variable->description = $description;
	} else {
		$variable->description = "";
	}
	$variable->update($variable);
	$alert = new UserAlert;
	$alert->content = 'Variable \'<a href="'.rrurl('/services/'.urlencode($service->name).'/variables/'.urlencode($variable->name)).'" class="alert-link">'.hesc($variable->name).'</a>\' successfully updated.';
	$alert->escaping = ESC_NONE;
	$active_user->add_alert($alert);
	redirect('/services/'.urlencode($service->name).'/variables/'.urlencode($variable->name).'#edit');
} else {
	if (isset($router->vars['format']) && $router->vars['format'] == 'json') {
		$page = new PageSection('variable_json');
		$page->set('variable', $variable);
		$page->set('service', $service);
		header('Content-type: application/json; charset=utf-8');
		echo $page->generate();
		exit;
	} else {
		$content = new PageSection('variable');
		$content->set('variable', $variable);
		$content->set('service', $service);
		$content->set('log', $variable->get_log());
		$head = '<link rel="alternate" type="application/json" href="' . urlencode($router->vars['name']) . '/format.json" title="JSON for this page">' . "\n";
	}
}

$page = new PageSection('base');
$page->set('title', $variable->name);
if(isset($head)) {
	$page->set('head', $head);
}
$page->set('content', $content);
$page->set('alerts', $active_user->pop_alerts());
echo $page->generate();
