# pdfium

A native PHP extension for fast PDF work, built on Google's [PDFium](https://pdfium.googlesource.com/pdfium/).
Open, read, render, split and merge PDFs; pages load on demand, so long documents stay cheap.

PHP 8.2 to 8.5, NTS and ZTS, on Linux x86_64 and arm64 and macOS arm64.

**Documentation: <https://php-pdfium.readthedocs.io/>**

## Install

You need libpng and libjpeg first:

```sh
# Debian / Ubuntu
sudo apt-get install -y libpng-dev libjpeg-dev pkg-config

# Alpine
apk add libpng-dev libjpeg-turbo-dev pkgconf

# macOS (Homebrew)
brew install libpng jpeg-turbo pkg-config
```

Then install with [PIE](https://github.com/php/pie):

```sh
pie install chaikivskyi/php-pdfium
```

The build downloads the pinned PDFium release for your platform and checks its checksum, and the
install puts it next to the extension. Without internet access, download a
[pdfium-binaries](https://github.com/bblanchon/pdfium-binaries) release yourself and pass
`--with-pdfium=DIR`.

Or build from source:

```sh
git clone https://github.com/chaikivskyi/php-pdfium
cd php-pdfium
phpize
./configure
make
sudo make install
```

Add `extension=pdfium` to `php.ini` if it is not there yet.

## Usage

Extract text and render every page:

```php
use Pdfium\Document;

$doc = Document::fromFile('/path/file.pdf');

foreach ($doc as $page) {
    echo $page->text();
    $page->render(dpi: 150)->savePng("/tmp/page-{$page->index()}.png");
}
```

Split and merge:

```php
$doc->extractPages([0, 1, 4])->save('/tmp/subset.pdf');
Document::merge([$docA, $docB])->save('/tmp/all.pdf');
```

## Licence

MIT, see `LICENSE`. PDFium is BSD-3-Clause / Apache-2.0; its licence files come with the prebuilt
binaries and must accompany any distribution that bundles them.
