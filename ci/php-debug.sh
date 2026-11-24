#!/bin/sh
# Builds a debug PHP into PREFIX, with AddressSanitizer and UndefinedBehaviorSanitizer (asan) or with Valgrind support (valgrind).
# A stock php that loads libasan late resolves malloc and free to glibc, so ASan never tracks the USE_ZEND_ALLOC=0 heap; only an
# ASan-built binary links it first.
# A debug build also collects cycles at shutdown, so objects still alive in a cycle are not reported as leaks.
set -eu
usage="usage: ci/php-debug.sh PREFIX asan|valgrind [zts]"
prefix=${1:?$usage}
tool=${2:?$usage}
zts=${3:-}
case "$tool" in
  asan) tool_flags="--enable-address-sanitizer --enable-undefined-sanitizer" parallel_flags="-fsanitize=address" ;;
  valgrind) tool_flags="--with-valgrind" parallel_flags= ;;
  *) echo "$usage" >&2; exit 1 ;;
esac
case "$zts" in
  ''|zts) ;;
  *) echo "$usage" >&2; exit 1 ;;
esac
version=8.4.27
sha256=5bafed9994c1a49796b25b5f61c22c9183bfe046b6acc7b363842d03c77f972f
parallel_version=1.2.15
parallel_sha256=27c4b2ca4bfa67efebe84541ac4d25547ba75b603139c8f0e136780d080fb924
tmp=$(mktemp -d)
trap 'rm -rf "$tmp"' EXIT
curl -fsSL "https://www.php.net/distributions/php-$version.tar.gz" -o "$tmp/php.tar.gz"
echo "$sha256  $tmp/php.tar.gz" | sha256sum -c -
tar -xzf "$tmp/php.tar.gz" -C "$tmp"
cd "$tmp/php-$version"
./configure --prefix="$prefix" --disable-all --disable-cgi --disable-phpdbg --enable-debug $tool_flags \
  --with-zlib --with-openssl --enable-posix ${zts:+--enable-zts}
make -j"$(nproc)"
make install
[ -n "$zts" ] || exit 0
curl -fsSL "https://pecl.php.net/get/parallel-$parallel_version.tgz" -o "$tmp/parallel.tgz"
echo "$parallel_sha256  $tmp/parallel.tgz" | sha256sum -c -
tar -xzf "$tmp/parallel.tgz" -C "$tmp"
cd "$tmp/parallel-$parallel_version"
"$prefix/bin/phpize"
./configure --with-php-config="$prefix/bin/php-config" \
  CFLAGS="$parallel_flags -fno-omit-frame-pointer -g" LDFLAGS="$parallel_flags"
make -j"$(nproc)"
make install
