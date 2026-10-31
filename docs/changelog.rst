Changelog
=========

0.1.0 (unreleased)
------------------

First release, built against PDFium |pdfium_version|.

- Open PDFs from a file or a string, with an optional password.
- Read the page count, metadata, permissions, page sizes, rotation and text.
- Iterate over pages lazily, one at a time, with ``foreach ($doc as $page)`` or ``pages()``.
- Render pages to PNG or JPEG at 1 to 2400 DPI.
- Split, merge and save documents, with an option to remove encryption.
- Throw exceptions for every error, including :php:exc:`Pdfium\\MemoryException` instead of a fatal
  error when ``memory_limit`` would be exceeded.
- Support PHP 8.2 to 8.5 (NTS) on Linux x86_64 and arm64 (glibc and musl) and macOS arm64.
- Install with PIE.
