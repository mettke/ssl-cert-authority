<?php
$certificates = $this->get('certificates');
$json = array();
foreach($certificates as $certificate) {
	$json_obj = new StdClass;
	$json_obj->name = $certificate->name;
	$json_obj->serial = $certificate->serial;
	$json_obj->expiration = strtotime($certificate->expiration);
	$json[] = $json_obj;
}
out(json_encode($json), ESC_NONE);
