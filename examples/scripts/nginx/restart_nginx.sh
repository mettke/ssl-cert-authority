#!/usr/bin/env sh
PID_FILE=${PID_FILE}
if [ -z "${PID_FILE}" ]; then
    (>&2 echo "Variable PID_FILE is required")
    exit 255
fi

set -o nounset
set -o errexit
set -o pipefail

if [ -f "${PID_FILE}" ]; then
    PID=$(cat ${PID_FILE})
    if [ -n "${PID}" -a -d "/proc/${PID}" ]; then
        sudo nginx -s reload
        exit 0
    fi
fi
sudo nginx
