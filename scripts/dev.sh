#!/bin/sh
set -eu
cd "$(dirname "$0")/.."
docker image inspect php-pdfium-dev >/dev/null 2>&1 || docker build -q -f Dockerfile.dev -t php-pdfium-dev .
exec docker run --rm --user "$(id -u):$(id -g)" -e HOME=/tmp -v "$PWD":/src -w /src php-pdfium-dev "$@"
