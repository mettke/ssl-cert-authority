<?php
$content = new PageSection('error422');
$content->set('parameter', $parameter);

$page = new PageSection('base');
$page->set('title', 'Parameter missing');
$page->set('content', $content);
$page->set('alerts', array());
header('HTTP/1.1 422 Unprocessable Entity');
echo $page->generate();
