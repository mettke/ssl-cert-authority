<?php
$scripts = $this->get('scripts');
$json = array();
foreach($scripts as $script) {
	$json_obj = new StdClass;
	$json_obj->name = $script->name;
	$json_obj->type = $script->type;
	$json[] = $json_obj;
}
out(json_encode($json), ESC_NONE);
