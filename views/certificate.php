<?php
try {
	$certificate = $certificate_dir->get_certificate_by_name($router->vars['name']);
} catch(CertificateNotFoundException $e) {
	require('views/error404.php');
	die;
}
if(!$active_user->admin && $certificate->owner_id != $active_user->id) {
	require('views/error403.php');
	die;
}
if(isset($_POST['delete_certificate'])) {
	try {
		$certificate->delete();
		$alert = new UserAlert;
		$alert->content = "Certificate deleted.";
		$active_user->add_alert($alert);
		redirect('/certificates');
	} catch(CertificateInUseException $e) {
		$content = new PageSection('certificate_required');
	}
} elseif(isset($_POST['migrate']) && $active_user->admin) {
	try {
		$new_certificate_id = $certificate_dir->get_certificate_by_id(trim($_POST['certificate_id']))->id;
		$profiles = $certificate->list_dependent_profiles(array());
		foreach($profiles as $profile) {
			$profile->certificate_id = $new_certificate_id;
			$profile->update();
		}
		$alert = new UserAlert;
		$alert->content = 'Profiles successfully migrated to new certificate.';
		$alert->escaping = ESC_NONE;
		$active_user->add_alert($alert);
		redirect('#migrate');
	} catch(CertificateNotFoundException $e) {
		$content = new PageSection('certificate_not_found');
	}
} else {
	if(isset($router->vars['format']) && $router->vars['format'] == 'json') {
		$page = new PageSection('certificate_json');
		$page->set('certificate', $certificate);
		header('Content-type: application/json; charset=utf-8');
		echo $page->generate();
		exit;
	} else {
		$defaults = array();
		$defaults['name'] = '';
		$filter = simplify_search($defaults, $_GET);
		try {
			$profiles = $certificate->list_dependent_profiles(array(), $filter);
		} catch(InvalidRegexpException $e) {
			$profiles = array();
			$alert = new UserAlert;
			$alert->content = "The name search pattern '".$filter['name']."' is invalid.";
			$alert->class = 'danger';
			$active_user->add_alert($alert);
		}
	
		$content = new PageSection('certificate');
		$content->set('admin', $active_user->admin);
		$content->set('filter', $filter);
		$content->set('certificate', $certificate);
		$content->set('all_certificates', $certificate_dir->list_certificates());
		$content->set('profiles', $profiles);
		$content->set('log', $certificate->get_log());
		$head = '<link rel="alternate" type="application/json" href="'.urlencode($router->vars['name']).'/format.json" title="JSON for this page">'."\n";
	}
}

$page = new PageSection('base');
$page->set('title', "Certificate " . $certificate->name);
if(isset($head)) {
	$page->set('head', $head);
}
$page->set('content', $content);
$page->set('alerts', $active_user->pop_alerts());
echo $page->generate();
