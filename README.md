# pdfium

A native PHP extension for fast PDF work, built on Google's [PDFium](https://pdfium.googlesource.com/pdfium/).

Pure-PHP PDF libraries decode a whole document into PHP objects before you can touch a page, so time
and memory grow with page count. `pdfium` binds a C engine instead: pages load on demand, and walking
a 160-page document does not hold 160 pages in memory.

- Open a PDF from a path or a string, with an optional password.
- Page count, metadata, permissions, and per-page size, rotation and UTF-8 text.
- Render pages to PNG or JPEG at any DPI, with form fields and annotations drawn.
- Split, merge and save.
- No FFI, and no ini setting beyond `extension=pdfium`.

PHP 8.2 to 8.5, NTS only. Linux x86_64 and arm64 (glibc and Alpine/musl) and macOS arm64.

## Install

Source: <https://github.com/chaikivskyi/pdfium>.

The extension links a prebuilt PDFium from [bblanchon/pdfium-binaries](https://github.com/bblanchon/pdfium-binaries),
pinned to the release in `PDFIUM_VERSION`. It also needs libpng and libjpeg.

Prerequisites:

```sh
# Debian / Ubuntu
sudo apt-get install -y libpng-dev libjpeg-dev pkg-config

# Alpine
apk add libpng-dev libjpeg-turbo-dev pkgconf

# macOS (Homebrew)
brew install libpng jpeg-turbo pkg-config
```

Get PDFium. In a checkout, `scripts/fetch-pdfium.sh` downloads the pinned release for your platform
into `src/libpdfium/` and verifies it against `pdfium.sha256`. Otherwise download the matching
`pdfium-<platform>.tgz` from the release by hand and unpack it so that `include/` and `lib/` sit
side by side.

Then install with [PIE](https://github.com/php/pie):

```sh
pie install chaikivskyi/pdfium --with-pdfium=/path/to/pdfium
```

The extension records the absolute path of `lib/` as a run-time library path, so keep that directory
where it is.

Add `extension=pdfium` to your `php.ini` if PIE did not do it for you.

### Building from source

```sh
scripts/fetch-pdfium.sh
phpize
./configure --with-pdfium="$PWD/src/libpdfium"
make
php -d extension=$PWD/modules/pdfium.so --ri pdfium
```

phpize's `make clean` runs `find . -name '*.so'`, which deletes
`src/libpdfium/lib/libpdfium.so`. After a `make clean`, run `scripts/fetch-pdfium.sh` again to
restore it.

Run the tests with `scripts/test.sh` (or `php run-tests.php -n -d extension=$PWD/modules/pdfium.so tests`).
`scripts/dev.sh` runs any command inside a Docker image with the toolchain installed.

## Usage

```php
use Pdfium\Document;

$doc = Document::open('/path/file.pdf', password: null);
$doc = Document::fromString($bytes, password: null);

$doc->pageCount();   // int; no page is parsed to answer it
count($doc);         // the same, via Countable
$doc->metadata();    // array<string, string>: non-empty entries only, dates in raw "D:..." form
$doc->permissions(); // array<string, bool>: reported, never enforced

foreach ($doc as $page) {              // lazy: one page loaded at a time; same as $doc->pages()
    $page->index();                    // 0-based
    $page->width();                    // float, points, crop box after rotation
    $page->height();
    $page->rotation();                 // 0, 90, 180 or 270
    $text = $page->text();             // UTF-8

    $bitmap = $page->render(dpi: 150); // Pdfium\Bitmap
    $bitmap->width();                  // pixels
    $bitmap->height();
    $bitmap->savePng('/tmp/p.png');
    $bitmap->saveJpeg('/tmp/p.jpg', quality: 85);
    $png = $bitmap->toPng();           // string
    $jpg = $bitmap->toJpeg(quality: 85);
}

$page = $doc->page(4);                 // random access, 0-based

$doc->extractPages([0, 1, 4])->save('/tmp/subset.pdf');   // a new Document
Document::merge([$docA, $docB])->save('/tmp/all.pdf');
$bytes = $doc->toString();
$doc->save('/tmp/open.pdf', removeSecurity: true);
```

Page indexes are 0-based everywhere. There is no `close()`: a document is freed when the last
reference to it, including those held by its `Page` objects, goes away. `unset($doc)` is the idiom.
A `Page` keeps its `Document` alive, and a document from `fromString()` keeps the string alive.
`extractPages()` and `merge()` copy the pages, so their results do not keep the sources alive.

`Pdfium\Document`, `Page`, `PageIterator` and `Bitmap` are final and cannot be constructed with
`new`, cloned or serialized. Full signatures are in `pdfium.stub.php`.

## Exceptions

Every failure throws. Nothing returns `false` and nothing raises a warning.

| Class | When |
|---|---|
| `Pdfium\Exception` | Base class; extends `\RuntimeException`. Also thrown when PDFium cannot import, serialise or encode. |
| `Pdfium\FileException` | The file cannot be read or written, or `open_basedir` forbids it. |
| `Pdfium\FormatException` | Not a PDF, or corrupt beyond what PDFium recovers. |
| `Pdfium\PasswordException` | A password is required, or the one given is wrong. |
| `Pdfium\UnsupportedException` | A security handler or feature PDFium does not support. |
| `Pdfium\MemoryException` | A render would not fit under `memory_limit`. |

A bad argument throws `\ValueError` (index out of range, empty list, DPI or quality out of range, empty
bytes) or `\TypeError` (a non-int index, a non-`Document` in a merge list).

## Behaviour that follows PDFium

Where PDFium has a default, the extension exposes it and adds no policy of its own.

- **Permissions are reported, never enforced.** `permissions()` returns the document's flags. No
  method refuses to work because of them.
- **`save()` and `toString()` keep the source document's encryption** unless you pass
  `removeSecurity: true`, which writes an unencrypted copy.
- **Split and merge output is unencrypted and carries no metadata, no viewer preferences, no
  bookmarks and no working form fields.** `extractPages()` and `merge()` use PDFium's page import.
  Pages and their content carry over; PDFium has no API to set metadata or to add encryption, and
  merged form fields render but no longer work.
- **A failed `save()` removes the partial file.**
- `save()`, `savePng()` and `saveJpeg()` write through PHP streams, so `open_basedir` and stream
  wrappers apply.

## Rendering

`render(int $dpi = 150)` draws the page as a viewer would:

- white background, no alpha channel;
- the crop box, at its size after rotation;
- form fields and annotations drawn;
- each side is `max(1, round(points * dpi / 72))` pixels.

Limits:

- DPI must be between 1 and 2400, otherwise `\ValueError`.
- A side over 65,500 pixels (the JPEG maximum) or an image over 100,000,000 pixels throws
  `\ValueError`. These limits are not configurable.
- If `memory_limit` is set and its headroom is below roughly 7 bytes per pixel (the bitmap plus the
  encoder's buffers), `render()` throws `MemoryException` instead of risking a fatal error. The bitmap
  itself is allocated outside the Zend allocator.
- JPEG quality must be between 1 and 100, otherwise `\ValueError`.

## NTS only

PDFium keeps one process-wide library state and is not thread-safe. A build against a ZTS PHP fails
with an `#error`: `pdfium supports NTS PHP only: PDFium is not thread-safe`, and `composer.json` declares
`support-zts: false`.

## Licence

MIT, see `LICENSE`. PDFium is BSD-3-Clause / Apache-2.0; its `LICENSE` and the licences of its bundled
components come with the prebuilt binaries (in `src/libpdfium/` after `scripts/fetch-pdfium.sh`) and must
accompany any distribution that bundles them.
