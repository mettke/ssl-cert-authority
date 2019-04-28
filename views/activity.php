<?php
if(isset($_GET['clear'])) {
    redirect('/activity');
}
if(isset($_GET['limit'])) {
    $limit = $_GET['limit'];
    if($limit < 100) {
		$alert = new UserAlert;
		$alert->content = 'Limit lower then 100 is not allowed.';
		$alert->escaping = ESC_NONE;
        $alert->class = 'danger';
		$active_user->add_alert($alert);
        redirect('/activity');
    } elseif($limit > 10000) {
		$alert = new UserAlert;
		$alert->content = 'Limit is too high.';
		$alert->escaping = ESC_NONE;
        $alert->class = 'danger';
		$active_user->add_alert($alert);
        redirect('/activity');
    }
} else {
    $limit = 100;
}
$defaults = array();
$defaults['object_id'] = '';
$defaults['type'] = '';
$defaults['limit'] = '100';
$filter = simplify_search($defaults, $_GET);


$content = new PageSection('activity');
$content->set('events', $event_dir->list_events(array(), $filter, $limit));
$content->set('filter', $filter);

$page = new PageSection('base');
$page->set('title', 'Activity');
$page->set('content', $content);
$page->set('alerts', $active_user->pop_alerts());
echo $page->generate();
