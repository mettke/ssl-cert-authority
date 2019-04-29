<?php
$private_key = openssl_pkey_get_private('file:///'.$base_path.'/config/cert-sync');
if (is_bool($private_key)) {
	error_log(openssl_error_string());
	throw new Exception("Missing cert-sync.pub file.");
}
$public_key_details = openssl_pkey_get_details($private_key);
if (is_bool($public_key_details)) {
	error_log(openssl_error_string());
	throw new Exception("Unable to decode private_key");
}
$page = new PageSection('crypt_js');
$page->set('rsa_n', to_hex($public_key_details['rsa']['n']));
$page->set('rsa_e', to_hex($public_key_details['rsa']['e']));
header('Content-type: application/javascript; charset=utf-8');
echo $page->generate();
