Platforms
=========

Supported
---------

CI builds and tests the extension on these platforms with PHP 8.2, 8.3, 8.4 and 8.5 (NTS):

.. list-table::
   :header-rows: 1

   * - System
     - CPU
     - PDFium build
   * - Ubuntu 24.04 (glibc)
     - x86_64
     - ``pdfium-linux-x64.tgz``
   * - Ubuntu 24.04 (glibc)
     - arm64
     - ``pdfium-linux-arm64.tgz``
   * - Alpine (musl)
     - x86_64
     - ``pdfium-linux-musl-x64.tgz``
   * - Alpine (musl)
     - arm64
     - ``pdfium-linux-musl-arm64.tgz``
   * - macOS 14
     - arm64
     - ``pdfium-mac-arm64.tgz``

``scripts/fetch-pdfium.sh`` picks the right build automatically and checks it against
``pdfium.sha256``.


Not supported
-------------

**Thread-safe (ZTS) PHP.** PDFium is not thread-safe, so the build stops with:

.. code-block:: text

   #error "pdfium supports NTS PHP only: PDFium is not thread-safe"

**Windows and Intel macOS.** These are not tested, and ``scripts/fetch-pdfium.sh`` stops with
``unsupported platform``. You can still download the matching PDFium build yourself and pass its
directory to ``--with-pdfium``, at your own risk.

The PDFium directory must contain ``include/fpdfview.h`` and ``lib/``. Otherwise ``configure`` fails
with ``--with-pdfium=DIR must point at a pdfium-binaries tree``.
