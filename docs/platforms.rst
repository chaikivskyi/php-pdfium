Platforms
=========

Supported
---------

CI builds and tests the extension on these platforms with PHP 8.2, 8.3, 8.4 and 8.5, NTS and ZTS. macOS runs
on demand only (the ``macOS`` workflow); the others run on every push and pull request.

.. list-table::
   :header-rows: 1

   * - System
     - CPU
     - PDFium build
   * - Debian 13
     - x86_64
     - ``pdfium-linux-x64.tgz``
   * - Debian 13
     - arm64
     - ``pdfium-linux-arm64.tgz``
   * - Alpine
     - x86_64
     - ``pdfium-linux-musl-x64.tgz``
   * - Alpine
     - arm64
     - ``pdfium-linux-musl-arm64.tgz``
   * - macOS 26
     - arm64
     - ``pdfium-mac-arm64.tgz``

``configure`` picks the right build automatically and checks it against
``pdfium.sha256``.


Not supported
-------------

**Windows and Intel macOS.** These are not tested, and ``configure`` stops with ``unsupported platform``
followed by ``cannot download PDFium``. You can still download the matching PDFium build yourself and pass its
directory to ``--with-pdfium``, at your own risk.

The PDFium directory must contain ``include/fpdfview.h`` and ``lib/``. Otherwise ``configure`` fails
with ``--with-pdfium=DIR must point at a pdfium-binaries tree``.
