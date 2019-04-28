# Example: httpd + htpasswd

This Example shows how to use sca with httpd and ldap using docker.

## Prepare setup

1. Start system using `docker-compose up -d`
1. Visit http://localhost
1. Login using one of the following credentials (Only cert-sync account exists at first):

|Username|Password|Type|
|---|---|---|
|cert-sync|password|admin|
|rainbow|password|admin|
|proceme|password|user|

If something goes wrong, check the log using:
```
docker logs -f httpd-local_sca_1
```

## Using sca

_The `cert-sync` user should only be used for the first setup. Afterwards its best to create a dedicated account per user._

1. Login using the admin account `cert-sync`.
1. Create user `rainbow` as admin and user `proceme` as user at http://localhost/users#add
1. Add the server `test.example.com` at http://localhost/servers#add
1. Sca should be able to connet to the system and update its authorized_keys file. You can verify this by checking whether there is an `Synced successfully` next to the server. 

## Add/Change passwords for users

1. Either install `htpasswd` on your system or connect to the httpd container using `docker exec -it httpd-local_sca_1 /bin/ash`.
1. Run `htpasswd` on the htpasswd file. Inside the container it is `htpasswd /allowed_users <username>`
