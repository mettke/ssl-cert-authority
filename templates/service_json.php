<?php
$service = $this->get('service');
$json_obj = new StdClass;
$json_obj->name = $service->name;
$json_obj->restart_script = $service->restart_script->name;
$json_obj->status_script = $service->status_script->name;
$json_obj->check_script = $service->check_script->name;
out(json_encode($json_obj), ESC_NONE);
