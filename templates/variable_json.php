<?php
$variable = $this->get('variable');
$service = $this->get('service');
$json_obj = new StdClass;
$json_obj->name = $variable->name;
$json_obj->value = $variable->value;
$json_obj->description = $variable->description;
$json_obj->service = $service->name;
out(json_encode($json_obj), ESC_NONE);
