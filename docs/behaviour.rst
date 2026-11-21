Behaviour
=========

Things worth knowing that are easy to miss.


Lifetimes
---------

There is no ``close()``. PHP frees a document when nothing refers to it any more. These keep a
document alive:

- the :php:class:`Document <Pdfium\\Document>` object itself;
- any :php:class:`Page <Pdfium\\Page>`, :php:class:`TextPage <Pdfium\\TextPage>`,
  :php:class:`PageIterator <Pdfium\\PageIterator>` or :php:class:`Attachment <Pdfium\\Attachment>`
  from it.

A :php:class:`TextPage <Pdfium\\TextPage>` also keeps its page loaded.

A document opened with ``fromString()`` also keeps its string in memory, and one opened with
``fromFile(lazy: true)`` keeps its file open.

These do **not** keep a document alive:

- a :php:class:`Bitmap <Pdfium\\Bitmap>` rendered from one of its pages;
- a document made from it with ``extractPages()`` or ``merge()``. The pages are copied, so you can
  free the source right away.

To free a document, ``unset()`` it and every page, text page, iterator and attachment taken from
it.


.. _pdfium-defaults:

PDFium defaults
---------------

The extension follows PDFium and adds few rules of its own. ``extractPages()`` and ``merge()``
refuse an encrypted source unless you pass ``removeSecurity: true`` (below). For a document opened
with ``fromFile(lazy: true)``, a save will not overwrite its file in place, and once its file could
not be read in full it will not be saved, split or merged; see :ref:`large-files`.

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
   can save over a file while its document is open. With ``lazy: true``, ``fromFile()`` instead
   keeps the file open and reads it as needed; see :ref:`large-files`.

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

**An incremental save falls back to a full copy without saying so.**
   ``incremental: true`` writes the original file as it was, from its ``%PDF`` header on, and
   appends a section after it; bytes before the header, which some files have, are dropped. The
   appended section holds the changes and also unchanged copies of the objects PDFium has read, such
   as the document catalog, the metadata once you call ``metadata()``, and a page's objects once you
   open it. An encrypted document stays encrypted.

   PDFium writes a full copy instead, and reports success, in two cases: with
   ``removeSecurity: true``, and for a document from ``extractPages()`` or ``merge()``, which has no
   original bytes. To check that a save was incremental, test that it starts with the original from
   its ``%PDF`` on:

   .. code-block:: php

      $fromHeader = substr($original, strpos($original, '%PDF'));
      $wasIncremental = str_starts_with($saved, $fromHeader);

   Each incremental save appends one section to the bytes the document was opened from, even when
   nothing changed. A second save of the same ``Document`` also starts from those bytes, not from
   the first save's output; opening the result and saving it incrementally adds another section, so
   a file updated that way over and over keeps growing.

   The original is not rewritten, so the ranges a digital signature covers stay as they were.
   Whether a viewer still accepts the signature depends on what you read as well as what you
   changed, since some validators treat any object written after signing as a modification; the
   extension does not check signatures.

   Most files from PDF 1.5 on index their objects with a cross-reference stream. For those, PDFium
   writes the appended index without the ``/Type /XRef`` entry the format requires. PDFium reads the
   result, but other tools, qpdf for one, cannot, and a signature validator may not get as far as
   the signature.

**The version option only changes the header.**
   ``version`` sets the PDF version in the file header, from ``10`` for 1.0 to ``17`` for 1.7.
   Nothing in the document is converted to suit an older version. PDFium ignores any other value
   without an error, as if ``version`` were not given, and writes the source's 1.x version, or 1.7
   for a document from ``extractPages()`` or ``merge()``. An incremental save keeps the original
   header, so ``version`` only applies when the save falls back to a full copy.

   A full save of a PDF 2.0 file writes a ``%PDF-1.0`` header unless you pass a ``version`` from 10
   to 17; ``version: 20`` is outside the range and ignored like any other value.

**Saves of one document usually differ.**
   Every save, full or incremental, writes a new changing identifier (see ``changingId()``), so two
   saves of the same document are normally not byte-identical. An incremental save keeps the
   permanent identifier. Compare documents by their content, not their bytes.

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

- ``region``: only a rectangle of the page, see below;
- ``transparent: true``: a transparent background instead of white;
- ``annotations: false``: only the page's content, without annotations or form fields;
- ``grayscale: true``: gray instead of colour.

The image size is ``points × dpi / 72``, rounded, and at least 1 pixel. An A4 page
(595.28 × 841.89 points) at 150 DPI gives 1240 × 1754 pixels.

The ``region`` :php:class:`Rect <Pdfium\\Rect>` is in points, as for
:ref:`text positions <text-positions>`, so a search match can be cropped directly:

.. code-block:: php

   $match = $page->textPage()->search('Total')[0];
   $page->render(dpi: 300, region: $match->rects[0])->savePng('/tmp/total.png');

The image is that part of a whole-page render at the same DPI, rotated with the page (so width and
height swap at 90° and 270°), to within a pixel at the edges. Its size is the rectangle's size ×
dpi / 72, rounded, and at least 1 pixel. Parts outside the page come out as background. A rectangle
with no width or height, or one that does not overlap the page (touching its edge is not enough),
throws ``\ValueError``.

Limits:

- DPI must be from 1 to 2400.
- Each side can be at most 65,500 pixels, the largest JPEG allows.
- The image can have at most 100 million pixels.
- With ``region``, the whole page at that DPI can be at most 4,194,304 pixels a side, far beyond
  any real page.
- JPEG quality must be from 1 to 100 (default 85).

Going over a limit throws ``\ValueError``. The limits cannot be changed. With ``region``, the other
size limits apply to the region's image, so a small area can be rendered at a DPI the whole page
could not.

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


.. _large-files:

Large files
-----------

``fromFile($path, lazy: true)`` opens a local file without reading it whole. The document keeps the
file open, and PDFium reads the parts it needs when it needs them: opening a well-formed file and
counting its pages typically reads a few kilobytes, and rendering a page reads what that page uses.
A file whose cross-reference table (the index of where each object is) is damaged can make PDFium
read all of it while opening. In one measurement, a 120 MB file whose pages are large images needed
about 20 MB to open and render one page, against about 140 MB when loaded whole. The file does not
need to be linearized ("fast web view"); that made no difference in this measurement.

- Only local files: a path that goes through a stream wrapper (``s3://``, ``ftp://``, ``data:``,
  ``php://``, ``phar://``, your own) throws :php:exc:`Pdfium\\UnsupportedException`. If PHP
  cannot use the wrapper, because it is not registered or ``allow_url_fopen`` disables it, the call
  throws :php:exc:`Pdfium\\FileException` instead. ``open_basedir`` applies, and ``file://`` paths
  are local.
- The file must not change while the document exists. PDFium cannot tell, and would read a mix of
  old and new bytes. Deleting or renaming the file is fine: the document keeps reading the open
  file, and its disk space stays in use until the document is freed.
- Each document opened this way holds one open file, which counts against the process's open-file
  limit.
- PDFium keeps what it has read until the document is freed. Rendering every page or a full save
  reads the whole file, and PDFium's memory grows to about the file's size.
- PDFium's own memory is not counted against ``memory_limit``; only what the extension builds in
  PHP is. So a file larger than ``memory_limit`` can be opened lazily and its pages rendered.
- ``save()`` and ``toString()`` still build the whole output in PHP memory, counted against
  ``memory_limit``, as for any document. A full save is about the size of the file and an
  incremental save at least that, so saving a file larger than ``memory_limit`` throws
  :php:exc:`Pdfium\\MemoryException`.
- You can save over the file the document reads: the save writes a new copy and renames it into
  place, and the document keeps reading the old one. When the save would have to overwrite the file
  in place instead (see :ref:`pdfium-defaults` for when), it throws :php:exc:`Pdfium\\FileException`
  and leaves the file alone. The check covers every save the extension makes in the same request or
  thread, by any document or bitmap. It does not cover saves through a stream wrapper, or writes by
  other threads or processes.
- If the file is truncated or a read fails while the document is open, anything read from the
  document, such as pages, text, bookmarks, links, attachment contents and ``javaScripts()``, may
  come back incomplete without an error, or fail as it would for a damaged file. From then on,
  ``save()``, ``toString()``, ``extractPages()`` and ``merge()`` throw
  :php:exc:`Pdfium\\FormatException` rather than produce an incomplete copy.

The extension does not expose PDFium's API for opening a document while it is still downloading
(``fpdf_dataavail.h``). For a remote file, download it to a temporary file and open that with
``lazy: true``.


.. _fonts:

Fonts
-----

A PDF either carries its fonts or only names them. For a font it only names, PDFium looks in the
system's font directories and, when it finds nothing suitable, draws the text with a built-in
substitute. That is never an error, so a page can render with the wrong letter shapes without any
sign from the extension, and characters the substitute lacks may not be drawn at all.

The 14 standard fonts (Helvetica, Times, Courier and their styles, Symbol and ZapfDingbats) are
named rather than carried in most PDFs. PDFium draws Helvetica, Times and Courier with Arial, Times
New Roman and Courier New when it finds them in the font directories, and with its built-in fonts
otherwise.

The ini setting ``pdfium.font_paths`` lists the directories PDFium scans, separated by ``:`` like
``include_path``:

.. code-block:: ini

   pdfium.font_paths = /app/fonts:/usr/share/fonts:/usr/local/share/fonts

- The list **replaces** PDFium's own directories: on Linux, ``/usr/share/fonts``,
  ``/usr/local/share/fonts``, ``/usr/share/X11/fonts/Type1`` and ``/usr/share/X11/fonts/TTF``. List
  the ones you still want. Empty, the default, means PDFium's own directories, and so does a value
  with no directory in it, such as ``:``.
- It is read once, when PHP starts. Set it in ``php.ini`` or a file in its ``conf.d``
  directory, or with ``-d`` on the command line, and restart PHP-FPM after changing it.
  ``ini_set()`` refuses it. A value set later, with ``php_admin_value`` in a PHP-FPM pool or an
  Apache virtual host, has no effect on PDFium, even though ``ini_get()`` and ``phpinfo()`` show it.
- A directory that does not exist is ignored, without a warning. Use absolute paths.
- It is one setting for the whole process: every document sees the same fonts.

This is the usual fix for a container image with no fonts installed: copy the fonts your PDFs name
into a directory and list it. A copy of Arial, Times New Roman or Courier New there also changes how
Helvetica, Times or Courier text looks.


.. _text-positions:

Text positions
--------------

Positions are in the PDF's own coordinates: points (1/72 inch), with ``y`` growing upwards and,
on most pages, the origin (0, 0) at the bottom left corner. The page's rotation is not applied.

To mark a character on an image from ``render()``, convert its position to pixels:

.. code-block:: php

   $across = $char->x * $dpi / 72;
   $down = ($page->height() - $char->y) * $dpi / 72;

This holds for a page with no rotation whose crop box starts at 0, 0. For a ``region`` image,
measure from the region's top left corner instead, which works on any page with no rotation:

.. code-block:: php

   $across = ($char->x - $region->left) * $dpi / 72;
   $down = ($region->top - $char->y) * $dpi / 72;

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


.. _navigation:

Navigation
----------

Link rectangles and quads, and the point you pass to ``linkAt()``, are in points, as for
:ref:`text positions <text-positions>`, without the page's rotation.

A broken outline can loop, with an entry pointing back to an earlier one. ``bookmarks()`` lists
each entry once: when it reaches an entry it has already listed, it skips that entry and the rest
of its level. It also goes at most 100 levels deep: entries on the 100th level have no children.
Real outlines are far shallower.

A bookmark or link has a plain destination, an action, or neither. To find the page it leads to,
read ``destination``: it is set for a plain destination and for a ``"GoTo"`` action (then it
equals ``action->destination``), and is ``null`` for every other action. ``action`` is ``null``
when there is no action. If a PDF gives both a destination and an action, the action wins.

A :php:class:`Destination <Pdfium\\Destination>` always points into this document; when its page
cannot be found, ``destination`` is ``null``. Actions into another PDF (``"GoToR"``) or an embedded
PDF (``"GoToE"``) have no destination: the page they lead to is not available. ``filePath`` names
the other file; for ``"GoToE"`` it is ``null`` when the embedded PDF is attached to this document.

A ``"URI"`` action's ``uri`` is not checked for encoding. PDF asks for 7-bit ASCII, but real files
hold UTF-8 and other encodings, so check it before you use it as text.

Actions PDFium does not read, such as JavaScript, form submission and named actions like *next
page*, have the type ``"Unsupported"`` and nothing else set.


.. _javascript:

JavaScript
----------

``javaScripts()`` reads only the scripts in the document's script list (its ``/JavaScript`` name
tree). It leaves out scripts chained after an entry with ``/Next`` and scripts on page actions,
links, annotations and form fields; those actions still have the type ``"Unsupported"``. It also
leaves out the document's open action (``/OpenAction``) and document actions (``/AA``, such as
before closing or printing), which no method reads; ``Page::openAction()`` reads only a page's. So
an empty result does not mean the file has no JavaScript. An entry that is not JavaScript, or whose
source is missing or is not a string or stream, is left out. An entry whose source is empty is
returned with an empty ``script``.

Nothing is ever run: the extension only returns the source. It comes from the file, so treat it as
untrusted text. NUL bytes in a name or script are kept, so nothing is cut short.

A damaged script list, one that loops or nests too deeply, is read as far as PDFium can follow it.
A crafted PDF of a few kilobytes can keep ``javaScripts()`` busy for minutes. The extension cannot
detect such a file, and ``max_execution_time`` cannot interrupt PDFium. On NTS PHP the process is
killed ``hard_timeout`` seconds (2 by default) after the time limit runs out, without running
shutdown functions; on ZTS PHP the limit only takes effect when PDFium returns, and other threads
get ``BusyException`` meanwhile. With PDFs you do not trust, run this work where it can be killed
from outside, for example under PHP-FPM's ``request_terminate_timeout``.


.. _attachments:

Attachments
-----------

``addAttachment()`` and ``deleteAttachment()`` change the loaded document, never the file or
string it came from; ``save()`` or ``toString()`` writes the change out. A deleted file only leaves
the list: its bytes stay in the saved PDF, unused.

The list is kept sorted by name, so adding or deleting a file can shift the indexes of the others.
After either call, every :php:class:`Attachment <Pdfium\\Attachment>` fetched before it is stale:
its methods throw ``PdfiumException`` instead of reading a different file. Fetch it again with
``attachments()`` or ``attachment()``. The one ``addAttachment()`` returns is current. To delete
several files, see ``deleteAttachment()`` in :doc:`php_api`.

Strictly, the list is sorted and checked by a key PDFium does not show. PDFium sets it to the file
name, but other tools may not, so two entries can have the same ``name()``, and ``addAttachment()``
can refuse a name that no ``name()`` shows.

When PDFium cannot decode a file, ``contents()`` returns its bytes as stored, still compressed or
encoded. This happens when the stream is damaged, or when it is ``/RunLengthDecode`` and would
decode to 20 MB or more.

PDFium decodes a file in its own memory, which ``memory_limit`` does not count, before
``contents()`` can check the size. A small compressed file that expands to gigabytes can exhaust
the process's memory before ``MemoryException`` is thrown. ``addAttachment()`` also gives PDFium a
copy of ``$contents`` outside ``memory_limit``.

A file added to an encrypted document is encrypted with it on save, unless you pass
``removeSecurity: true``.

A damaged list, one that loops or nests too deeply, is read as far as PDFium can follow it. Entries
past that point are not counted, and ``addAttachment()`` throws ``PdfiumException`` on a list that
nests too deeply.

A crafted PDF of a few kilobytes can keep ``attachments()``, ``attachment()``, ``addAttachment()``
and ``deleteAttachment()`` busy for minutes, and ``index()`` of an added file too.
``attachmentCount()`` and the other ``Attachment`` methods stay fast. The extension cannot detect
such a file, and ``max_execution_time`` cannot interrupt PDFium. On NTS PHP the process is killed
``hard_timeout`` seconds (2 by default) after the time limit runs out, without running shutdown
functions; on ZTS PHP the limit only takes effect when PDFium returns, and other threads get
``BusyException`` meanwhile. With PDFs you do not trust, run this work where it can be killed from
outside, for example under PHP-FPM's ``request_terminate_timeout``.

Ordinary large lists can be slow too: ``attachments()`` takes about two seconds for 20,000 files
stored as a tree of nested parts of 50 entries each. A flat list stays fast.

``extractPages()`` and ``merge()`` copy pages only, so their results have no attachments.


.. _memory-limit:

Memory limit
------------

When PHP runs out of ``memory_limit``, it normally stops with a fatal error. This extension checks
first and throws :php:exc:`Pdfium\\MemoryException` instead, so you can catch it.

Rough memory needed:

- ``fromFile()``: the size of the file; nothing with ``lazy: true``.
- ``render()``: about 7 bytes per pixel; 8 with ``transparent``, 5 with ``grayscale`` and 6 with
  both.
- ``text()``: about 8 bytes per character.
- ``textIn()`` and ``textInEach()``: about 30 bytes per character on the page, whatever the
  rectangles, and about 600 bytes per rectangle.
- ``chars()``: about 600 bytes per character returned.
- ``search()``: about 8 bytes per character on the page, 10 bytes per byte of the needle, and
  about 500 bytes per match.
- ``webLinks()``: about 500 bytes per result.
- ``permanentId()`` and ``changingId()``: the length of the identifier.
- ``bookmarks()``: about 2 KB per entry, 5 bytes per character of its title, and the length of
  its URI or file path.
- ``openAction()`` and ``closeAction()``: about 1 KB, and the length of the URI or file path.
- ``javaScripts()``: about 100 bytes per script and 5 bytes per character of each name and script.
- ``links()`` and ``linkAt()``: about 2 KB per link, 256 bytes per quad, and the length of its
  URI or file path.
- ``attachments()``: about 100 bytes per attachment.
- ``Attachment::name()`` and ``metadata()``: about 5 bytes per character.
- ``Attachment::contents()``: the size of the file. See :ref:`attachments` for PDFium's own copy.
- ``addAttachment()``: 2 bytes per character of the name and description.
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
- Freeing a document, page, text page, page iterator or attachment, and ``PageIterator::next()``
  and ``rewind()``, never throw it, but may wait for the other thread to finish.
- ``fromFile()`` reads the file before it checks. For a stream you cannot read twice, such as
  ``php://stdin``, read it into a string yourself and retry with ``fromString()``.
- Do not ``pcntl_fork()`` while another thread may be using PDFium. The child would find PDFium
  busy forever and hang when it frees a document or page.
