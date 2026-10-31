#!/bin/sh
set -eu
cd "$(dirname "$0")/.."
if [ ! -f Makefile ] || [ config.m4 -nt Makefile ]; then
  phpize
  ./configure --with-pdfium="$PWD/src/libpdfium"
fi
make
export TEST_PHP_EXECUTABLE="${TEST_PHP_EXECUTABLE:-$(command -v php)}"
[ $# -gt 0 ] || set -- tests
exec "$TEST_PHP_EXECUTABLE" run-tests.php -q --show-diff -n -d extension="$PWD/modules/pdfium.so" "$@"
