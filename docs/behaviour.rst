Behaviour
=========

Things worth knowing that are easy to miss.


Lifetimes
---------

There is no ``close()``. PHP frees a document when nothing refers to it any more. These keep a
document alive:

- the :php:class:`Document <Pdfium\\Document>` object itself;
- any :php:class:`Page <Pdfium\\Page>` or :php:class:`PageIterator <Pdfium\\PageIterator>` from it.

A document opened with ``fromString()`` also keeps its string in memory.

These do **not** keep a document alive:

- a :php:class:`Bitmap <Pdfium\\Bitmap>` rendered from one of its pages;
- a document made from it with ``extractPages()`` or ``merge()``. The pages are copied, so you can
  free the source right away.

To free a document, ``unset()`` it and every page and iterator taken from it.


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
   The first ``render()`` of a page sets up PDFium's form support for the document. For form fields
   without an appearance stream, PDFium then generates one, and a later ``save()`` or
   ``toString()`` includes it.


.. _rendering:

Rendering
---------

``render()`` draws the page the way a PDF viewer does:

- on a white background, with no transparency;
- with annotations and form fields;
- at its size after rotation.

The image size is ``points × dpi / 72``, rounded, and at least 1 pixel. An A4 page
(595.28 × 841.89 points) at 150 DPI gives 1240 × 1754 pixels.

Limits:

- DPI must be from 1 to 2400.
- Each side can be at most 65,500 pixels, the largest JPEG allows.
- The image can have at most 100 million pixels.
- JPEG quality must be from 1 to 100 (default 85).

Going over a limit throws ``\ValueError``. The limits cannot be changed. PNG and JPEG output is RGB.


.. _memory-limit:

Memory limit
------------

When PHP runs out of ``memory_limit``, it normally stops with a fatal error. This extension checks
first and throws :php:exc:`Pdfium\\MemoryException` instead, so you can catch it.

Rough memory needed:

- ``render()``: about 7 bytes per pixel.
- ``text()``: about 8 bytes per character.
- ``toPng()``, ``toJpeg()``, ``toString()`` and the ``save*()`` methods: the size of the output.

With ``memory_limit = -1`` there are no checks.

.. note::

   A :php:class:`Bitmap <Pdfium\\Bitmap>` holds its pixels (4 bytes each) in PHP memory for as long as
   it lives, so they count toward ``memory_limit`` and show in ``memory_get_usage()``. Keeping many
   large bitmaps can make the next ``render()`` throw ``MemoryException``. Save or encode a bitmap and
   ``unset()`` it when you are done.


.. _threads:

Threads
-------

NTS and ZTS PHP are both supported. PDFium is not thread-safe, so on ZTS only one thread per process
can use it at a time.

A call made while another thread is using PDFium throws :php:exc:`Pdfium\\BusyException` and does
nothing, so it is safe to retry. To render in parallel, use several processes, for example PHP-FPM
workers.

- ``Bitmap`` methods never throw it.
- Freeing a document, page or page iterator, and ``PageIterator::next()`` and ``rewind()``, never
  throw it, but may wait for the other thread to finish.
- ``fromFile()`` reads the file before it checks. For a stream you cannot read twice, such as
  ``php://stdin``, read it into a string yourself and retry with ``fromString()``.
- Do not ``pcntl_fork()`` while another thread may be using PDFium. The child would find PDFium
  busy forever and hang when it frees a document or page.
