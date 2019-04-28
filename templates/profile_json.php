<?php
$profile = $this->get('profile');
$servers = $this->get('servers');
$services = $this->get('services');
$json_obj = new StdClass;
$json_obj->name = $profile->name;
$json_obj->certificate = $profile->certificate->name;
$json_obj->servers = array();
foreach($servers as $server) {
    $json_obj->servers[] = $server->hostname;
}
$json_obj->services = array();
foreach($services as $service) {
    $json_obj->services[] = $service->name;
}
out(json_encode($json_obj), ESC_NONE);
