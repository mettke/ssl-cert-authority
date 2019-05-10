<?php
$migration_name = 'Certificate Signing Request';

$this->database->query("
ALTER TABLE `certificate`
    ADD `signing_request` tinyint(1) unsigned NOT NULL DEFAULT '0',
    ADD `csr` text NOT NULL DEFAULT ''
");
