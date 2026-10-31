#!/bin/sh
# Builds an AddressSanitizer PHP into PREFIX. A stock php that loads libasan late resolves malloc and free
# to glibc, so ASan never tracks the USE_ZEND_ALLOC=0 heap; only an ASan-built binary links it first.
# A debug build also collects cycles at shutdown, so objects still alive in a cycle are not reported as leaks.
set -eu
prefix=${1:?usage: ci/php-asan.sh PREFIX}
version=8.4.27
sha256=5bafed9994c1a49796b25b5f61c22c9183bfe046b6acc7b363842d03c77f972f
tmp=$(mktemp -d)
trap 'rm -rf "$tmp"' EXIT
curl -fsSL "https://www.php.net/distributions/php-$version.tar.gz" -o "$tmp/php.tar.gz"
echo "$sha256  $tmp/php.tar.gz" | sha256sum -c -
tar -xzf "$tmp/php.tar.gz" -C "$tmp"
cd "$tmp/php-$version"
./configure --prefix="$prefix" --disable-all --disable-cgi --disable-phpdbg --enable-debug --enable-address-sanitizer
make -j"$(nproc)"
make install
