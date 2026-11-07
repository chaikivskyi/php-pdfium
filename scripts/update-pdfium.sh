#!/bin/sh
set -eu
root=$(cd "$(dirname "$0")/.." && pwd)
api() {
  if [ -n "${GH_TOKEN:-}" ]; then
    curl -fsSL -H "Authorization: Bearer $GH_TOKEN" "$1"
  else
    curl -fsSL "$1"
  fi
}
version=${1:-$(api https://api.github.com/repos/bblanchon/pdfium-binaries/releases/latest | sed -n 's/^ *"tag_name": *"\([^"]*\)".*/\1/p')}
[ -n "$version" ] || { echo "cannot find the latest pdfium-binaries release" >&2; exit 1; }
if [ "$version" = "$(cat "$root/PDFIUM_VERSION")" ]; then
  echo "PDFium is already at $version"
  exit 0
fi
tag=$(printf %s "$version" | sed 's|/|%2F|g')
tmp=$(mktemp -d)
trap 'rm -rf "$tmp"' EXIT
for asset in $(awk '{ print $2 }' "$root/pdfium.sha256"); do
  curl -fsSL "https://github.com/bblanchon/pdfium-binaries/releases/download/$tag/$asset" -o "$tmp/$asset"
  sum=$(sha256sum "$tmp/$asset" 2>/dev/null | cut -d' ' -f1 || true)
  [ -n "$sum" ] || sum=$(shasum -a 256 "$tmp/$asset" | cut -d' ' -f1)
  printf '%s  %s\n' "$sum" "$asset" >> "$tmp/pdfium.sha256"
done
mv "$tmp/pdfium.sha256" "$root/pdfium.sha256"
printf '%s\n' "$version" > "$root/PDFIUM_VERSION"
echo "PDFium updated to $version"
