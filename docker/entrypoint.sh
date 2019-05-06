#!/usr/bin/env ash
if [ `whoami` == 'cert-sync' ]; then
  if [ ! -r /sca/config/config.ini ]; then
      echo "config.ini not found or incorrect permissions."
      echo "Permissions must be $(id -u cert-sync):$(id -g cert-sync) with at least 400"
      exit 1
  fi
  if [ ! -r /sca/config/cert-sync ]; then
      echo "private key not found or incorrect permissions."
      echo "Permissions must be $(id -u cert-sync):$(id -g nobody) with 440"
      exit 1
  fi
  if [ ! -r /sca/config/cert-sync.pub ]; then
      echo "public key not found or incorrect permissions."
      echo "Permissions must be $(id -u cert-sync):$(id -g nobody) with at least 440"
      exit 1
  fi
  if ! grep "^timeout_util = BusyBox$" /sca/config/config.ini > /dev/null; then
      echo "timeout_util must be set to BusyBox."
      echo "Change it to: timeout_util = BusyBox"
      exit 1
  fi
elif [ $(id -u) = 0 ]; then
  if ! sudo -u cert-sync /entrypoint.sh; then
    exit 1
  fi
  rsync -a --delete /sca/public_html/ /public_html/
  echo "Waiting for database..."
  for i in $(seq 1 10); do 
    if /sca/scripts/apply_migrations.php; then
      echo "Success"
      break
    fi
    echo "Trying again in 1 sec"
    sleep 1
  done

  /usr/sbin/crond
  /sca/scripts/syncd.php --user cert-sync
  /usr/sbin/php-fpm7 -F
else
  echo "Must be executed with root"
fi
