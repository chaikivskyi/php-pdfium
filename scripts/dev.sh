#!/bin/sh
set -eu
cd "$(dirname "$0")/.."
if [ "${PDFIUM_ZTS:-}" = 1 ]; then
	image=php-pdfium-dev-zts
	base=php:8.4-zts-bookworm
	parallel=1.2.15
else
	image=php-pdfium-dev
	base=php:8.4-cli-bookworm
	parallel=
fi
docker image inspect "$image" >/dev/null 2>&1 || docker build -q --build-arg PHP_IMAGE="$base" --build-arg PARALLEL_VERSION="$parallel" -f Dockerfile.dev -t "$image" .
exec docker run --rm --user "$(id -u):$(id -g)" -e HOME=/tmp -v "$PWD":/src -w /src "$image" "$@"
