Getting started
===============

What it does
------------

- Open a PDF from a file or a string, with an optional password.
- Read the page count, metadata, permissions, page sizes, rotation and text.
- Render pages to PNG or JPEG at any DPI.
- Split, merge and save documents.

It needs PHP 8.2 to 8.5, NTS, or ZTS on Linux x86_64. No FFI and no ini settings are needed.
See :doc:`platforms` for the supported systems.


Installation
------------

The extension needs three things: libpng, libjpeg, and a prebuilt PDFium from
`pdfium-binaries <https://github.com/bblanchon/pdfium-binaries>`_ (version |pdfium_version|).

1. Install libpng and libjpeg:

   .. code-block:: sh

      # Debian / Ubuntu
      sudo apt-get install -y libpng-dev libjpeg-dev pkg-config

      # Alpine
      apk add libpng-dev libjpeg-turbo-dev pkgconf

      # macOS (Homebrew)
      brew install libpng jpeg-turbo pkg-config

2. Get PDFium. In a checkout of the `repository <https://github.com/chaikivskyi/pdfium>`_, run:

   .. code-block:: sh

      scripts/fetch-pdfium.sh

   This downloads the right build for your platform into ``src/libpdfium/`` and checks its
   checksum. You can also download ``pdfium-<platform>.tgz`` from the release yourself and unpack it.

3. Build and install the extension, either with `PIE <https://github.com/php/pie>`_:

   .. code-block:: sh

      pie install chaikivskyi/pdfium --with-pdfium=/path/to/pdfium

   or from source:

   .. code-block:: sh

      phpize
      ./configure --with-pdfium="$PWD/src/libpdfium"
      make
      sudo make install

4. Add ``extension=pdfium`` to ``php.ini`` if it is not there yet, and check that it loads:

   .. code-block:: sh

      php --ri pdfium

.. note::

   The extension remembers where PDFium's ``lib/`` directory is. Do not move or delete it after
   building.

.. warning::

   ``make clean`` also deletes ``src/libpdfium/lib/libpdfium.so``. Run ``scripts/fetch-pdfium.sh``
   again afterwards.


Usage
-----

All classes are in the ``Pdfium`` namespace. Page numbers start at 0. Every error throws an exception.

Open a document
~~~~~~~~~~~~~~~

.. code-block:: php

   use Pdfium\Document;

   $doc = Document::fromFile('/path/file.pdf');
   $doc = Document::fromFile('/path/secret.pdf', password: 'secret');

   // From a string, for example a file from S3 or a phar archive:
   $doc = Document::fromString(file_get_contents('s3://bucket/report.pdf'));

``fromFile()`` reads through PHP streams as well, so
``Document::fromFile('s3://bucket/report.pdf')`` works too. Either way the whole file is held in PHP
memory.

Read document information
~~~~~~~~~~~~~~~~~~~~~~~~~

.. code-block:: php

   $doc->pageCount();     // 3
   count($doc);           // 3

   $doc->metadata()->title;        // 'Report', or null if the document has no title
   $doc->permissions()->copy;      // false

Work with pages
~~~~~~~~~~~~~~~

.. code-block:: php

   // One page at a time (the same as foreach ($doc->pages() ...)):
   foreach ($doc as $i => $page) {
       $page->width();      // 612.0 (points, 1/72 inch)
       $page->height();     // 792.0
       $page->rotation();   // 0, 90, 180 or 270
       $page->text();       // "Page 1 ..."
   }

   // A single page:
   $page = $doc->page(4);   // the fifth page

Render pages
~~~~~~~~~~~~

.. code-block:: php

   $bitmap = $doc->page(0)->render(dpi: 150);

   $bitmap->width();    // 1240 for A4 at 150 DPI
   $bitmap->height();   // 1754

   $bitmap->savePng('/tmp/page.png');
   $bitmap->saveJpeg('/tmp/page.jpg', quality: 85);

   $png  = $bitmap->toPng();    // as a string
   $jpeg = $bitmap->toJpeg();

See :ref:`rendering` for sizes and limits.

Split, merge and save
~~~~~~~~~~~~~~~~~~~~~

.. code-block:: php

   // Pages 3, 1 and 1 again, in that order:
   $doc->extractPages([2, 0, 0])->save('/tmp/subset.pdf');

   // All pages of A, then all pages of B:
   Document::merge([$docA, $docB])->save('/tmp/all.pdf');

   // An encrypted source must opt in, because the result cannot be encrypted:
   $doc->extractPages([0], removeSecurity: true);

   // As a string:
   $bytes = $doc->toString();

   // Save an encrypted document without its password:
   $doc->save('/tmp/open.pdf', removeSecurity: true);

The new documents keep only the pages: no encryption, bookmarks, viewer preferences or metadata
beyond what PDFium writes, and form fields can no longer be filled in. See :ref:`pdfium-defaults`.

Free a document
~~~~~~~~~~~~~~~

There is no ``close()``. PHP frees a document when nothing uses it any more. Pages keep their
document alive:

.. code-block:: php

   $page = Document::fromFile('/path/file.pdf')->page(0);
   echo $page->text();   // works: the page keeps the document open
   unset($page);         // now the document is freed

Handle errors
~~~~~~~~~~~~~

.. code-block:: php

   use Pdfium\Document;
   use Pdfium\PasswordException;

   try {
       $doc = Document::fromFile($path);
   } catch (PasswordException) {
       $doc = Document::fromFile($path, password: askForPassword());
   } catch (Pdfium\PdfiumException $e) {
       error_log($e->getMessage());   // "Cannot open document: not a PDF or corrupted"
       throw $e;
   }

See :ref:`exceptions` for the full list.


Development
-----------

Run the tests (this builds the extension first if needed):

.. code-block:: sh

   scripts/test.sh                      # all tests
   scripts/test.sh tests/render.phpt    # one test

Or run them inside Docker, with all tools installed:

.. code-block:: sh

   scripts/dev.sh scripts/test.sh
   PDFIUM_ZTS=1 scripts/dev.sh scripts/test.sh   # ZTS PHP, with the thread tests

The thread tests run when the ``parallel`` extension is installed; ``scripts/test.sh`` loads it.

The public API is defined in ``pdfium.stub.php``. ``pdfium_arginfo.h`` is generated from it with
PHP's ``build/gen_stub.php``.

CI builds with ``-Wall -Wextra -Werror``, checks that ``pdfium_arginfo.h`` matches the stub, and
runs the tests on every supported platform and PHP version, and also under Valgrind,
AddressSanitizer and UndefinedBehaviorSanitizer. The Linux jobs run in prebuilt images from
``ci/docker/``, which the ``CI images`` workflow publishes to ``ghcr.io``.

Build these docs:

.. code-block:: sh

   python3 -m venv .venv
   .venv/bin/pip install -r docs/requirements.txt
   .venv/bin/sphinx-build -W -b html docs docs/_build/html


Contributing
------------

Report bugs and send pull requests on `GitHub <https://github.com/chaikivskyi/pdfium>`_. Please include
the output of ``php -v`` and ``php --ri pdfium``, and the PDF that causes the problem if you can share
it.


License
-------

The extension is MIT licensed. PDFium is BSD-3-Clause / Apache-2.0; its license files come with the
prebuilt binaries and must be included if you distribute them.

Thanks to the `PDFium <https://pdfium.googlesource.com/pdfium/>`_ authors,
`pdfium-binaries <https://github.com/bblanchon/pdfium-binaries>`_ for the prebuilt releases, and
`pypdfium2 <https://github.com/pypdfium2-team/pypdfium2>`_, whose docs inspired these.
