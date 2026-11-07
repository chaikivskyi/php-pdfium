Getting started
===============

What it does
------------

- Open a PDF from a file or a string, with an optional password.
- Read the page count, metadata, permissions, page sizes, rotation and text.
- Render pages to PNG or JPEG at any DPI.
- Split, merge and save documents.

It needs PHP 8.2 to 8.5, NTS or ZTS. No FFI and no ini settings are needed.
See :doc:`platforms` for the supported systems.


Installation
------------

The extension needs libpng, libjpeg, and a prebuilt PDFium from
`pdfium-binaries <https://github.com/bblanchon/pdfium-binaries>`_ (version |pdfium_version|). The
build downloads PDFium for you.

1. Install libpng and libjpeg:

   .. code-block:: sh

      # Debian / Ubuntu
      sudo apt-get install -y libpng-dev libjpeg-dev pkg-config

      # Alpine
      apk add libpng-dev libjpeg-turbo-dev pkgconf

      # macOS (Homebrew)
      brew install libpng jpeg-turbo pkg-config

2. Build and install the extension, either with `PIE <https://github.com/php/pie>`_:

   .. code-block:: sh

      pie install chaikivskyi/php-pdfium

   or from a checkout of the `repository <https://github.com/chaikivskyi/php-pdfium>`_:

   .. code-block:: sh

      phpize
      ./configure
      make
      sudo make install

   ``configure`` downloads the PDFium build for your platform into ``src/libpdfium/`` and checks
   it against ``pdfium.sha256``. ``make install`` copies ``libpdfium.so`` (``libpdfium.dylib`` on
   macOS) next to ``pdfium.so`` in PHP's extension directory, where the extension finds it, so the
   build directory can be deleted afterwards.

3. Add ``extension=pdfium`` to ``php.ini`` if it is not there yet, and check that it loads:

   .. code-block:: sh

      php --ri pdfium

To build without internet access, download ``pdfium-<platform>.tgz`` from the pdfium-binaries
release yourself, unpack it so that ``include/`` and ``lib/`` sit side by side, and pass its
directory with ``--with-pdfium=DIR`` to ``pie install`` or ``./configure``.

.. warning::

   ``make clean`` also deletes ``src/libpdfium/lib/libpdfium.so``. Run ``./configure`` again
   afterwards to download it again.


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

Without the scripts, run PHP's test runner directly. On ZTS PHP add ``-d extension=parallel`` so the
thread tests are not skipped:

.. code-block:: sh

   php run-tests.php -n -d extension=$PWD/modules/pdfium.so tests

The public API is defined in ``pdfium.stub.php``. ``pdfium_arginfo.h`` is generated from it with
PHP's ``build/gen_stub.php``.

CI builds with ``-Wall -Wextra -Werror``, checks that ``pdfium_arginfo.h`` matches the stub, and
runs the tests on every supported platform and PHP version, and also under Valgrind,
AddressSanitizer and UndefinedBehaviorSanitizer. The Linux jobs run in prebuilt images from
``ci/docker/``, which the ``CI images`` workflow publishes to ``ghcr.io``.

PDFium is pinned by ``PDFIUM_VERSION`` and ``pdfium.sha256``. ``scripts/update-pdfium.sh`` moves
both to the latest pdfium-binaries release, or to the one given as its argument. The ``PDFium
update`` workflow runs it on the first day of each month and opens a pull request when there is a
new release. It uses the ``PDFIUM_BOT_TOKEN`` secret, a fine-grained token with contents and pull
request write access to this repository, so that CI runs on that pull request.

Build these docs:

.. code-block:: sh

   python3 -m venv .venv
   .venv/bin/pip install -r docs/requirements.txt
   .venv/bin/sphinx-build -W -b html docs docs/_build/html


Contributing
------------

Report bugs and send pull requests on `GitHub <https://github.com/chaikivskyi/php-pdfium>`_. Please include
the output of ``php -v`` and ``php --ri pdfium``, and the PDF that causes the problem if you can share
it.


License
-------

The extension is MIT licensed. PDFium is BSD-3-Clause / Apache-2.0; its license files come with the
prebuilt binaries and must be included if you distribute them.

Thanks to the `PDFium <https://pdfium.googlesource.com/pdfium/>`_ authors,
`pdfium-binaries <https://github.com/bblanchon/pdfium-binaries>`_ for the prebuilt releases, and
`pypdfium2 <https://github.com/pypdfium2-team/pypdfium2>`_, whose docs inspired these.
