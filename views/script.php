<?php
if(!$active_user->admin) {
	require('views/error403.php');
	die;
}
try {
	$script = $script_dir->get_script_by_name($router->vars['name']);
} catch(ScriptNotFoundException $e) {
	require('views/error404.php');
	die;
}
if(isset($_POST['delete_script'])) {
	$script->delete();
	$alert = new UserAlert;
	$alert->content = "Script deleted.";
	$active_user->add_alert($alert);
	redirect('/scripts');
} elseif(isset($_POST['edit_script'])) {
    $script->name = trim($_POST['name']);
	$script->content = trim($_POST['content']);
	$type = trim($_POST['type']);
	
    switch($type) {
        case 'restart':
        case 'status':
        case 'check':
            $script->type = $type;
			try {
				$script->update();
				$alert = new UserAlert;
				$alert->content = "Script saved.";
				$active_user->add_alert($alert);
				redirect('/scripts/'.urlencode($script->name).'#edit');
			} catch(UniqueKeyViolationException $e) {
				$content = new PageSection('unique_key_violation');
				$content->set('exception', $e);
			}
            break;
        default:
            $content = new PageSection('invalid_script_type');
            $content->set('type', $type);
    }
} else {
	if(isset($router->vars['format']) && $router->vars['format'] == 'json') {
		$page = new PageSection('script_json');
		$page->set('script', $script);
		header('Content-type: application/json; charset=utf-8');
		echo $page->generate();
		exit;
	} else {
        $defaults = array();
        $defaults['uid'] = '';
        $defaults['name'] = '';
        $filter = simplify_search($defaults, $_GET);
		try {
			$services = $script->list_dependent_services(array(), $filter);
		} catch(InvalidRegexpException $e) {
			$services = array();
			$alert = new UserAlert;
			$alert->content = "The name search pattern '".$filter['name']."' is invalid.";
			$alert->class = 'danger';
			$active_user->add_alert($alert);
		}
		
		$content = new PageSection('script');
		$content->set('filter', $filter);
		$content->set('script', $script);
		$content->set('services', $services);
		$content->set('log', $script->get_log());
		$head = '<link rel="alternate" type="application/json" href="'.urlencode($router->vars['name']).'/format.json" title="JSON for this page">'."\n";
	}
}

$page = new PageSection('base');
$page->set('title', $script->name);
if(isset($head)) {
	$page->set('head', $head);
}
$page->set('content', $content);
$page->set('alerts', $active_user->pop_alerts());
echo $page->generate();
