<?php
$script = $this->get('script');
$json_obj = new StdClass;
$json_obj->name = $script->name;
$json_obj->type = $script->type;
$json_obj->content = $script->content;
out(json_encode($json_obj), ESC_NONE);
