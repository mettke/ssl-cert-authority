<?php
$migration_name = 'Unpriviledged User';

$this->database->query("
ALTER TABLE `user`
    ADD `admin` tinyint(1) unsigned NOT NULL DEFAULT '0';
");

$this->database->query("
UPDATE `user`
    SET admin = 1;
");

$this->database->query("
ALTER TABLE `certificate`
    ADD `owner_id` int(10) unsigned,
	ADD CONSTRAINT `FK_certificate_owner_id` FOREIGN KEY (`owner_id`) REFERENCES `user` (`id`) ON DELETE SET NULL;
");
