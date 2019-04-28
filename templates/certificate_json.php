<?php
$certificate = $this->get('certificate');
$json_obj = new StdClass;
$json_obj->name = $certificate->name;
$json_obj->cert = $certificate->cert;
$json_obj->fullchain = $certificate->fullchain;
$json_obj->serial = $certificate->serial;
$json_obj->expiration = strtotime($certificate->expiration);
out(json_encode($json_obj), ESC_NONE);
