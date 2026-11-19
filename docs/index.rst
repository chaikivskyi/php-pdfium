php-pdfium
==========

**pdfium** is a PHP extension for reading, searching and rendering PDF files, editing their pages,
splitting and merging them, putting several pages on one sheet, and starting new ones.
It is built on Google's `PDFium <https://pdfium.googlesource.com/pdfium/>`_, the PDF engine inside
Chrome.

Pages load only when you ask for them, so walking a long document does not hold every page in memory.

.. code-block:: php

   use Pdfium\Document;

   $doc = Document::fromFile('/path/file.pdf');

   foreach ($doc as $page) {
       echo $page->text();
       $page->render(dpi: 150)->savePng("/tmp/page-{$page->index()}.png");
   }

These docs cover version |release|, built against PDFium |pdfium_version|.

.. toctree::
   :maxdepth: 2

   introduction
   php_api
   behaviour
   platforms
   changelog
