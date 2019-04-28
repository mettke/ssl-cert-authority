<?php
$migration_name = 'Initial setup';

$this->database->query("
CREATE TABLE `user` (
	`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
	`uid` varchar(50) NOT NULL,
	`name` varchar(100) NOT NULL,
	`email` varchar(100) NOT NULL,
	`auth_realm` enum('LDAP','local','external') NOT NULL DEFAULT 'local',
	`active` tinyint(1) unsigned NOT NULL DEFAULT '1',
	`developer` tinyint(1) unsigned NOT NULL DEFAULT '0',
	`force_disable` tinyint(1) unsigned NOT NULL DEFAULT '0',
	`csrf_token` binary(128) DEFAULT NULL,
	PRIMARY KEY (`id`),
	UNIQUE KEY `uid` (`uid`),
	KEY `user_uid` (`uid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

$this->database->query("
CREATE TABLE `user_alert` (
	`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
	`user_id` int(10) unsigned NOT NULL,
	`class` varchar(15) NOT NULL,
	`content` mediumtext NOT NULL,
	`escaping` int(10) unsigned NOT NULL DEFAULT '1',
	PRIMARY KEY (`id`),
	KEY `user_alert_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

$this->database->query("
CREATE TABLE `script` (
	`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
	`name` varchar(150) NOT NULL,
	`content` text NOT NULL,
	`type` enum('restart', 'status', 'check') NOT NULL,
	PRIMARY KEY (`id`),
	UNIQUE KEY `name` (`name`),
	KEY `script_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

$this->database->query("
CREATE TABLE `variable` (
	`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
	`service_id` int(10) unsigned NOT NULL,
	`name` varchar(150) NOT NULL,
	`description` text NOT NULL,
	`value` varchar(150) NOT NULL,
	PRIMARY KEY (`id`),
	UNIQUE KEY `name` (`name`, `service_id`),
	KEY `variable_name` (`name`),
	KEY `variable_service_id` (`service_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

$this->database->query("
CREATE TABLE `service` (
	`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
	`name` varchar(150) NOT NULL,
	`restart_script_id` int(10) unsigned DEFAULT NULL,
	`status_script_id` int(10) unsigned DEFAULT NULL,
	`check_script_id` int(10) unsigned DEFAULT NULL,
	PRIMARY KEY (`id`),
	UNIQUE KEY `name` (`name`),
	KEY `service_name` (`name`),
	KEY `service_restart_script_id` (`restart_script_id`),
	KEY `service_status_script_id` (`status_script_id`),
	KEY `service_check_script_id` (`check_script_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

$this->database->query("
CREATE TABLE `certificate` (
	`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
	`name` varchar(150) NOT NULL,
	`private` text NOT NULL,
	`cert` text NOT NULL,
	`fullchain` text NOT NULL,
	`serial` char(40) NOT NULL,
	`expiration` datetime NOT NULL,
	PRIMARY KEY (`id`),
	UNIQUE KEY `name` (`name`),
	KEY `certificate_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

$this->database->query("
CREATE TABLE `profile` (
	`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
	`name` varchar(150) NOT NULL,
	`certificate_id` int(10) unsigned NOT NULL,
	PRIMARY KEY (`id`),
	UNIQUE KEY `name` (`name`),
	KEY `profile_name` (`name`),
	KEY `profile_certificate_id` (`certificate_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

$this->database->query("
CREATE TABLE `server_profile` (
	`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
	`profile_id` int(10) unsigned NOT NULL,
	`server_id` int(10) unsigned NOT NULL,
	PRIMARY KEY (`id`),
	UNIQUE KEY `server_profile_id` (`server_id`, `profile_id`),
	KEY `FK_server_profile_profile` (`profile_id`),
	KEY `FK_server_profile_server` (`server_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

$this->database->query("
CREATE TABLE `service_profile` (
	`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
	`profile_id` int(10) unsigned NOT NULL,
	`service_id` int(10) unsigned NOT NULL,
	PRIMARY KEY (`id`),
	UNIQUE KEY `service_profile_id` (`service_id`, `profile_id`),
	KEY `FK_service_profile_profile` (`profile_id`),
	KEY `FK_service_profile_service` (`service_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

$this->database->query("
CREATE TABLE `server` (
	`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
	`uuid` varchar(36) DEFAULT NULL,
	`hostname` varchar(150) NOT NULL,
	`port` int(10) unsigned NOT NULL DEFAULT 22,
	`ip_address` varchar(64) DEFAULT NULL,
	`rsa_key_fingerprint` char(48) DEFAULT NULL,
	`sync_status` enum('not synced yet', 'sync success', 'sync failure', 'sync warning', 'proposed') NOT NULL DEFAULT 'not synced yet',
	PRIMARY KEY (`id`),
	UNIQUE KEY `hostname` (`hostname`),
	KEY `server_uuid` (`uuid`),
	KEY `server_ip_address` (`ip_address`),
	KEY `server_rsa_key_fingerprint` (`rsa_key_fingerprint`),
	KEY `server_hostname` (`hostname`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

$this->database->query("
CREATE TABLE `server_note` (
	`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
	`server_id` int(10) unsigned NOT NULL,
	`user_id` int(10) unsigned,
	`date` datetime NOT NULL,
	`note` mediumtext NOT NULL,
	PRIMARY KEY (`id`),
	KEY `FK_server_note_server` (`server_id`),
	KEY `FK_server_note_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

$this->database->query("
CREATE TABLE `sync_request` (
	`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
	`server_id` int(10) unsigned NOT NULL,
	`processing` tinyint(1) unsigned NOT NULL DEFAULT '0',
	PRIMARY KEY (`id`),
	UNIQUE KEY `server_id_account_name` (`server_id`),
	KEY `FK_sync_request_server` (`server_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

$this->database->query("
CREATE TABLE `event` (
	`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
	`object_id` int(10) unsigned NOT NULL,
	`actor_id` int(10) unsigned,
	`date` datetime NOT NULL,
	`details` mediumtext NOT NULL,
	`type` enum('User', 'Script', 'Service', 'Certificate', 'Server', 'Profile', 'ServiceVariable') NOT NULL,
	PRIMARY KEY (`id`),
	KEY `event_object_id` (`object_id`),
	KEY `event_actor_id` (`actor_id`),
	KEY `event_type` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

$this->database->query("
ALTER TABLE `user_alert`
	ADD CONSTRAINT `FK_user_alert_user_id` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE
");

$this->database->query("
ALTER TABLE `variable`
	ADD CONSTRAINT `FK_env_service_id` FOREIGN KEY (`service_id`) REFERENCES `service` (`id`) ON DELETE CASCADE
");

$this->database->query("
ALTER TABLE `service`
	ADD CONSTRAINT `FK_service_restart_script` FOREIGN KEY (`restart_script_id`) REFERENCES `script` (`id`) ON DELETE SET NULL,
	ADD CONSTRAINT `FK_service_status_script` FOREIGN KEY (`status_script_id`) REFERENCES `script` (`id`) ON DELETE SET NULL,
	ADD CONSTRAINT `FK_service_check_script` FOREIGN KEY (`check_script_id`) REFERENCES `script` (`id`) ON DELETE SET NULL
");

$this->database->query("
ALTER TABLE `profile`
	ADD CONSTRAINT `FK_profile_certificate` FOREIGN KEY (`certificate_id`) REFERENCES `certificate` (`id`)
");

$this->database->query("
ALTER TABLE `server_profile`
ADD CONSTRAINT `FK_server_profile_profile` FOREIGN KEY (`profile_id`) REFERENCES `profile` (`id`) ON DELETE CASCADE,
	ADD CONSTRAINT `FK_server_profile_server` FOREIGN KEY (`server_id`) REFERENCES `server` (`id`) ON DELETE CASCADE
");

$this->database->query("
ALTER TABLE `service_profile`
ADD CONSTRAINT `FK_service_profile_profile` FOREIGN KEY (`profile_id`) REFERENCES `profile` (`id`) ON DELETE CASCADE,
	ADD CONSTRAINT `FK_service_profile_service` FOREIGN KEY (`service_id`) REFERENCES `service` (`id`) ON DELETE CASCADE
");

$this->database->query("
ALTER TABLE `server_note`
	ADD CONSTRAINT `FK_server_note_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE SET NULL,
	ADD CONSTRAINT `FK_server_note_server` FOREIGN KEY (`server_id`) REFERENCES `server` (`id`) ON DELETE CASCADE
");

$this->database->query("
ALTER TABLE `sync_request`
	ADD CONSTRAINT `FK_sync_request_server` FOREIGN KEY (`server_id`) REFERENCES `server` (`id`) ON DELETE CASCADE
");

$this->database->query("
ALTER TABLE `event`
	ADD CONSTRAINT `event_actor_id` FOREIGN KEY (`actor_id`) REFERENCES `user` (`id`) ON DELETE SET NULL
");

$this->database->query("
INSERT INTO user SET uid = 'cert-sync', name = 'Synchronization script', email = '', auth_realm = 'local'
");
