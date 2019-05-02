#!/usr/bin/env sh
set -o nounset
set -o errexit
set -o pipefail

sudo httpd -k restart
