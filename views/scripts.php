<?php
if(isset($_POST['add_script'])) {
    $name = trim($_POST['name']);
    $content = trim($_POST['content']);
    $type = trim($_POST['type']);

    $script = new Script;
    $script->name = $name;
    $script->content = $content;

    switch($type) {
        case 'restart':
        case 'status':
        case 'check':
            $script->type = $type;
            try {
                $script_dir->add_script($script);
                $alert = new UserAlert;
                $alert->content = 'Script \'<a href="'.rrurl('/scripts/'.urlencode($script->name)).'" class="alert-link">'.hesc($script->name).'</a>\' successfully created.';
                $alert->escaping = ESC_NONE;
                $active_user->add_alert($alert);
            } catch(ScriptAlreadyExistsException $e) {
                $alert = new UserAlert;
                $alert->content = 'Script \'<a href="'.rrurl('/scripts/'.urlencode($script->name)).'" class="alert-link">'.hesc($script->name).'</a>\' is already known by SSL Cert Authority.';
                $alert->escaping = ESC_NONE;
                $alert->class = 'danger';
                $active_user->add_alert($alert);
            }
            redirect('#add');
            break;
        default:
            $content = new PageSection('invalid_script_type');
            $content->set('type', $type);
    }
} else {
    $defaults = array();
    $defaults['name'] = '';
    $defaults['type'] = array('restart', 'status', 'check');
    $filter = simplify_search($defaults, $_GET);
    try {
        $scripts = $script_dir->list_scripts(array(), $filter);
    } catch(InvalidRegexpException $e) {
        $scripts = array();
        $alert = new UserAlert;
        $alert->content = "The name search pattern '".$filter['name']."' is invalid.";
        $alert->class = 'danger';
        $active_user->add_alert($alert);
    }
    if(isset($router->vars['format']) && $router->vars['format'] == 'json') {
		$page = new PageSection('scripts_json');
        $page->set('scripts', $scripts);
		header('Content-type: application/json; charset=utf-8');
		echo $page->generate();
		exit;
    } else {
        $content = new PageSection('scripts');
        $content->set('filter', $filter);
        $content->set('scripts', $scripts);
		$head = '<link rel="alternate" type="application/json" href="scripts.json" title="JSON for this page">'."\n";
    }
}
    
$page = new PageSection('base');
$page->set('title', 'Scripts');
if(isset($head)) {
	$page->set('head', $head);
}
$page->set('content', $content);
$page->set('alerts', $active_user->pop_alerts());
echo $page->generate();
