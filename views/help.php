<?php
$content = new PageSection('help');
if(file_exists('config/cert-sync.pub')) {
	$content->set('cert-sync-pubkey', file_get_contents('config/cert-sync.pub'));
} else {
	$content->set('cert-sync-pubkey', 'Error: keyfile missing');
}
$content->set('admin_mail', $config['email']['admin_address']);
$content->set('baseurl', $config['web']['baseurl']);
$content->set('security_config', isset($config['security']) ? $config['security'] : array());

$page = new PageSection('base');
$page->set('title', 'Help');
$page->set('content', $content);
$page->set('alerts', $active_user->pop_alerts());
echo $page->generate();
