<?php
$server = $this->get('server');
$last_sync_event = $this->get('last_sync_event');
$json_obj = new StdClass;
$json_obj->uuid = $server->uuid;
$json_obj->hostname = $server->hostname;
$json_obj->port = $server->port;
$json_obj->ip_address = $server->ip_address;
$json_obj->rsa_key_fingerprint = $server->rsa_key_fingerprint;
$json_obj->sync_status = $server->sync_status;
if($last_sync_event) {
	$json_obj->last_sync_event = new StdClass;
	$json_obj->last_sync_event->details = $last_sync_event->details;
	$json_obj->last_sync_event->date = $last_sync_event->date;
} else {
	$json_obj->last_sync_event = null;
}
out(json_encode($json_obj), ESC_NONE);
