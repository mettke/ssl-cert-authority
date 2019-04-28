SCA - SSL Cert Authority
=======================

A tool for a Semi-automated deployment of certificates on multiple servers.

This tool is only Semi-automated as it requires a user to start the deployment. The deployment itself is automated and runs on one server at a time. A failure on any given server will stop the whole deployment to allow the user to fix the problem before continuing.

Features
--------

* Manage the deployment of certificates to multiple servers.
* Automatically restart services after deployment.
* Integrate with your LDAP directory service for user authorization.

Demo
----

You can view the SSL Cert Authority in action on the [demonstration server](https://sca-demo.itmettke.de/).

Use one of the following sets of username / password credentials to log in:

* rainbow / password - admin user

All data on this demonstration server is reset nightly at 00:00 UTC.

Requirements
------------

* Apache 2.2 or higher
* PHP 5.6 or higher
* PHP JSON extension
* PHP LDAP extension
* PHP mbstring (Multibyte String) extension
* PHP MySQL extension
* MySQL (5.5+), Percona Server (5.5+) or MariaDB database

Installation
------------

1.  Clone the repo somewhere outside of your default Apache document root.

2.  Add the following directives to your Apache configuration (eg. virtual host config):

        DocumentRoot /path/to/sca/public_html
        DirectoryIndex init.php
        FallbackResource /init.php

3.  Create a MySQL user and database (run in MySQL shell):

        CREATE USER 'sca-user'@'localhost' IDENTIFIED BY 'password';
        CREATE DATABASE `sca-db` DEFAULT CHARACTER SET utf8mb4;
        GRANT ALL ON `sca-db`.* to 'sca-user'@'localhost';

4.  Copy the file `config/config-sample.ini` to `config/config.ini` and edit the settings as required.

5.  Set up authentication for your virtual host. The Auth-user variable must be passed to the application.

6.  Set `scripts/cron.php` to run on a regular cron job.

7.  Generate an SSH key pair to synchronize with. SSL Cert Authority will expect to find the files as `config/cert-sync` and `config/cert-sync.pub` for the private and public keys respectively.

8.  Install the SSH key synchronization daemon. 

    * For systemd:

        1.  Copy `services/systemd/cert-sync.service` to `/etc/systemd/system/`
        2.  Modify `ExecStart` path and `User` as necessary. If SSL Cert Authority is installed under `/home`, disable `ProtectHome`.
        3.  `systemctl daemon-reload`
        4.  `systemctl enable cert-sync.service`

    * For sysv-init:

        1.  Copy `services/init.d/cert-sync` to `/etc/init.d/`
        2.  Modify `SCRIPT` path and `USER` as necessary.
        3.  `update-rc.d cert-sync defaults`

    * Manual:

        1. Make sure that `scripts/syncd.php --user cert-sync` is executed whenever the system is restarted

Usage
-----

If LDAP is enabed anyone who fits the filter under `filter` in `config/config.ini` will be able to login and use the application as admin.

Without LDAP, only the `cert-sync` users will be available after installation. With that user, it is possible to add users.

Certificate distribution
----------------

SSL Cert Authority distributes certificates to your servers via SSH. It does this by:

1.  Connecting to the server with SSH, authorizing as the `cert-sync` user.
2.  Writing the appropriate certificates, profiles, variables, scripts  to files and fodlers in `/var/local/cert-sync/` (eg. all profiles a server is in will be written to `/var/local/cert-sync/profiles/<profile_name>/{fullchain,cert,private}`).

This means that your application will need to be configured to read certificates and private keys from `/var/local/cert-sync/profiles/<profile_name>/{fullchain,cert,private}`.

Screenshots
-----------

### Profile overview
![Profile overview](public_html/screenshot-profiles.png)

### Server listing
![Server listing](public_html/screenshot-servers.png)

### Certificate listing
![Certificate listing](public_html/screenshot-certificates.png)

### Service listing
![Service listing](public_html/screenshot-services.png)

### Script listing
![Script listing](public_html/screenshot-scripts.png)

### User listing
![User listing](public_html/screenshot-users.png)

### Activity log
![Activity log](public_html/screenshot-activity.png)

License
-------

Copyright 2019 Marc Mettke

Licensed under the Apache License, Version 2.0 (the "License");
you may not use this file except in compliance with the License.
You may obtain a copy of the License at

   http://www.apache.org/licenses/LICENSE-2.0

Unless required by applicable law or agreed to in writing, software
distributed under the License is distributed on an "AS IS" BASIS,
WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
See the License for the specific language governing permissions and
limitations under the License.
