#!/bin/sh
set -eu
cd "$(dirname "$0")/.."
export TEST_PHP_EXECUTABLE="${TEST_PHP_EXECUTABLE:-$(command -v php)}"
bindir=$(dirname "$TEST_PHP_EXECUTABLE")
phpize=phpize
php_config=php-config
if [ -x "$bindir/phpize" ] && [ -x "$bindir/php-config" ]; then
  phpize="$bindir/phpize"
  php_config="$bindir/php-config"
fi
want=$("$TEST_PHP_EXECUTABLE" -n -r 'echo PHP_EXTENSION_DIR;')
have=$(sed -n 's/^EXTENSION_DIR = //p' Makefile 2>/dev/null || true)
if [ ! -f Makefile ] || [ config.m4 -nt Makefile ] || [ "$have" != "$want" ]; then
  rm -rf modules .libs src/.libs ./*.lo ./*.la ./*.dep src/*.lo src/*.dep
  "$phpize"
  ./configure --with-php-config="$php_config" --with-pdfium="$PWD/src/libpdfium"
  have=$(sed -n 's/^EXTENSION_DIR = //p' Makefile)
  if [ "$have" != "$want" ]; then
    echo "$phpize builds for $have, but $TEST_PHP_EXECUTABLE needs $want" >&2
    exit 1
  fi
fi
make
[ $# -gt 0 ] || set -- tests
if ! "$TEST_PHP_EXECUTABLE" -n -r 'exit(extension_loaded("parallel") ? 0 : 1);' >/dev/null 2>&1 \
  && "$TEST_PHP_EXECUTABLE" -n -d extension=parallel -r 'exit(extension_loaded("parallel") ? 0 : 1);' >/dev/null 2>&1; then
  set -- -d extension=parallel "$@"
fi
exec "$TEST_PHP_EXECUTABLE" run-tests.php -q --show-diff -n -d extension="$PWD/modules/pdfium.so" "$@"
