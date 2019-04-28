<?php
$variables = $this->get('variables');
$json = array();
foreach($variables as $variable) {
	$json_obj = new StdClass;
	$json_obj->name = $variable->name;
	$json_obj->value = $variable->value;
	$json[] = $json_obj;
}
out(json_encode($json), ESC_NONE);
