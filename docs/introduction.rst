Getting started
===============

What it does
------------

- Open a PDF from a file or a string, with an optional password.
- Read the page count, metadata, permissions, file identifiers, page sizes, rotation and text.
- Read the outline (bookmarks), link annotations and page open and close actions, with where they
  lead.
- List the scripts in the document's JavaScript name tree, without running them.
- Find text on a page: search it, read the text inside a rectangle, list the URLs written in it,
  and get every character with its position, font and colour.
- Render pages, or part of a page, to PNG or JPEG at 1 to 2400 DPI, in colour or gray, on white or
  transparent.
- List, extract, add and delete embedded files (attachments).
- List digital signatures and read their details.
- Read whether a document is tagged, and read and set its language.
- List, read, add, change and remove annotations, such as highlights, notes and links. Change and
  remove form fields as Widget annotations; you cannot add them.
- Read and fill in form fields (text fields, checkboxes, radio buttons, combo and list boxes), and
  flatten a page's annotations and fields into page content.
- Edit pages: delete, add, copy in from another document, move, rotate and set their boxes.
- Split and merge documents, put several pages on one sheet, start an empty document, and save,
  incrementally if you want to keep the original file.
- Open large local files without loading them whole into memory.

Requirements:

=====================  ==========================================================================
PHP                    8.2 to 8.5, NTS or ZTS. No FFI.
Platforms              See :doc:`platforms`.
``pdfium.font_paths``  Optional, the only ini setting. Tells PDFium where to find fonts; see
                       :ref:`fonts`.
=====================  ==========================================================================


Installation
------------

You need libpng, libjpeg, and a prebuilt PDFium from
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
   it against ``pdfium.sha256``.

   ``make install`` copies ``libpdfium.so`` (``libpdfium.dylib`` on macOS) next to ``pdfium.so``
   in PHP's extension directory, where the extension finds it. You can delete the build directory
   afterwards.

3. Add ``extension=pdfium`` to ``php.ini`` if it is not there yet, and check that it loads:

   .. code-block:: sh

      php --ri pdfium

To build without internet access, download ``pdfium-<platform>.tgz`` from the pdfium-binaries
release yourself. Unpack it so that ``include/`` and ``lib/`` sit side by side, and pass its
directory with ``--with-pdfium=DIR`` to ``pie install`` or ``./configure``.

.. warning::

   ``make clean`` also deletes ``src/libpdfium/lib/libpdfium.so``. Run ``./configure`` again
   afterwards to download it again.


Usage
-----

Every class lives in the ``Pdfium`` namespace. Page numbers start at 0. Every error throws an
exception.

Open a document
~~~~~~~~~~~~~~~

.. code-block:: php

   use Pdfium\Document;

   $doc = Document::fromFile('/path/file.pdf');
   $doc = Document::fromFile('/path/secret.pdf', password: 'secret');
   $doc = Document::fromFile('/path/huge.pdf', lazy: true);   // read as needed, not loaded whole

   // From a string, for example a file from S3 or a phar archive:
   $doc = Document::fromString(file_get_contents('s3://bucket/report.pdf'));

``fromFile()`` reads through PHP streams too, so ``Document::fromFile('s3://bucket/report.pdf')``
works. Either way PHP holds the whole file in memory. Pass ``lazy: true`` for a local file to avoid
that.

Read document information
~~~~~~~~~~~~~~~~~~~~~~~~~

.. code-block:: php

   $doc->pageCount();     // 3
   count($doc);           // 3

   $doc->metadata()->title;        // 'Report', or null if the document has no title
   $doc->permissions()->copy;      // false

   $doc->bookmarks();              // Bookmark[]: the outline, each with title, destination, children
   $doc->javaScripts();            // JavaScript[]: the named scripts, each with name and source

Work with pages
~~~~~~~~~~~~~~~

.. code-block:: php

   // One page at a time (the same as foreach ($doc->pages() ...)):
   foreach ($doc as $i => $page) {
       $page->width();      // 612.0 (points, 1/72 inch)
       $page->height();     // 792.0
       $page->rotation();   // 0, 90, 180 or 270
       $page->text();       // "Page 1 ..."
       $page->links();      // Link[]: the clickable areas, with where each one leads
   }

   // A single page:
   $page = $doc->page(4);   // the fifth page

Find text on a page
~~~~~~~~~~~~~~~~~~~

.. code-block:: php

   use Pdfium\Rect;

   $text = $doc->page(0)->textPage();   // reads the page's text once

   foreach ($text->search('invoice') as $match) {
       $match->text;       // 'Invoice', as written on the page
       $match->rects[0];   // a Rect to highlight, in points
   }

   $text->textIn(new Rect(left: 400, bottom: 90, right: 560, top: 110));   // '1 240.00'
   $text->chars()[0]->fontName;   // 'Helvetica'
   $text->webLinks()[0]->url;     // 'https://example.com'

See :ref:`text-positions` for the coordinates and how characters are counted.

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

   // Just the total, at 300 DPI, gray on a transparent background:
   $total = new Rect(left: 400, bottom: 90, right: 560, top: 110);
   $doc->page(0)->render(dpi: 300, region: $total, transparent: true, grayscale: true)
       ->savePng('/tmp/total.png');

See :ref:`rendering` for the options, sizes and limits.

Create, split, merge, put pages on a sheet and save
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. code-block:: php

   // Pages 3, 1 and 1 again, in that order:
   $doc->extractPages([2, 0, 0])->save('/tmp/subset.pdf');

   // All pages of A, then all pages of B:
   Document::merge([$docA, $docB])->save('/tmp/all.pdf');

   // Two pages side by side on each landscape A4 sheet:
   $doc->nUp(842, 595, 2, 1)->save('/tmp/2-up.pdf');

   // A new document with one blank A4 page:
   $blank = Document::create();
   $blank->addPage(595, 842);

   // An encrypted source must opt in, because the result cannot be encrypted:
   $doc->extractPages([0], removeSecurity: true);

   // As a string:
   $bytes = $doc->toString();

   // Save an encrypted document without its password:
   $doc->save('/tmp/open.pdf', removeSecurity: true);

   // Keep the original file and append the changes:
   $doc->save('/tmp/appended.pdf', incremental: true);

   // Write a PDF 1.4 header:
   $doc->save('/tmp/old.pdf', version: 14);

The new documents keep only the pages. They get no encryption, no bookmarks, no viewer preferences,
no metadata beyond what PDFium writes, and no ``/AcroForm``, so ``formType()`` is ``None``.

See :ref:`pdfium-defaults`.

Edit pages
~~~~~~~~~~

.. code-block:: php

   $doc = Document::fromFile('/path/report.pdf');
   $cover = $doc->page(1);

   $doc->deletePage(0);                     // $cover->index() is now 0
   $cover->setRotation(90);
   $appendix = Document::fromFile('/path/appendix.pdf');
   $doc->insertPages($appendix);            // its pages, after the last page

   $doc->save('/tmp/edited.pdf');           // written out only here; report.pdf is not changed

A ``Page`` you hold follows its page through the edits, and throws once you delete its page. See
:ref:`page-editing`.

Embedded files
~~~~~~~~~~~~~~

.. code-block:: php

   foreach ($doc->attachments() as $a) {
       $type = $a->metadata()->subtype ?? 'unknown type';
       echo $a->name(), " ($type): ", strlen($a->contents() ?? ''), " bytes\n";
   }

   // Changes this document in place; save() or toString() writes it out:
   $doc->addAttachment('data.csv', $csv, 'Raw figures');
   $doc->save('/tmp/with-data.pdf');

Adding a file can shift the others, so fetch attachments again afterwards. See :ref:`attachments`.

Signatures
~~~~~~~~~~

.. code-block:: php

   foreach ($doc->signatures() as $s) {
       if ($s->contents !== null) {
           echo $s->subFilter, ' ', $s->time, ' ', $s->reason, "\n";
       }
   }

See :ref:`signatures` for which fields the list includes.

Language and tagging
~~~~~~~~~~~~~~~~~~~~

.. code-block:: php

   if ($doc->language() === null) {
       $doc->setLanguage('en-US');
       $doc->save('/tmp/with-language.pdf');
   }
   var_dump($doc->isTagged());

``isTagged()`` reports the document's own claim. See :ref:`accessibility`.

Annotations
~~~~~~~~~~~

.. code-block:: php

   use Pdfium\Quad;

   $page = $doc->page(0);
   foreach ($page->annotations() as $a) {
       echo $a->type(), ': ', $a->stringValue('Contents') ?? '', "\n";   // Text: Check this
   }

   // Highlight a word; changes this document in place:
   $r = $page->textPage()->search('total')[0]->rects[0];
   $h = $page->addAnnotation('Highlight', $r);
   $h->addQuadPoints(new Quad(
       $r->left, $r->top, $r->right, $r->top, $r->left, $r->bottom, $r->right, $r->bottom,
   ));
   $h->setColor(255, 200, 0);
   $h->setStringValue('Contents', 'Check the total');

   $page->render();   // draws the highlight and stores its appearance, for other viewers
   $doc->save('/tmp/highlighted.pdf');

Other viewers draw an annotation from its appearance stream. ``render()`` generates one for most
types, so render the page before you save. See :ref:`annotations`.

Fill in a form
~~~~~~~~~~~~~~

.. code-block:: php

   $doc = Document::fromFile('/path/form.pdf');
   $page = $doc->page(0);

   foreach ($page->formFields() as $field) {
       echo $field->name(), ' (', $field->type()->name, '): ', $field->value(), "\n";
   }

   foreach ($page->formFields() as $field) {
       match ($field->name()) {
           'name' => $field->setValue('Jane Doe'),
           'agree' => $field->setChecked(),
           'country' => $field->select(2),
           default => null,
       };
   }

   // Turn the fields and every other annotation into page content, then write the document out:
   $page->flatten();
   $doc->save('/tmp/filled.pdf');

Filling and flattening change the loaded document, never the file it came from. See :ref:`forms`
for what PDFium does with read-only fields, scripts and long text.

Free a document
~~~~~~~~~~~~~~~

There is no ``close()``. PHP frees a document once nothing uses it. Pages keep their document
alive:

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

Run the tests. The script builds the extension first if needed:

.. code-block:: sh

   scripts/test.sh                      # all tests
   scripts/test.sh tests/render.phpt    # one test

Or run them inside Docker, with all tools installed:

.. code-block:: sh

   scripts/dev.sh scripts/test.sh
   PDFIUM_ZTS=1 scripts/dev.sh scripts/test.sh   # ZTS PHP, with the thread tests

The thread tests need the ``parallel`` extension; ``scripts/test.sh`` loads it.

Without the scripts, run PHP's test runner directly. On ZTS PHP, add ``-d extension=parallel`` or
the runner skips the thread tests:

.. code-block:: sh

   php run-tests.php -n -d extension=$PWD/modules/pdfium.so tests

``pdfium.stub.php`` defines the public API. PHP's ``build/gen_stub.php`` generates
``pdfium_arginfo.h`` from it.

``src/unicode_tables.h`` holds the case-folding and word-character tables for
``TextPage::search()``. ``scripts/unicode-tables.py`` generates it from the Unicode data of the
Python that runs it, and records that version as ``PDFIUM_UNICODE_VERSION``.

Do not edit the header; run the script again.

CI does the following:

- builds with ``-Wall -Wextra -Werror``;
- checks that ``pdfium_arginfo.h`` matches the stub;
- runs the tests on every supported platform and PHP version;
- runs them again under Valgrind, AddressSanitizer and UndefinedBehaviorSanitizer.

The Linux jobs run in prebuilt images from ``ci/docker/``, which the ``CI images`` workflow
publishes to ``ghcr.io``.

``PDFIUM_VERSION`` and ``pdfium.sha256`` pin PDFium. ``scripts/update-pdfium.sh`` moves both to the
latest pdfium-binaries release, or to the one you pass as its argument.

The ``PDFium update`` workflow runs that script on the first day of each month and opens a pull
request when there is a new release.

The workflow uses the ``PDFIUM_BOT_TOKEN`` secret: a fine-grained token with contents and pull
request write access to this repository. A pull request opened with it triggers CI.

Build these docs:

.. code-block:: sh

   python3 -m venv .venv
   .venv/bin/pip install -r docs/requirements.txt
   .venv/bin/sphinx-build -W -b html docs docs/_build/html


Contributing
------------

Report bugs and send pull requests on `GitHub <https://github.com/chaikivskyi/php-pdfium>`_.
Include the output of ``php -v`` and ``php --ri pdfium``, and the PDF that causes the problem if
you can share it.


License
-------

The extension is MIT licensed. PDFium is BSD-3-Clause / Apache-2.0. Its license files ship with
the prebuilt binaries; include them if you distribute those binaries.

Thanks to the `PDFium <https://pdfium.googlesource.com/pdfium/>`_ authors,
`pdfium-binaries <https://github.com/bblanchon/pdfium-binaries>`_ for the prebuilt releases, and
`pypdfium2 <https://github.com/pypdfium2-team/pypdfium2>`_, whose docs inspired these.
