PHP API
=======

.. php:namespace:: Pdfium

All classes are in the ``Pdfium`` namespace. The exact signatures are in ``pdfium.stub.php``.

You cannot create these classes with ``new``, clone them or serialize them. Get a
:php:class:`Document` from :php:meth:`Document::open` or :php:meth:`Document::fromString`, and
everything else from the document.


Document
--------

.. php:class:: Document

   A PDF document. Implements ``\Countable`` and ``\IteratorAggregate``, so ``count($doc)`` and
   ``foreach ($doc as $index => $page)`` both work.

   .. rubric:: Opening

   .. php:staticmethod:: open(string $path, ?string $password = null) -> Document

      Reads a PDF file through PHP streams, so stream wrappers such as ``phar://`` or ``s3://`` work
      and ``open_basedir`` is respected. The whole file is kept in PHP memory; the file itself is
      not used again.

      :param string $path: Path or stream URL.
      :param ?string $password: Password for an encrypted document.
      :rtype: Document
      :throws \\ValueError: The path is empty, or the path or password contains a NUL byte.
      :throws FileException: The file cannot be read, the path is too long, or it is outside
         ``open_basedir``.
      :throws MemoryException: The file would not fit under ``memory_limit``.
      :throws FormatException: The file is not a PDF or is corrupted.
      :throws PasswordException: A password is needed, or the password is wrong.
      :throws UnsupportedException: The document uses an encryption PDFium does not support.

   .. php:staticmethod:: fromString(string $bytes, ?string $password = null) -> Document

      Opens a PDF from a string. The string is not copied; the document keeps it in memory.

      :param string $bytes: The contents of a PDF file.
      :param ?string $password: Password for an encrypted document.
      :rtype: Document
      :throws \\ValueError: The string is empty, or the password contains a NUL byte.
      :throws FormatException: The data is not a PDF or is corrupted.
      :throws PasswordException: A password is needed, or the password is wrong.
      :throws UnsupportedException: The document uses an encryption PDFium does not support.

   .. rubric:: Information

   .. php:method:: pageCount() -> int

      The number of pages. Fast: no page is loaded.

      :rtype: int

   .. php:method:: count() -> int

      Same as :php:meth:`pageCount`, so ``count($doc)`` works.

      :rtype: int

   .. php:method:: metadata() -> array

      The document's title, author and so on, as UTF-8 strings. Empty or missing entries are left
      out. Dates are returned as they are stored in the PDF, for example ``D:20261031120000Z``.

      Possible keys: ``title``, ``author``, ``subject``, ``keywords``, ``creator``, ``producer``,
      ``creationDate``, ``modDate``.

      :returns: ``array<string, string>``
      :rtype: array

   .. php:method:: permissions() -> array

      The document's permission flags. They are only reported, not enforced.

      Always has these eight keys: ``print``, ``modify``, ``copy``, ``annotate``, ``fillForms``,
      ``extractForAccessibility``, ``assemble``, ``printHighQuality``. For an unencrypted document,
      all of them are ``true``.

      :returns: ``array<string, bool>``
      :rtype: array

   .. rubric:: Pages

   .. php:method:: page(int $index) -> Page

      Loads one page.

      :param int $index: Page number, starting at 0.
      :rtype: Page
      :throws \\ValueError: The index is out of range.
      :throws FormatException: PDFium cannot load the page.

   .. php:method:: pages() -> PageIterator

      An iterator over all pages. It loads one page at a time.

      :rtype: PageIterator

   .. php:method:: getIterator() -> PageIterator

      Same as :php:meth:`pages`, so ``foreach ($doc as $index => $page)`` works.

      :rtype: PageIterator

   .. rubric:: Splitting, merging and saving

   .. php:method:: extractPages(array $indexes) -> Document

      A new document with the given pages, in the given order. A page can appear more than once.

      The new document is unencrypted and has no bookmarks, viewer preferences or metadata beyond
      what PDFium writes. Form fields still draw but can no longer be filled in. See
      :ref:`pdfium-defaults`.

      :param array $indexes: A non-empty list of page numbers (``int[]``).
      :rtype: Document
      :throws \\ValueError: The list is empty, or an index is out of range.
      :throws \\TypeError: An element is not an ``int``.
      :throws Exception: PDFium cannot copy the pages.

   .. php:staticmethod:: merge(array $documents) -> Document

      A new document with all pages of each document, one after another.

      Like :php:meth:`extractPages`, the result is unencrypted and has no bookmarks, viewer
      preferences or metadata beyond what PDFium writes, and its form fields can no longer be filled
      in.

      :param array $documents: A non-empty list of ``Document`` objects.
      :rtype: Document
      :throws \\ValueError: The list is empty.
      :throws \\TypeError: An element is not a ``Document``.
      :throws Exception: PDFium cannot copy the pages.

   .. php:method:: toString(bool $removeSecurity = false) -> string

      The whole document as a PDF file in a string.

      :param bool $removeSecurity: ``true`` writes the document without encryption. By default an
         encrypted document stays encrypted.
      :rtype: string
      :throws MemoryException: The result would not fit under ``memory_limit``.
      :throws Exception: PDFium cannot write the document.

   .. php:method:: save(string $path, bool $removeSecurity = false) -> void

      Writes :php:meth:`toString` to a file, replacing it if it exists. Stream wrappers and
      ``open_basedir`` work here. If writing a new file fails, the half-written file is deleted.
      An existing file is replaced by renaming a complete copy over it where possible; see
      :ref:`the behaviour page <pdfium-defaults>`.

      :param string $path: File to write.
      :param bool $removeSecurity: ``true`` writes the document without encryption.
      :throws \\ValueError: The path is empty.
      :throws FileException: The file cannot be written.
      :throws MemoryException: The result would not fit under ``memory_limit``.
      :throws Exception: PDFium cannot write the document.


Page
----

.. php:class:: Page

   One page of a :php:class:`Document`.

   .. php:method:: index() -> int

      The page's position in its document, starting at 0.

      :rtype: int

   .. php:method:: width() -> float

      Width in points (1/72 inch), after rotation. For example, ``595.28`` for A4.

      :rtype: float

   .. php:method:: height() -> float

      Height in points, after rotation. For example, ``841.89`` for A4.

      :rtype: float

   .. php:method:: rotation() -> int

      Rotation in degrees: ``0``, ``90``, ``180`` or ``270``.

      :rtype: int

   .. php:method:: text() -> string

      All text on the page, as UTF-8. Returns ``""`` if the page has no text.

      :rtype: string
      :throws FormatException: PDFium cannot read the page's text.
      :throws MemoryException: The text would not fit under ``memory_limit``.

   .. php:method:: render(int $dpi = 150) -> Bitmap

      Renders the page to a bitmap. See :ref:`rendering`.

      :param int $dpi: Resolution, from 1 to 2400.
      :rtype: Bitmap
      :throws \\ValueError: The DPI is out of range, a side would be over 65,500 pixels, or the
         image would be over 100 million pixels.
      :throws MemoryException: The bitmap would not fit under ``memory_limit``.


PageIterator
------------

.. php:class:: PageIterator

   Returned by :php:meth:`Document::pages`. Implements ``\Iterator``: keys are page numbers, values
   are :php:class:`Page` objects. Only the current page is kept in memory.

   .. code-block:: php

      foreach ($doc->pages() as $index => $page) { /* ... */ }

   .. php:method:: current() -> Page

      :rtype: Page
      :throws Exception: The iterator is past the last page.
      :throws FormatException: PDFium cannot load the page.

   .. php:method:: key() -> int

      The current page number.

      :rtype: int

   .. php:method:: next() -> void

      Moves to the next page.

   .. php:method:: rewind() -> void

      Goes back to the first page.

   .. php:method:: valid() -> bool

      Whether the iterator is on a page.

      :rtype: bool


Bitmap
------

.. php:class:: Bitmap

   The pixels of a rendered page, returned by :php:meth:`Page::render`. Save it or encode it as PNG
   or JPEG.

   .. php:method:: width() -> int

      Width in pixels.

      :rtype: int

   .. php:method:: height() -> int

      Height in pixels.

      :rtype: int

   .. php:method:: toPng() -> string

      The bitmap as PNG data.

      :rtype: string
      :throws MemoryException: The PNG would not fit under ``memory_limit``.
      :throws Exception: Encoding fails.

   .. php:method:: toJpeg(int $quality = 85) -> string

      The bitmap as JPEG data.

      :param int $quality: Quality, from 1 to 100.
      :rtype: string
      :throws \\ValueError: The quality is out of range.
      :throws MemoryException: The JPEG would not fit under ``memory_limit``.
      :throws Exception: Encoding fails.

   .. php:method:: savePng(string $path) -> void

      Writes :php:meth:`toPng` to a file. Works like :php:meth:`Document::save`.

      :param string $path: File to write.
      :throws \\ValueError: The path is empty.
      :throws FileException: The file cannot be written.
      :throws MemoryException: The PNG would not fit under ``memory_limit``.
      :throws Exception: Encoding fails.

   .. php:method:: saveJpeg(string $path, int $quality = 85) -> void

      Writes :php:meth:`toJpeg` to a file. Works like :php:meth:`Document::save`.

      :param string $path: File to write.
      :param int $quality: Quality, from 1 to 100.
      :throws \\ValueError: The path is empty, or the quality is out of range.
      :throws FileException: The file cannot be written.
      :throws MemoryException: The JPEG would not fit under ``memory_limit``.
      :throws Exception: Encoding fails.


.. _exceptions:

Exceptions
----------

Every error throws an exception. No method returns ``false`` or raises a warning.

Bad arguments throw PHP's own ``\ValueError`` or ``\TypeError``. Everything else throws one of
these:

.. php:exception:: Exception

   Base class for all exceptions below. Extends ``\RuntimeException``.

   Also thrown on its own when PDFium cannot copy pages, write a document or encode a bitmap.

.. php:exception:: FileException

   The file cannot be read or written, the path is too long, or ``open_basedir`` blocks it.

   When opening or saving fails, the PHP warning that says why (for example ``No such file or
   directory``) is attached as the previous exception (``$e->getPrevious()``). No warning is shown
   or logged. Through stream wrappers the call behaves like ``@fopen()``: your error handler, if you
   set one, still sees the wrapper's warnings, and fatal errors are shown as usual.

.. php:exception:: FormatException

   The data is not a PDF, or it is too damaged to read. Also thrown when a page or its text cannot
   be loaded.

.. php:exception:: PasswordException

   A password is needed, or the password is wrong.

.. php:exception:: UnsupportedException

   The document uses an encryption PDFium does not support.

.. php:exception:: MemoryException

   The operation would go over ``memory_limit``. Thrown instead of PHP's fatal out-of-memory error.
   See :ref:`memory-limit`.

When a document fails to open, ``$e->getCode()`` holds PDFium's error code:

=====  =======================  ==========================
Code   Meaning                  Exception
=====  =======================  ==========================
1      Unknown error            :php:exc:`Exception`
2      File error               :php:exc:`FileException`
3      Format error             :php:exc:`FormatException`
4      Password error           :php:exc:`PasswordException`
5      Unsupported encryption   :php:exc:`UnsupportedException`
6      Page error               :php:exc:`FormatException`
=====  =======================  ==========================

Other exceptions have code 0.
