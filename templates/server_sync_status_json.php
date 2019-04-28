<?php
$sync_status = $this->get('sync_status');
$last_sync = $this->get('last_sync');
$pending = $this->get('pending');
$json = new StdClass;
$json->sync_status = $sync_status;
if(is_null($last_sync)) {
	$json->last_sync = null;
} else {
	$json->last_sync = new StdClass;
	$json->last_sync->date = $last_sync->date;
	$json->last_sync->details = json_decode($last_sync->details)->value;
}
$json->pending = $pending;
out(json_encode($json), ESC_NONE);
