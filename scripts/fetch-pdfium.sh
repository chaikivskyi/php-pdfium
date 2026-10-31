#!/bin/sh
set -eu
root=$(cd "$(dirname "$0")/.." && pwd)
version=$(cat "$root/PDFIUM_VERSION")
case "$(uname -s)-$(uname -m)" in
  Linux-x86_64)
    if [ -f /etc/alpine-release ]; then asset=pdfium-linux-musl-x64.tgz; else asset=pdfium-linux-x64.tgz; fi ;;
  Linux-aarch64|Linux-arm64)
    if [ -f /etc/alpine-release ]; then asset=pdfium-linux-musl-arm64.tgz; else asset=pdfium-linux-arm64.tgz; fi ;;
  Darwin-arm64) asset=pdfium-mac-arm64.tgz ;;
  *) echo "unsupported platform: $(uname -s)-$(uname -m)" >&2; exit 1 ;;
esac
url="https://github.com/bblanchon/pdfium-binaries/releases/download/$(printf %s "$version" | sed 's|/|%2F|g')/$asset"
tmp=$(mktemp -d)
trap 'rm -rf "$tmp"' EXIT
curl -fsSL "$url" -o "$tmp/$asset"
expected=$(awk -v a="$asset" '$2 == a { print $1 }' "$root/pdfium.sha256")
[ -n "$expected" ] || { echo "no checksum recorded for $asset" >&2; exit 1; }
actual=$(sha256sum "$tmp/$asset" 2>/dev/null | cut -d' ' -f1 || true)
[ -n "$actual" ] || actual=$(shasum -a 256 "$tmp/$asset" | cut -d' ' -f1)
[ "$expected" = "$actual" ] || { echo "sha256 mismatch for $asset: expected $expected, got $actual" >&2; exit 1; }
rm -rf "$root/src/libpdfium"
mkdir -p "$root/src/libpdfium"
tar -xzf "$tmp/$asset" -C "$root/src/libpdfium"
