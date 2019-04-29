<?php
if(isset($_POST['add_certificate'])) {
    $name = trim($_POST['name']);
    $private = trim($_POST['private']);
    $cert = trim($_POST['cert']);
    $fullchain = trim($_POST['fullchain']);
    
    $certificate = new Certificate;
    $certificate->name = $name;
    $certificate->private = $private;
    $certificate->cert = $cert;
    $certificate->fullchain = $fullchain;
    $certificate->owner_id = $active_user->id;

    try {
        $certificate_dir->add_certificate($certificate);
        $alert = new UserAlert;
        $alert->content = 'Certificate \'<a href="'.rrurl('/certificates/'.urlencode($certificate->name)).'" class="alert-link">'.hesc($certificate->name).'</a>\' successfully created.';
        $alert->escaping = ESC_NONE;
        $active_user->add_alert($alert);
        redirect('#add');
    } catch(CertificateAlreadyExistsException $e) {
        $alert = new UserAlert;
        $alert->content = 'Certificate \'<a href="'.rrurl('/certificates/'.urlencode($certificate->name)).'" class="alert-link">'.hesc($certificate->name).'</a>\' is already known by SSL Cert Authority.';
        $alert->escaping = ESC_NONE;
        $alert->class = 'danger';
        $active_user->add_alert($alert);
        redirect('#add');
    } catch(InvalidArgumentException $e) {
        $content = new PageSection('certificate_upload_fail');
    }
} else {
    $defaults = array();
    $defaults['name'] = '';
    $defaults['serial'] = '';
    $filter = simplify_search($defaults, $_GET);
    if(!$active_user->admin) {
        $filter['owner_id'] = $active_user->id;
    }
	try {
		$certificates = $certificate_dir->list_certificates(array(), $filter);
	} catch(InvalidRegexpException $e) {
		$certificates = array();
		$alert = new UserAlert;
		$alert->content = "The search pattern '".$filter['name']."' or '".$filter['serial']."' is invalid.";
		$alert->class = 'danger';
		$active_user->add_alert($alert);
	}
    if(isset($router->vars['format']) && $router->vars['format'] == 'json') {
		$page = new PageSection('certificates_json');
        $page->set('certificates', $certificates);
		header('Content-type: application/json; charset=utf-8');
		echo $page->generate();
		exit;
    } else {
    
        $content = new PageSection('certificates');
        $content->set('filter', $filter);
        $content->set('certificates', $certificates);
		$head = '<link rel="alternate" type="application/json" href="certificates.json" title="JSON for this page">'."\n";
    }
}
    
$page = new PageSection('base');
$page->set('title', 'Certificates');
if(isset($head)) {
	$page->set('head', $head);
}
$page->set('content', $content);
$page->set('alerts', $active_user->pop_alerts());
echo $page->generate();
