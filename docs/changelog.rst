Changelog
=========

0.1.0 (2026-11-07)
------------------

First release, built against PDFium |pdfium_version|.

Features:

- Open PDFs from a file or a string, with an optional password.
- Read the page count, metadata, permissions, page sizes, rotation and text.
- Iterate over pages.
- Render pages to PNG or JPEG.
- Split, merge and save documents.

Supported:

- PHP 8.2 to 8.5, NTS and ZTS.
- Linux x86_64 and arm64 (glibc and musl), macOS arm64.
- Installation with ``pie install chaikivskyi/php-pdfium``.
