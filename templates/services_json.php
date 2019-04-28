<?php
$services = $this->get('services');
$json = array();
foreach($services as $service) {
	$json_obj = new StdClass;
	$json_obj->name = $service->name;
	$json[] = $json_obj;
}
out(json_encode($json), ESC_NONE);
