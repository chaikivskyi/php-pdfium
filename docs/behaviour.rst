Behaviour
=========

Things worth knowing that are easy to miss.


Lifetimes
---------

There is no ``close()``. PHP frees a document when nothing refers to it any more. These keep a
document alive:

- the :php:class:`Document <Pdfium\\Document>` object itself;
- any :php:class:`Page <Pdfium\\Page>`, :php:class:`TextPage <Pdfium\\TextPage>` or
  :php:class:`PageIterator <Pdfium\\PageIterator>` from it.

A :php:class:`TextPage <Pdfium\\TextPage>` also keeps its page loaded.

A document opened with ``fromString()`` also keeps its string in memory.

These do **not** keep a document alive:

- a :php:class:`Bitmap <Pdfium\\Bitmap>` rendered from one of its pages;
- a document made from it with ``extractPages()`` or ``merge()``. The pages are copied, so you can
  free the source right away.

To free a document, ``unset()`` it and every page, text page and iterator taken from it.


.. _pdfium-defaults:

PDFium defaults
---------------

The extension follows PDFium and adds no rules of its own.

**Permissions are not enforced.**
   ``permissions()`` tells you what the document allows, but nothing stops you. For example, you can
   read the text of a document that forbids copying.

**Removing encryption is always explicit.**
   ``save()`` and ``toString()`` keep the encryption. Pass ``removeSecurity: true`` to save without
   it. PDFium cannot encrypt a new document, so ``extractPages()`` and ``merge()`` throw
   :php:exc:`Pdfium\\UnsupportedException` for an encrypted source, whether it was opened with a user
   password or only has an owner password, unless you pass ``removeSecurity: true``. Then the result
   is an ordinary unencrypted document.

**Split and merge output keeps the pages and the viewer preferences.**
   ``extractPages()`` keeps its document's viewer preferences and ``merge()`` the first document's,
   so a right-to-left document still reads right to left. The result has no bookmarks and none of the
   sources' metadata: ``metadata()`` reports only ``creator`` and ``producer`` "PDFium" and the
   ``creationDate`` of the split or merge. Form fields still draw but can no longer be filled in.
   PDFium has no API to set metadata, add encryption or create bookmarks.

**Reading and writing files work differently.**
   Both go through PHP streams, so stream wrappers and ``open_basedir`` work. ``fromFile()`` reads the
   whole file into PHP memory (counted against ``memory_limit``) and never touches it again, so you
   can save over a file while its document is open.

   Saving over an existing local file writes a complete new file next to it and renames it into
   place, keeping the owner, mode and extended attributes. When that is not possible (other hard
   links, a read-only directory, a file owned by someone else, extended attributes or ACLs that
   cannot be copied, or a system without extended attribute support), the file is overwritten in
   place. Where the filesystem can reserve the space first, a full disk then leaves it untouched;
   elsewhere (macOS, ZFS, NFS, copy-on-write filesystems) a failed write can leave it damaged. A
   failed write deletes the half-written file if the save created it.

   Through stream wrappers (``ftp://``, ``s3://``, your own), nothing is deleted after a failed
   write: the extension cannot tell reliably whether the target existed, and the wrapper knows best
   how to clean up.

**Rendering can change what saving writes.**
   The first time any page of a document is rendered with annotations (the default), PDFium sets up
   form support for the document. For form fields without an appearance stream, PDFium then
   generates one, and a later ``save()`` or ``toString()`` includes it. ``annotations: false``
   leaves the document unchanged.


.. _rendering:

Rendering
---------

``render()`` draws the page the way a PDF viewer does:

- on a white background, with no transparency;
- with annotations and form fields;
- the crop box, clipped to the media box, at its size after rotation.

Options change that:

- ``transparent: true``: a transparent background instead of white;
- ``annotations: false``: only the page's content, without annotations or form fields;
- ``grayscale: true``: gray instead of colour.

The image size is ``points × dpi / 72``, rounded, and at least 1 pixel. An A4 page
(595.28 × 841.89 points) at 150 DPI gives 1240 × 1754 pixels.

Limits:

- DPI must be from 1 to 2400.
- Each side can be at most 65,500 pixels, the largest JPEG allows.
- The image can have at most 100 million pixels.
- JPEG quality must be from 1 to 100 (default 85).

Going over a limit throws ``\ValueError``. The limits cannot be changed.

The output channels follow the options:

==================================  ===============  =====
Options                             PNG              JPEG
==================================  ===============  =====
none                                RGB              RGB
``transparent``                     RGBA             RGB
``grayscale``                       gray             gray
``transparent`` and ``grayscale``   gray and alpha   gray
==================================  ===============  =====

Alpha is straight, not premultiplied. JPEG has no alpha channel, so a transparent render's JPEG is
drawn on white.


.. _text-positions:

Text positions
--------------

Positions are in the PDF's own coordinates: points (1/72 inch), with ``y`` growing upwards and,
on most pages, the origin (0, 0) at the bottom left corner. The page's rotation is not applied.

To mark a character on an image from ``render()``, convert its position to pixels:

.. code-block:: php

   $across = $char->x * $dpi / 72;
   $down = ($page->height() - $char->y) * $dpi / 72;

This holds for a page with no rotation whose crop box starts at 0, 0.

Character indexes follow PDFium's count:

- a character PDFium adds, such as a space between words or a line break, has an index too. Its
  ``Char`` has ``generated`` set to ``true``;
- a character outside the Basic Multilingual Plane, such as an emoji, takes two indexes. The first
  ``Char`` has the whole character as ``text`` and the second has ``""``;
- when a word is hyphenated across two lines, PDFium joins it and no line break follows. The
  hyphen's ``Char`` has ``hyphen`` set to ``true`` and its ``text`` is ``"-"``, as in ``text()``.

A character PDFium cannot map to Unicode has an empty ``text`` and is left out of ``text()``,
``textIn()`` and the text of search matches. Joining every ``text`` usually gives the same string
as ``text()``, but not always: ``text()`` and ``textIn()`` leave control characters out.

``search()`` looks for the needle in what ``text()`` shows, so a match can run over a character
PDFium cannot map.


.. _memory-limit:

Memory limit
------------

When PHP runs out of ``memory_limit``, it normally stops with a fatal error. This extension checks
first and throws :php:exc:`Pdfium\\MemoryException` instead, so you can catch it.

Rough memory needed:

- ``render()``: about 7 bytes per pixel; 8 with ``transparent``, 5 with ``grayscale`` and 6 with
  both.
- ``text()``: about 8 bytes per character.
- ``textIn()`` and ``textInEach()``: about 30 bytes per character on the page, whatever the
  rectangles, and about 600 bytes per rectangle.
- ``chars()``: about 600 bytes per character returned.
- ``search()``: about 8 bytes per character on the page, 10 bytes per byte of the needle, and
  about 500 bytes per match.
- ``webLinks()``: about 500 bytes per result.
- ``toPng()``, ``toJpeg()``, ``toString()`` and the ``save*()`` methods: the size of the output.

With ``memory_limit = -1`` there are no checks.

A ``TextPage`` keeps the page's text inside PDFium, which ``memory_limit`` does not count, for as
long as it lives. ``unset()`` it when you are done with the page.

.. note::

   A :php:class:`Bitmap <Pdfium\\Bitmap>` holds its pixels (4 bytes each; 1 in grayscale, 2 in
   transparent grayscale) in PHP memory for as long as it lives, so they count toward
   ``memory_limit`` and show in ``memory_get_usage()``. Keeping many large bitmaps can make the next
   ``render()`` throw ``MemoryException``. Save or encode a bitmap and ``unset()`` it when you are
   done.


.. _threads:

Threads
-------

NTS and ZTS PHP are both supported. PDFium is not thread-safe, so on ZTS only one thread per process
can use it at a time.

A call made while another thread is using PDFium throws :php:exc:`Pdfium\\BusyException` and does
nothing, so it is safe to retry. To render in parallel, use several processes, for example PHP-FPM
workers.

- ``Bitmap`` methods never throw it.
- Freeing a document, page, text page or page iterator, and ``PageIterator::next()`` and
  ``rewind()``, never throw it, but may wait for the other thread to finish.
- ``fromFile()`` reads the file before it checks. For a stream you cannot read twice, such as
  ``php://stdin``, read it into a string yourself and retry with ``fromString()``.
- Do not ``pcntl_fork()`` while another thread may be using PDFium. The child would find PDFium
  busy forever and hang when it frees a document or page.
