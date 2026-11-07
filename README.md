# pdfium

A native PHP extension for fast PDF work, built on Google's [PDFium](https://pdfium.googlesource.com/pdfium/).
Open, read, render, split and merge PDFs; pages load on demand, so long documents stay cheap.

PHP 8.2 to 8.5, NTS and ZTS, on Linux x86_64 and arm64 and macOS arm64.

**Documentation: <https://php-pdfium.readthedocs.io/>**

## Install

You need libpng and libjpeg first; see
[Installation](https://php-pdfium.readthedocs.io/en/latest/introduction.html#installation) for the
packages per platform. Then fetch PDFium in a checkout of the repository:

```sh
git clone https://github.com/chaikivskyi/php-pdfium
cd php-pdfium
scripts/fetch-pdfium.sh
```

Install with [PIE](https://github.com/php/pie), from the checkout:

```sh
pie install chaikivskyi/php-pdfium --with-pdfium="$PWD/src/libpdfium"
```

Or build from source:

```sh
phpize
./configure --with-pdfium="$PWD/src/libpdfium"
make
sudo make install
```

`make clean` also deletes `src/libpdfium/lib/libpdfium.so`; run `scripts/fetch-pdfium.sh` again
afterwards.

Add `extension=pdfium` to `php.ini` if it is not there yet. The extension loads PDFium at run time from
the `lib/` directory under `--with-pdfium`, so keep that directory where it is.

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
