# Example: nginx + client certificate

This Example shows how to use sca with nginx and client certificate using docker.

## Prepare setup

1. Start system using `docker-compose up -d`
1. Add pfx [certificates](../shared/config-client-cert/) to your browser
1. Visit https://localhost
1. Login using one of the following credentials (Only cert-sync account exists at first):

|Username|Type|
|---|---|
|cert-sync|admin|
|rainbow|admin|
|proceme|user|

If something goes wrong, check the log using:
```
docker logs -f nginx-client-cert_sca_1
```

## Using sca

_The `cert-sync` user should only be used for the first setup. Afterwards its best to create a dedicated account per user._

1. Login using the admin account `cert-sync`.
1. Create user `rainbow` as admin and user `proceme` as user at https://localhost/users#add
1. Add the server `nginx.example.com` and `httpd.example.com` at https://localhost/servers#add
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
1. Go to https://localhost/servers#list and use `Sync listed servers now`. After the confirmation it should only take a few seconds until both servers should be set to `Synced successfully`.
1. Visit https://localhost:8443/ and https://localhost:8444/ and take a look at the certificate it should be valid till 2020-01-01. Now visit [certificate test1](https://localhost/certificates/test1#migrate) and migrate it to `test2`. Afterwards synchronise the servers again. After reloading both pages, the certificate should now be valid till 2021-01-01.

## Add access for new users

1. Generate private key: `openssl genrsa -out <username>.key 2048`
1. Generate certificate signing request: `openssl req -new -key <username>.key -out <username>.csr -subj "/CN=<username>"`
1. Generate certificate using the ca: `openssl x509 -req -in <username>.csr -CA ca.pem -CAkey ca.key -CAcreateserial -out <username>.pem -days 36500 -sha256`
1. Generate pfx: `openssl pkcs12 -export -out <username>.pfx -inkey <username>.key -in <username>.pem -certfile ca.pem`

## View synchronisation logs

The Web UI only shows very rudimentary error messages. For more information connect to the php container using `docker exec -it nginx-client-cert_sca-php_1 /bin/ash`. In there, you can use the this command to follow the log file of the synchronisation daemon: `tail -f /var/log/cert/sync.log`.
