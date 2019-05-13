# Example: nginx + htpasswd

This Example shows how to use sca with nginx and htpasswd using docker.

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
docker logs -f nginx-local_sca_1
```

## Using sca

_The `cert-sync` user should only be used for the first setup. Afterwards its best to create a dedicated account per user._

1. Login using the admin account `cert-sync`.
1. Create user `rainbow` as admin and user `proceme` as user at http://localhost/users#add
1. Add the server `nginx.example.com` and `httpd.example.com` at http://localhost/servers#add
1. Sca should be able to connect to both systems. You can verify this by checking whether there is an `Synced successfully` next to the servers. 
1. Add the scripts [check_loport.sh](../scripts/check_loport.sh), [restart_httpd.sh](../scripts/httpd/restart_httpd.sh), [restart_nginx.sh](../scripts/nginx/restart_nginx.sh) and [status_pidfile.sh](../scripts/status_pidfile.sh)
1. Add two services called `nginx_8443` and `httpd_8444`. Both should use `check_loport.sh`, `status_pidfile.sh` and their respective restart script
1. Add the following two variables to both services.
   * nginx_8443:
      * Name: `PID_FILE`
      * Value: `/run/nginx/nginx.pid`
      * Name: `PORT`
      * Value: `8443`
   * httpd_8444:
      * Name: `PID_FILE`
      * Value: `/var/run/apache2/httpd.pid`
      * Name: `PORT`
      * Value: `8444`
1. Add the certificates from [certificates](../certificates)
1. Add two profiles. `nginx_demo` with `nginx.example.com` and `nginx_8443` as well as `httpd_demo` with `httpd.example.com` and `httpd_8444`. Both with certifcate `test1`.
1. Go to http://localhost/servers#list and use `Sync listed servers now`. After the confirmation it should only take a few seconds until both servers should be set to `Synced successfully`.
1. Visit https://localhost:8443/ and https://localhost:8444/ and take a look at the certificate it should be valid till 2020-01-01. Now visit [certificate test1](http://localhost/certificates/test1#migrate) and migrate it to `test2`. Afterwards synchronise the servers again. After reloading both pages, the certificate should now be valid till 2021-01-01.

## Add/Change passwords for users

1. Either install `htpasswd` on your system or connect to the nginx container using `docker exec -it nginx-local_sca_1 /bin/ash` and install it there with `apk add apache2-utils`
1. Run `htpasswd` on the htpasswd file. Inside the container it is `htpasswd /allowed_users <username>`

## View synchronisation logs

The Web UI only shows very rudimentary error messages. For more information connect to the php container using `docker exec -it nginx-local_sca-php_1 /bin/ash`. In there, you can use the this command to follow the log file of the synchronisation daemon: `tail -f /var/log/cert/sync.log`.
