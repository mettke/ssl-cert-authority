<?php
$servers = $this->get('servers');
$json = array();
foreach($servers as $server) {
	$json_obj = new StdClass;
	$json_obj->uuid = $server->uuid;
	$json_obj->hostname = $server->hostname;
	$json_obj->port = $server->port;
	$json_obj->sync_status = $server->sync_status;
	$json[] = $json_obj;
}
out(json_encode($json), ESC_NONE);
