#!/usr/bin/env sh
PID_FILE=${PORT}
if [ -z "${PORT}" ]; then
    (>&2 echo "Variable PORT is required")
    exit 255
fi

set -o nounset
set -o errexit
set -o pipefail

/usr/bin/openssl s_client -showcerts \
	  -servername localhost -connect localhost:$PORT \
	   </dev/null 2>/dev/null \
   | /usr/bin/openssl x509 -noout -serial \
   | /usr/bin/cut -d'=' -f2
   