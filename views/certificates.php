<?php
if(isset($_POST['add_signing_request'])) {
    $name = getParameterOrDie($_POST, 'name');
    $subject = getParameterOrDie($_POST, 'subject');
    $key_type = getParameterOrDie($_POST, 'key_type');

    $certificate = new Certificate;
    $certificate->name = $name;
    $certificate->owner_id = $active_user->id;

    try {
        $certificate->create_openssl_certificate_signing_request($subject, $key_type);
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
    } catch(InvalidKeyTypeException $e) {
        error_log($e);
        $content = new PageSection('invalid_key_type');
        $content->set('type', $key_type);
    } catch(InvalidCertificateSubject $e) {
        error_log($e);
        $content = new PageSection('invalid_subject');
        $content->set('subject', $subject);
    }
} else if(isset($_POST['upload_certificate'])) {
    $name = getParameterOrDie($_POST, 'name');
    $private = getParameterOrDie($_POST, 'private');
    $password = getParameterOrDie($_POST, 'password');
    $cert = getParameterOrDie($_POST, 'cert');
    $fullchain = getParameterOrDie($_POST, 'fullchain');
    
    $private_key = openssl_pkey_get_private('file:///'.$base_path.'/config/cert-sync');
    if (is_bool($private_key)) {
        throw new Exception("Missing cert-sync.pub file.");
    }
    $password_plain = "";
    $password = pack('H*', $password);
    $success = openssl_private_decrypt($password, $password_plain, $private_key);
    if($success) {
        $private = cryptoJsAesDecrypt($password_plain, $private);
        $success = $private != null;
    }
    if ($success) {
        $certificate = new Certificate;
        $certificate->name = $name;
        $certificate->private = $private;
        $certificate->cert = $cert;
        $certificate->fullchain = $fullchain;
        $certificate->owner_id = $active_user->id;
        $certificate->signing_request = 0;
        $certificate->csr = '';
    
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
		$head = '<link rel="alternate" type="application/json" href="certificates.json" title="JSON for this page">\n'.
            '<script defer src="rsa/jsbn.js"></script>\n'.
            '<script defer src="rsa/prng4.js"></script>\n'.
            '<script defer src="rsa/rng.js"></script>\n'.
            '<script defer src="rsa/rsa.js"></script>\n'.
            '<script defer src="rsa/aes.js"></script>\n'.
            '<script defer src="crypt.js"></script>\n';
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
