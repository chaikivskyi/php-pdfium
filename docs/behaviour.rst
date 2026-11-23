Behaviour
=========


Lifetimes
---------

There is no ``close()``. PHP frees a document once nothing refers to it. These keep a document
alive:

- the :php:class:`Document <Pdfium\\Document>` object itself;
- any :php:class:`Page <Pdfium\\Page>`, :php:class:`TextPage <Pdfium\\TextPage>`,
  :php:class:`PageIterator <Pdfium\\PageIterator>`, :php:class:`Attachment <Pdfium\\Attachment>`,
  :php:class:`Annotation <Pdfium\\Annotation>` or :php:class:`FormField <Pdfium\\FormField>` from it.

A :php:class:`TextPage <Pdfium\\TextPage>`, :php:class:`Annotation <Pdfium\\Annotation>` or
:php:class:`FormField <Pdfium\\FormField>` also keeps its page loaded. An ``Attachment`` from an
annotation keeps that annotation alive.

A document from ``fromString()`` also keeps its string in memory. One from
``fromFile(lazy: true)`` keeps its file open.

These do **not** keep a document alive:

- a :php:class:`Bitmap <Pdfium\\Bitmap>` rendered from one of its pages;
- a document made from it with ``extractPages()``, ``merge()`` or ``nUp()``, or a document its
  pages were copied into with ``insertPages()``. These copy the pages, so you can free the source
  right away.

To free a document, ``unset()`` it and every page, text page, iterator, attachment, annotation and
form field you took from it.


.. _pdfium-defaults:

PDFium defaults
---------------

The extension follows PDFium and adds few rules of its own:

- ``extractPages()``, ``insertPages()``, ``merge()`` and ``nUp()`` refuse an encrypted source
  unless you pass ``removeSecurity: true`` (below).
- ``setLanguage()`` and ``addAttachment()`` refuse a few values PDFium would read back wrongly; see
  :ref:`accessibility`.
- For a document from ``fromFile(lazy: true)``, a save never overwrites its file in place.
- Once such a document's file could not be read in full, it refuses to be saved, split, merged,
  copied from with ``insertPages()`` or laid out with ``nUp()``; see :ref:`large-files`.

**Permissions.**
   ``permissions()`` reports what the document allows; nothing enforces it. For example, you can
   read the text of a document that forbids copying.

   Page edits work whatever ``permissions()`` says about modifying or assembling the document, and
   do not change what it reports.

**Removing encryption.**
   ``save()`` and ``toString()`` keep the encryption. Pass ``removeSecurity: true`` to save without
   it.

   PDFium cannot encrypt a new document. So ``extractPages()``, ``merge()`` and ``nUp()`` throw
   :php:exc:`Pdfium\\UnsupportedException` for an encrypted source unless you pass
   ``removeSecurity: true``. Then the result is an ordinary unencrypted document.

   This applies whether you opened the source with a user password or it only has an owner
   password.

   ``insertPages()`` does the same when the source is another, encrypted document, because the
   source's password no longer protects the copied pages. They take the encryption of the document
   they land in, if it has any.

**Split, merge and nUp() output.**
   ``extractPages()`` keeps its document's viewer preferences and ``merge()`` the first document's,
   so a right-to-left document still reads right to left. ``nUp()`` keeps none of them.

   The result has no bookmarks, attachments or language, is not tagged, and has none of the
   sources' metadata. ``metadata()`` reports only ``creator`` and ``producer`` "PDFium" and
   ``creationDate``, the creation date of the new document.

   ``extractPages()``, ``merge()`` and ``nUp()`` with one page per sheet copy form fields. The
   fields still draw and accept input, but the result has no ``/AcroForm``, so ``formType()`` is
   ``None``.

   ``nUp()`` with several pages per sheet leaves out links, form fields and other annotations.
   PDFium has no API to set metadata, add encryption or create bookmarks.

**Reading and writing files.**
   Both go through PHP streams, so stream wrappers and ``open_basedir`` work.

   ``fromFile()`` reads the whole file into PHP memory (counted against ``memory_limit``) and never
   touches it again, so you can save over a file while its document is open. With
   ``lazy: true``, ``fromFile()`` keeps the file open and reads it as needed; see
   :ref:`large-files`.

   Saving over an existing local file writes a complete new file next to it and renames it into
   place, keeping the owner, mode and extended attributes.

   The save overwrites the file in place instead when it cannot do that:

   - the file has other hard links;
   - the directory is read-only;
   - someone else owns the file;
   - the extended attributes or ACLs cannot be copied;
   - the system does not support extended attributes.

   Where the filesystem can reserve the space first, a full disk then leaves the file untouched.
   Elsewhere (macOS, ZFS, NFS, copy-on-write filesystems) a failed write can leave it damaged.

   A failed write deletes the half-written file if the save created it.

   Through stream wrappers (``ftp://``, ``s3://``, your own), a failed write deletes nothing. The
   extension cannot tell reliably whether the target existed, and the wrapper knows best how to
   clean up.

**Incremental saves.**
   ``incremental: true`` writes the original file as it was, from its ``%PDF`` header on, and
   appends a section after it. It drops any bytes before the header, which some files have.

   The appended section holds the changes, plus unchanged copies of the objects PDFium has read:
   the document catalog, the metadata once you call ``metadata()``, and a page's objects once you
   open it. An encrypted document stays encrypted.

   PDFium writes a full copy instead, and reports success, in two cases:

   - with ``removeSecurity: true``;
   - for a document from ``create()``, ``extractPages()``, ``merge()`` or ``nUp()``, which has no
     original bytes.

   To check that a save was incremental, test that it starts with the original from its ``%PDF``
   on:

   .. code-block:: php

      $fromHeader = substr($original, strpos($original, '%PDF'));
      $wasIncremental = str_starts_with($saved, $fromHeader);

   Each incremental save appends one section to the bytes the document was opened from, even when
   nothing changed.

   A second save of the same ``Document`` also starts from those bytes, not from the first save's
   output. Opening the result and saving it incrementally adds another section, so a file updated
   that way over and over keeps growing.

   The original is not rewritten, so the ranges a digital signature covers stay as they were.
   Whether a viewer still accepts the signature depends on what you read as well as what you
   changed: some validators treat any object written after signing as a modification.

   The extension does not check signatures.

   Most files from PDF 1.5 on index their objects with a cross-reference stream. For those, PDFium
   writes the appended index without the ``/Type /XRef`` entry the format requires.

   PDFium reads the result, but other tools cannot (qpdf, for one), and a signature validator may
   not get as far as the signature.

**The version option.**
   ``version`` sets the PDF version in the file header:

   .. list-table::
      :header-rows: 1
      :widths: 30 70

      * - ``version``
        - Header written
      * - ``10`` to ``17``
        - ``%PDF-1.0`` to ``%PDF-1.7``
      * - any other value, or not given
        - The source's 1.x version, or 1.7 for a document from ``create()``, ``extractPages()``,
          ``merge()`` or ``nUp()``. PDFium ignores the value without an error.

   PDFium converts nothing in the document to suit an older version.

   An incremental save keeps the original header, so ``version`` applies only when the save falls
   back to a full copy.

   A full save of a PDF 2.0 file writes a ``%PDF-1.0`` header unless you pass a ``version`` from 10
   to 17. ``version: 20`` is outside the range, so PDFium ignores it like any other value.

**File identifiers.**
   Every save, full or incremental, writes a new changing identifier (see ``changingId()``), so two
   saves of the same document are normally not byte-identical.

   An incremental save keeps the permanent identifier. Compare documents by their content, not
   their bytes.

**Generated appearance streams.**
   PDFium sets up form support for a document the first time either happens:

   - any page of it renders with annotations (the default);
   - you call any form method other than ``Document::formType()``.

   From then on PDFium generates an appearance stream for each form field that has none, and a
   later ``save()`` or ``toString()`` includes it.

   A render with annotations does the same for annotations of most types. On a form that asks for
   new appearances (``/NeedAppearances true``), it can replace the fields' existing streams. See
   :ref:`annotations`.

   ``annotations: false`` leaves the document unchanged.


.. _rendering:

Rendering
---------

By default ``render()`` draws the page the way a PDF viewer does. Options change that:

.. list-table::
   :header-rows: 1
   :widths: 25 35 40

   * - Option
     - Default
     - With the option
   * - ``region``
     - The area where the media box and crop box overlap, at its size after rotation
     - Only that rectangle of the page; see below
   * - ``transparent: true``
     - White background, no transparency
     - Transparent background
   * - ``annotations: false``
     - Annotations and form fields drawn
     - Only the page's content
   * - ``grayscale: true``
     - Colour
     - Gray

The image size is ``points × dpi / 72``, rounded, and at least 1 pixel. An A4 page
(595.28 × 841.89 points) at 150 DPI gives 1240 × 1754 pixels.

The ``region`` :php:class:`Rect <Pdfium\\Rect>` is in points, as for
:ref:`text positions <text-positions>`, so you can crop a search match directly:

.. code-block:: php

   $match = $page->textPage()->search('Total')[0];
   $page->render(dpi: 300, region: $match->rects[0])->savePng('/tmp/total.png');

The image is that part of a whole-page render at the same DPI, to within a pixel at the edges. It
rotates with the page, so width and height swap at 90° and 270°.

Its size is the rectangle's size × dpi / 72, rounded, and at least 1 pixel. Parts outside the page
come out as background.

A rectangle with no width or height throws ``\ValueError``. So does one that does not overlap the
page; touching its edge is not enough.

Limits:

.. list-table::
   :header-rows: 1
   :widths: 50 50

   * - Limit
     - Value
   * - DPI
     - 1 to 2400
   * - Pixels per side
     - 65,500 at most, the largest JPEG allows
   * - Pixels per image
     - 100 million at most
   * - Whole page at that DPI, with ``region``
     - 4,194,304 pixels a side at most, far beyond any real page
   * - JPEG quality
     - 1 to 100 (default 85)

Going over a limit throws ``\ValueError``. You cannot change the limits.

With ``region``, the other size limits apply to the region's image, so you can render a small area
at a DPI the whole page would not allow.

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
file open, and PDFium reads the parts it needs when it needs them.

Opening a well-formed file and counting its pages typically reads a few kilobytes. Rendering a page
reads what that page uses.

A damaged cross-reference table (the index of where each object is) can make PDFium read the whole
file while opening.

In one measurement, a 120 MB file whose pages are large images needed about 20 MB to open and
render one page, against about 140 MB when loaded whole.

The file does not need to be linearized ("fast web view"); that made no difference in this
measurement.

- **Local files only.** A path through a stream wrapper (``s3://``, ``ftp://``, ``data:``,
  ``php://``, ``phar://``, your own) throws :php:exc:`Pdfium\\UnsupportedException`.
- If PHP cannot use the wrapper, because it is not registered or ``allow_url_fopen`` disables it,
  the call throws :php:exc:`Pdfium\\FileException` instead. ``open_basedir`` applies, and
  ``file://`` paths are local.
- **Do not change the file while the document exists.** PDFium cannot tell, and would read a mix
  of old and new bytes.
- Deleting or renaming the file is fine: the document keeps reading the open file, and its disk
  space stays in use until PHP frees the document.
- Each lazy document holds one open file, which counts against the process's open-file limit.
- PDFium keeps what it has read until PHP frees the document. Rendering every page or a full save
  reads the whole file, and PDFium's memory grows to about the file's size.
- ``memory_limit`` does not count PDFium's own memory, only what the extension builds in PHP. So
  you can open a file larger than ``memory_limit`` lazily and render its pages.
- ``save()`` and ``toString()`` still build the whole output in PHP memory, counted against
  ``memory_limit``, as for any document.
- A full save is about the size of the file and an incremental save at least that. Saving a file
  larger than ``memory_limit`` throws :php:exc:`Pdfium\\MemoryException`.
- You can save over the file the document reads. The save writes a new copy and renames it into
  place, and the document keeps reading the old one.
- When the save would have to overwrite the file in place instead (see :ref:`pdfium-defaults` for
  when), it throws :php:exc:`Pdfium\\FileException` and leaves the file alone.
- That check covers every save the extension makes in the same request or thread, by any document
  or bitmap. It does not cover saves through a stream wrapper, or writes by other threads or
  processes.
- If the file is truncated or a read fails while the document is open, anything you read from the
  document may come back incomplete without an error, or fail as it would for a damaged file. That
  covers pages, text, bookmarks, links, attachment contents and ``javaScripts()``.
- From then on, ``save()``, ``toString()``, ``extractPages()``, ``merge()`` and ``nUp()`` throw
  :php:exc:`Pdfium\\FormatException` rather than produce an incomplete copy.
- ``insertPages()`` throws the same when it reads such a document, as source or as target. It
  first removes the pages it copied into a target that is not such a document, so that target is
  left as it was.
- A target that is such a document can be left with an undefined page order, as after a
  :ref:`failed edit <failed-edits>`.

The extension does not expose PDFium's API for opening a document while it is still downloading
(``fpdf_dataavail.h``). For a remote file, download it to a temporary file and open that with
``lazy: true``.


.. _fonts:

Fonts
-----

A PDF either carries its fonts or only names them. For a font it only names, PDFium looks in the
system's font directories. When it finds nothing suitable, it draws the text with a built-in
substitute.

That is never an error. A page can render with the wrong letter shapes without any sign from the
extension, and the substitute may not draw characters it lacks at all.

Most PDFs name the 14 standard fonts (Helvetica, Times, Courier and their styles, Symbol and
ZapfDingbats) rather than carry them.

PDFium draws Helvetica, Times and Courier with Arial, Times New Roman and Courier New when it finds
them in the font directories, and with its built-in fonts otherwise.

The ini setting ``pdfium.font_paths`` lists the directories PDFium scans, separated by ``:`` like
``include_path``:

.. code-block:: ini

   pdfium.font_paths = /app/fonts:/usr/share/fonts:/usr/local/share/fonts

.. list-table::
   :header-rows: 1
   :widths: 25 75

   * - Aspect
     - Behaviour
   * - Value
     - **Replaces** PDFium's own directories. On Linux those are ``/usr/share/fonts``,
       ``/usr/local/share/fonts``, ``/usr/share/X11/fonts/Type1`` and
       ``/usr/share/X11/fonts/TTF``. List the ones you still want.
   * - Default
     - Empty: PDFium's own directories. So does a value with no directory in it, such as ``:``.
   * - When read
     - Once, when PHP starts. Set it in ``php.ini`` or a file in its ``conf.d`` directory, or with
       ``-d`` on the command line. Restart PHP-FPM after changing it.
   * - Not honoured
     - ``ini_set()`` refuses it. A value set later, with ``php_admin_value`` in a PHP-FPM pool or
       an Apache virtual host, has no effect on PDFium, even though ``ini_get()`` and
       ``phpinfo()`` show it.
   * - Missing directory
     - Ignored, without a warning. Use absolute paths.
   * - Scope
     - The whole process: every document sees the same fonts.

This is the usual fix for a container image with no fonts installed: copy the fonts your PDFs name
into a directory and list it.

A copy of Arial, Times New Roman or Courier New there also changes how Helvetica, Times or Courier
text looks.


.. _text-positions:

Text positions
--------------

Positions are in the PDF's own coordinates: points (1/72 inch), with ``y`` growing upwards. On most
pages the origin (0, 0) is at the bottom left corner. The page's rotation is not applied.

To mark a character on an image from ``render()``, convert its position to pixels:

.. code-block:: php

   $across = $char->x * $dpi / 72;
   $down = ($page->height() - $char->y) * $dpi / 72;

This holds for a page with no rotation whose visible area, where the media box and crop box
overlap, starts at 0, 0.

``mediaBox()`` and ``cropBox()`` show where it starts, unless the page takes its boxes from its
page tree.

For a ``region`` image, measure from the region's top left corner instead. That works on any page
with no rotation:

.. code-block:: php

   $across = ($char->x - $region->left) * $dpi / 72;
   $down = ($region->top - $char->y) * $dpi / 72;

Character indexes follow PDFium's count:

- A character PDFium adds, such as a space between words or a line break, has an index too. Its
  ``Char`` has ``generated`` set to ``true``.
- A character outside the Basic Multilingual Plane, such as an emoji, takes two indexes. The first
  ``Char`` has the whole character as ``text`` and the second has ``""``.
- When a word is hyphenated across two lines, PDFium joins it and no line break follows. The
  hyphen's ``Char`` has ``hyphen`` set to ``true`` and its ``text`` is ``"-"``, as in ``text()``.

A character PDFium cannot map to Unicode has an empty ``text``. ``text()``, ``textIn()`` and the
text of search matches leave it out.

Joining every ``text`` usually gives the same string as ``text()``, but not always: ``text()`` and
``textIn()`` leave control characters out.

``search()`` looks for the needle in what ``text()`` shows, so a match can run over a character
PDFium cannot map.


.. _navigation:

Navigation
----------

Link rectangles and quads, and the point you pass to ``linkAt()``, are in points, as for
:ref:`text positions <text-positions>`, without the page's rotation.

A broken outline can loop, with an entry pointing back to an earlier one. ``bookmarks()`` lists
each entry once: when it reaches an entry it already listed, it skips that entry and the rest of
its level.

``bookmarks()`` also goes at most 100 levels deep: entries on the 100th level have no children.
Real outlines are far shallower.

A bookmark or link has a plain destination, an action, or neither. To find the page it leads to,
read ``destination``:

.. list-table::
   :header-rows: 1
   :widths: 40 30 30

   * - The bookmark or link has
     - ``destination``
     - ``action``
   * - A plain destination
     - set
     - ``null``
   * - A ``"GoTo"`` action
     - set, equal to ``action->destination``
     - set
   * - Any other action
     - ``null``
     - set
   * - Neither
     - ``null``
     - ``null``

If a PDF gives both a destination and an action, the action wins.

A :php:class:`Destination <Pdfium\\Destination>` always points into this document. When its page
cannot be found, ``destination`` is ``null``.

Actions into another PDF (``"GoToR"``) or an embedded PDF (``"GoToE"``) have no destination: the
page they lead to is not available.

``filePath`` names the other file. For ``"GoToE"`` it is ``null`` when the embedded PDF is attached
to this document.

The extension does not check a ``"URI"`` action's ``uri`` for encoding. PDF asks for 7-bit ASCII,
but real files hold UTF-8 and other encodings, so check it before you use it as text.

Actions PDFium does not read, such as JavaScript, form submission and named actions like *next
page*, have the type ``"Unsupported"`` and nothing else set.


.. _javascript:

JavaScript
----------

``javaScripts()`` reads only the scripts in the document's script list (its ``/JavaScript`` name
tree). It leaves out:

- scripts chained after an entry with ``/Next``;
- scripts on page actions, links, annotations and form fields; those actions still have the type
  ``"Unsupported"``;
- the document's open action (``/OpenAction``) and document actions (``/AA``, such as before
  closing or printing), which no method reads. ``Page::openAction()`` reads only a page's.

So an empty result does not mean the file has no JavaScript.

It also leaves out an entry that is not JavaScript, or whose source is missing or is not a string
or stream. It returns an entry whose source is empty with an empty ``script``.

The extension never runs anything; it only returns the source. That source comes from the file, so
treat it as untrusted text. NUL bytes in a name or script are kept, so nothing is cut short.

PDFium reads a damaged script list, one that loops or nests too deeply, as far as it can follow
it.

A crafted PDF of a few kilobytes can keep ``javaScripts()`` busy for minutes. The extension cannot
detect such a file, and ``max_execution_time`` cannot interrupt PDFium.

On NTS PHP, PHP kills the process ``hard_timeout`` seconds (2 by default) after the time limit runs
out, without running shutdown functions.

On ZTS PHP the limit takes effect only when PDFium returns, and other threads get
``BusyException`` meanwhile.

With PDFs you do not trust, run this work where something outside can kill it, for example under
PHP-FPM's ``request_terminate_timeout``.


.. _attachments:

Attachments
-----------

``addAttachment()`` and ``deleteAttachment()`` change the loaded document, never the file or
string it came from. ``save()`` or ``toString()`` writes the change out.

A saved PDF leaves out a deleted file that came with the loaded document, unless something else in
the document still points to it, such as a FileAttachment annotation.

A file you added since loading and then deleted stays in the saved PDF, unused.

PDFium keeps the list sorted by name, so adding or deleting a file can shift the indexes of the
others.

After either call, every :php:class:`Attachment <Pdfium\\Attachment>` of this list fetched before
it is stale: its methods throw ``PdfiumException`` instead of reading a different file.

To read a file again, call ``attachments()`` or ``attachment()`` for a new ``Attachment``, or use
the one ``addAttachment()`` returned: it is not stale. To delete several files, see
``deleteAttachment()`` in :doc:`php_api`.

Strictly, PDFium sorts and checks the list by a key it does not show. PDFium sets that key to the
file name, but other tools may not.

So two entries can have the same ``name()``, and ``addAttachment()`` can refuse a name that no
``name()`` shows.

On macOS, ``name()`` shows each ``/`` in a file name as ``:``, and drops the first ``/`` of a name
that starts with ``/Mac``, because PDFium converts file names to the platform's form.

When PDFium cannot decode a file, ``contents()`` returns its bytes as stored, still compressed or
encoded. That happens when the stream is damaged, or when it is ``/RunLengthDecode`` and would
decode to 20 MB or more.

PDFium decodes a file in its own memory, which ``memory_limit`` does not count, before
``contents()`` can check the size.

A small compressed file that expands to gigabytes can exhaust the process's memory before
``MemoryException`` is thrown.

``addAttachment()`` also gives PDFium a copy of ``$contents``, and copies of the name and
description several times their size, outside ``memory_limit``. If PDFium cannot allocate them, it
ends the process.

Saving an encrypted document encrypts a file you added to it, unless you pass
``removeSecurity: true``.

``addAttachment()`` throws ``ValueError`` for:

- names and descriptions that break the same rules as ``setLanguage()``; see
  :ref:`accessibility`;
- ``$contents`` longer than 2147483647 bytes. The limit is PDFium's: it stores a file's size as a
  32-bit number and refuses anything longer, so raising ``memory_limit`` does not help.

PDFium reads a damaged list, one that loops or nests too deeply, as far as it can follow it. It
does not count entries past that point, and ``addAttachment()`` throws ``PdfiumException`` on a
list that nests too deeply.

A crafted PDF of a few kilobytes can keep ``attachments()``, ``attachment()``,
``addAttachment()`` and ``deleteAttachment()`` busy for minutes, and ``index()`` of an added file
too. ``attachmentCount()`` and the other ``Attachment`` methods stay fast.

The extension cannot detect such a file, and ``max_execution_time`` cannot interrupt PDFium.

On NTS PHP, PHP kills the process ``hard_timeout`` seconds (2 by default) after the time limit runs
out, without running shutdown functions.

On ZTS PHP the limit takes effect only when PDFium returns, and other threads get
``BusyException`` meanwhile.

With PDFs you do not trust, run this work where something outside can kill it, for example under
PHP-FPM's ``request_terminate_timeout``.

Ordinary large lists can be slow too: ``attachments()`` takes about two seconds for 20,000 files
stored as a tree of nested parts of 50 entries each. A flat list stays fast.

An ``Attachment`` from a FileAttachment annotation is separate from this list: ``attachments()``
lists only the files the document's list names.

When the list names the same file, it shows up in both, and only the ``Attachment`` from
``attachments()`` has an index. See :ref:`annotations`.

``extractPages()``, ``merge()``, ``nUp()`` and ``insertPages()`` copy pages only, not the attachment
list.

A copied page keeps its annotations, and a FileAttachment annotation keeps its file. The exception
is ``nUp()`` with several pages per sheet, which leaves annotations out.

.. _accessibility:

Accessibility
-------------

``isTagged()`` reports what the document says about itself (``/Marked`` in its ``/MarkInfo``). It
does not check that the document has a usable structure tree.

``language()`` returns the language as PDFium reads it, without checking that it is a language
tag. It returns ``null`` when the document has none, when it is empty, and when it is not text (a
number, for example).

What PDFium reads can differ from what the file stores:

- It ends at the first NUL character. In a language stored as single bytes, bytes 0x7F, 0x9F and
  0xAD also count as NUL.
- In a language stored as UTF-16 or UTF-8, PDFium drops the escape character (U+001B) and anything
  between a pair of them.
- PDFium returns a language stored as a name as text, and one stored as a stream as the stream's
  decoded contents.

``setLanguage()`` changes the loaded document, never the file or string it came from. ``save()``
or ``toString()`` writes the change out.

It works even when :php:class:`Permissions <Pdfium\\Permissions>` says ``modify`` is ``false``. In
an encrypted document, the save encrypts the language unless you pass ``removeSecurity: true``.

An empty string marks the language as unknown, and ``language()`` then returns ``null``. You cannot
remove the entry.

``setLanguage()`` throws ``ValueError`` for a value PDFium would read back wrongly, and
``addAttachment()`` does the same for names and descriptions. Such a value either:

- contains the escape character (U+001B), or
- starts with ``þÿ``, ``ÿþ`` or ``ï»¿`` (U+00FE U+00FF, U+00FF U+00FE, U+00EF U+00BB U+00BF) and
  has only characters PDF can store as single bytes. A reader takes that start as a byte order
  mark.

Characters PDF can store as single bytes are most of ASCII and Latin-1, and a few symbols such as
``€`` and ``•``. One other character, such as a Cyrillic letter, makes the value acceptable.

``setLanguage()`` gives PDFium copies of the language, several times its size, that
``memory_limit`` does not count. If PDFium cannot allocate them, it ends the process.

PDFium decodes a language stored as a stream before ``language()`` can check its size. So a
crafted PDF under a megabyte can exhaust the process's memory before ``MemoryException`` is thrown.

``Document::metadata()`` has the same weakness. With PDFs you do not trust, limit the process's
memory from outside.

``extractPages()`` and ``merge()`` copy pages only, so their results have no language and are not
tagged.

The extension does not offer the structure tree (headings, paragraphs, alternative text). To read
it, PDFium (as of ``chromium/8125``) follows the document's ``/ParentTree`` with no depth limit or
loop check, so a crafted file can crash PHP.

The extension will offer it once PDFium bounds that walk.

.. _signatures:

Signatures
----------

The list holds every signature field at the top of the form's field list, in order:

- It lists unsigned fields too, with ``null`` properties and an empty ``byteRange``. So
  ``signatureCount() > 0`` does not mean the document is signed; look for a ``contents`` that is
  not ``null``.
- It does not list fields nested under another field, so it misses a signature stored on one and
  the document can look unsigned. If the parent is a signature field itself, the list shows it as
  unsigned.
- It returns a field the form lists twice, twice.

``byteRange`` offsets count bytes in the file or string you opened the document from. ``save()``
and ``toString()`` write a different file, which the offsets do not fit.

Treat every value as untrusted input:

.. list-table::
   :header-rows: 1
   :widths: 25 75

   * - Property
     - What to expect
   * - ``byteRange``
     - Not checked against the file: numbers can be negative, point past its end or come in any
       number of pairs. A number that does not fit in 32 bits comes back wrong, a fraction is cut,
       an entry that is not a number comes back as 0, and an odd last number is dropped.
   * - any
     - An empty value, or one of the wrong type, reads as missing. That includes a ``subFilter``
       stored as a string instead of a name.
   * - ``contents``
     - The exception to the row above: a number or name comes back as its text, so
       ``/Contents 5`` gives ``"5"``. Keeps the zero bytes signers pad it with.
   * - ``subFilter``, ``time``
     - The file's bytes. Can hold null bytes or invalid UTF-8, which ``json_encode()`` and
       databases refuse.
   * - ``time``
     - The file's claim, not parsed. A signed time, if any, is inside ``contents``.
   * - ``reason``
     - Converted to UTF-8; ends at its first null character.
   * - ``docMdpPermission``
     - 2 when the file names a certification without a level. A fraction is cut to a whole
       number. A level outside 1 to 3, or one that is not a number, gives ``null``.

In an encrypted document, the PDF standard leaves ``/Contents`` unencrypted. PDFium reads it as
stored only when the signature dictionary has ``/Type /Sig``, or no ``/Type`` and ``/FT /Sig``.

In any other dictionary, document timestamps (``/Type /DocTimeStamp``) included, PDFium decrypts
``contents`` like every other string, which garbles a correctly written one.

``signatures()`` gets slower with the square of the number of fields: about 3 seconds for 5,000
and a minute for 20,000. A small crafted file can reach that by listing one field many times.

``signatureCount()`` stays fast, so check it first on PDFs you do not trust.

``max_execution_time`` stops ``signatures()`` between two fields. Until then, on ZTS PHP, other
threads get ``BusyException``.

``extractPages()`` and ``merge()`` copy pages only, so their results have no signatures.


.. _annotations:

Annotations
-----------

**Changes.**
   ``addAnnotation()``, ``removeAnnotation()`` and the setters of
   :php:class:`Annotation <Pdfium\\Annotation>` change the loaded document, never the file or string
   it came from. ``save()`` or ``toString()`` writes the change out.

   Two ``Annotation`` objects for the same annotation see each other's changes. So do two
   :php:class:`Page <Pdfium\\Page>` objects for the same page, from two ``page()`` calls.

   The other ``Page`` counts, reads and draws an annotation you add, change or remove through one.
   An ``Annotation`` from one throws once you remove its annotation through the other.

**Adding.**
   ``addAnnotation()`` adds these 13 types: ``"Text"``, ``"Link"``, ``"FreeText"``, ``"Square"``,
   ``"Circle"``, ``"Highlight"``, ``"Underline"``, ``"Squiggly"``, ``"StrikeOut"``, ``"Stamp"``,
   ``"Ink"``, ``"Popup"`` and ``"FileAttachment"``.

   You can read and change other types, such as ``"Line"``, ``"Polygon"`` or ``"Widget"``, but not
   add them.

   A new annotation has only its type and rectangle. Give it what its type needs, and a colour with
   ``setColor()``:

   .. list-table::
      :header-rows: 1
      :widths: 30 70

      * - Type
        - Call
      * - Highlight
        - ``addQuadPoints()`` for its quad points
      * - Ink
        - ``addInkStroke()`` for its strokes
      * - Link
        - ``setUri()`` for its URI
      * - FileAttachment
        - ``setAttachment()`` for its file

**Appearance streams.**
   A viewer draws an annotation from its appearance stream, or appearance: a small piece of PDF
   content that ``appearance()`` reads.

   An annotation can have one for each mode: normal, rollover (under the pointer) and down
   (pressed). A new annotation has none.

   The next ``render()`` of a page with annotations, the default, generates a normal appearance for
   each annotation of these types that has none: ``"Text"``, ``"FreeText"``, ``"Square"``,
   ``"Circle"``, ``"Highlight"``, ``"Underline"``, ``"Squiggly"``, ``"StrikeOut"`` and ``"Ink"``.

   It skips one with ``FLAG_HIDDEN``. PDFium stores the appearance in the document, so a later
   ``save()`` includes it.

   Without such a render the saved PDF has no appearance for them, and a viewer that relies on it
   shows nothing. Render the page once before saving when other viewers must show the annotation.

   Other types get none: ``"Stamp"``, ``"FileAttachment"``, ``"Link"`` and ``"Popup"`` (even a Popup
   that belongs to another annotation), and the types PDFium cannot add, such as ``"Line"`` or
   ``"Polygon"``. Form fields are the exception; see Form fields below.

   A Stamp draws nothing until ``setAppearance()`` gives it one. PDFium gives that stream the
   annotation's rectangle as its bounding box, so the stream draws in page coordinates.

   Once an annotation has a normal appearance stream, whether generated, set with
   ``setAppearance()`` or written by another tool:

   .. list-table::
      :header-rows: 1
      :widths: 35 65

      * - Call
        - Effect
      * - ``render()``
        - Draws it as it is and does not generate a new one, except for a Widget on a form that
          asks for new appearances (see below).
      * - ``color()``, ``interiorColor()``
        - Return ``null``.
      * - ``setColor()``, ``setInteriorColor()``
        - Throw ``PdfiumException``, saying to call ``clearAppearance()`` first. The extension
          never drops an appearance by itself.
      * - ``addInkStroke()``, ``removeInkStrokes()``
        - Change the strokes, but ``render()`` keeps drawing the old ones.
      * - ``setRect()``
        - Accepted. ``render()`` draws the appearance in the new rectangle, except a Highlight,
          Underline, Squiggly or StrikeOut whose appearance PDFium generated, which stays at its
          quad points.
      * - ``addQuadPoints()``, ``setQuadPoints()``
        - Change the areas, but ``render()`` keeps drawing the old appearance, also in a saved
          PDF, until ``clearAppearance()``. It stays in place, except one PDFium generated for a
          Highlight, Underline, Squiggly or StrikeOut: when the box around the new areas no
          longer holds it, PDFium moves and stretches it to that box.
      * - ``setBorder()``
        - Removes the appearance streams of every mode, including one another tool wrote, with
          the same result as ``clearAppearance()`` below.

   An empty normal stream counts too: ``appearance()`` then returns ``null``, but ``hasKey('AP')``
   is ``true``. Rollover and down streams alone do not count.

   ``clearAppearance()`` removes the appearance stream of one mode; for the normal mode, the
   rollover and down ones too.

   The next render generates a new one only for the types listed above and for form fields. Any
   other type, such as ``"Stamp"``, or ``"Line"`` or ``"Polygon"`` from another tool, is left
   without one and draws nothing.

   The extension reads and writes an appearance stream as a PDF text string. ``setAppearance()``
   stores it one byte per character, in PDFDocEncoding, when every character has a byte there.

   Otherwise it stores all of it as UTF-16, which draws nothing. ``appearance()`` decodes a stream
   the same way.

   Keep streams to ASCII: bytes above 0x7F that another tool wrote, such as UTF-8 text in a string,
   do not read back as stored.

**Removing.**
   Removing an annotation moves the annotations after it down one index; ``index()`` always gives
   the current one.

   Every ``Annotation`` object for the removed annotation throws ``PdfiumException`` from then on,
   so none can change an annotation that is no longer on its page.

   Objects for the other annotations stay usable, and so do ``FormField`` objects for the other
   widgets. A ``FormField`` for a removed Widget throws. To remove several by index, remove from
   the highest down.

   A saved PDF leaves out a removed annotation, and anything from the loaded document that only it
   used, such as its file.

   Whatever something else still points to stays, such as an annotation that a Popup names as its
   ``/Parent``, or the form field of a removed Widget.

**Links.**
   ``linkAt()`` remembers a page's links. The first call for a page, through any ``Page`` object,
   reads the page's link annotations and keeps that list while the document is loaded.

   After ``addAnnotation()``, ``removeAnnotation()`` or ``remove()`` on that page, it still finds a
   removed link and does not find an added one.

   It does see a new URI or rectangle of a link it already listed. A document loaded from the saved
   PDF starts with the current links.

   ``links()`` and ``Annotation::link()`` always read the page as it is, so use them after adding
   or removing annotations.

**Linked annotations.**
   ``linkedAnnotation()`` returns only annotations in this page's annotation list, so ``index()``
   and ``remove()`` always work on what it returns.

   It returns ``null`` when the key points to:

   - an annotation of another page;
   - a dictionary in no page's list;
   - an annotation removed from this page;
   - a dictionary with no ``/Type /Annot``. PDF makes that entry optional, so some files leave it
     out.

   Take an annotation of another page from that page's ``annotations()``. A key that points back to
   the annotation itself gives a second object for it.

**File attachments.**
   A FileAttachment annotation holds one file. Read it with ``attachment()`` and replace it with
   ``setAttachment()``.

   The file need not be in the document's attachment list. The ``Attachment`` from the annotation
   throws from ``index()`` even when it is.

   That ``Attachment`` keeps the annotation alive and asks the annotation for its file on each call,
   so it always reads the annotation's current file.

   After ``setAttachment()``, or ``setStringValue()`` with the key ``FS``, an ``Attachment`` you
   fetched before reads the new file. Adding or deleting a document file does not affect it.

   Once you remove its annotation from the page, or the annotation has no file, the ``Attachment``
   throws ``PdfiumException``.

   ``setAttachment()`` replaces the file in steps. When PDFium cannot store the contents or
   description, it has already dropped the previous file and cannot undo it.

   The ``PdfiumException`` says so, and the annotation is left with a file entry without that
   content.

   A saved PDF leaves out a replaced file that came with the loaded document. A file an earlier
   ``setAttachment()`` set stays in it, unused.

**Encrypted documents.**
   In an encrypted document, the save encrypts the string values, appearance streams and files you
   set on annotations, unless you pass ``removeSecurity: true``.

**Form fields.**
   You list, read, change and remove Widget annotations, the form fields, like the others.
   Removing a Widget only takes it off its page: its field stays in the document's form, and in a
   saved PDF.

   After you change or remove a Widget of the document, the next ``render()`` with annotations of
   each :php:class:`Page <Pdfium\\Page>` object reloads the page's form fields, as for a newly
   loaded ``Page``. A change to any other annotation does not.

   That render, and the first render with annotations of each ``Page`` object, generates an
   appearance stream for every field on the page that has none.

   On a form that asks for new appearances (``/NeedAppearances true`` in the document's
   ``/AcroForm``), it generates one for every field on the page, replacing the one the field has.

   There, a stream that ``setAppearance()`` gives a Widget lasts until the next such render of its
   page. A ``save()`` or ``toString()`` before that render writes your stream; one after it writes
   PDFium's.

   On other forms the stream from ``setAppearance()`` stays. Fields on pages you do not render keep
   the appearance they have.

**Damaged lists.**
   PDFium reads a damaged annotation list as far as it can:

   - An entry that is not a dictionary, such as ``null`` or a reference to a missing object,
     counts in ``annotationCount()`` but is left out of ``annotations()``. ``annotation()`` throws
     ``FormatException`` for it.
   - An ``/Annots`` that is not an array counts as no annotations.
   - An annotation listed twice gives two objects whose ``index()`` is its first position.

     - ``remove()`` takes out that entry. The object it was called on throws from then on, and the
       others find the entry that is left.
     - ``removeAnnotation()`` takes out the entry at its index, and every object finds the entry
       that is left.

   - Values PDFium cannot use read as missing: quad points with fewer than 8 numbers give none, an
     ink stroke that is not a list of coordinate pairs is empty, and a border that is not an array
     is ``null``.
   - A rectangle that does not have exactly 4 numbers reads as 0, 0, 0, 0, and a colour with 5
     components as black.
   - A ``/Popup`` or ``/IRT`` that loops back, to the annotation itself or between two, does not
     hang ``linkedAnnotation()``: each call follows one step.

**Not covered.**
   The extension does not reach the page objects inside an annotation, such as the text and images
   of a Stamp.

   It lists a Widget with its common properties; its field's value, options and flags come from
   ``formFields()``, see :ref:`forms`.

   PDFium has no calls to remove a key, to write a value other than a string, or to remove one area
   of the quad points.


.. _forms:

Forms
-----

**Fields and widgets.**
   One or more widgets draw a field: a radio group has one per button, and a field can repeat on
   one page or on several.

   ``Page::formFields()`` gives a :php:class:`FormField <Pdfium\\FormField>` for each widget on that
   page, back to front. ``controlIndex()`` and ``controlCount()`` place it among its field's
   widgets.

   List the other pages to reach the widgets there. It lists widgets even when the document has no
   ``/AcroForm`` or the field is missing from its ``/Fields``.

**Changes.**
   ``setValue()``, ``setChecked()``, ``select()`` and ``flatten()`` never touch the file or string
   the document came from. ``save()`` or ``toString()`` writes them out.

   The save encrypts an encrypted document with the same password, unless you pass
   ``removeSecurity: true``.

   Reading a form can also change the saved file, as rendering does; see :ref:`pdfium-defaults`.

**What** ``value()`` **returns.**
   The value PDFium stores, as UTF-8, or ``""`` when there is none:

   .. list-table::
      :header-rows: 1
      :widths: 25 75

      * - Field
        - ``value()``
      * - Text field
        - Its text: a password field's in clear, and a rich-text field's plain ``/V`` rather than
          ``/RV``.
      * - Combo box
        - The selected option's export value, not its label. ``options()`` gives labels only.
      * - List box
        - With several options selected, only the first.
      * - Checkbox, radio group
        - The checked button's export value, or ``Off``. ``isChecked()`` tells one button's state.

   Field values are not page text: ``text()`` and ``search()`` find them only after ``flatten()``.

**What** ``setValue()`` **stores.**
   PDFium takes the text as if it were typed:

   - a ``/MaxLen`` above 0 silently cuts longer text; compare ``value()`` when it matters;
   - a multi-line field stores ``\n``, ``\r\n`` and ``\r`` as ``\r\n``, a single-line field drops
     line breaks, and a tab becomes a space;
   - a character above U+FFFF, such as most emoji, is stored wrongly: U+1F600 reads back as U+F600.
     Such characters already in the document read correctly.

**Read-only fields.**
   Forms fill even when ``permissions()->fillForms`` is ``false``.

   PDFium still changes a read-only text field, combo box or list box; check
   ``flags()->readOnly`` to refuse.

   It ignores a read-only checkbox or radio button, so ``setChecked()`` throws ``PdfiumException``
   ending in "; it is read-only".

**Scripts.**
   PDFium does not update calculated, formatted and validated fields when another field changes. A
   field's own format script does not reshape what ``setValue()`` stores.

**XFA forms.**
   You read and fill the AcroForm fields of an XFA document like any others; the XFA data stays as
   it is. ``Document::formType()`` tells such documents apart.

**Combo boxes and list boxes.**
   ``select()`` replaces the selection, except in a list box with ``multiSelect``, where the other
   options keep their state. Selecting a selected option changes nothing.

   PDFium does not deselect a combo box's option, so ``select($i, false)`` on one throws "PDFium
   did not change the selection".

   ``setValue()`` works on a combo box with ``editable`` only, and selects the option whose export
   value equals the text, or none.

**Checkboxes and radio buttons.**
   Checking a radio button unchecks the others of its group. PDFium does not uncheck a radio
   button, so ``setChecked(false)`` on a checked one throws; check another button instead.

   Checkboxes of one field that share an export value change together. So do radio buttons with
   the same export value in a group with ``RadiosInUnison``.

**Fields on several pages.**
   Filling rebuilds the appearance of every widget of the field, on every page, so the saved file
   shows the new value with or without ``/NeedAppearances``.

   One exception while the document is loaded: once a second ``Page`` object for a page has drawn
   or listed its fields, the first one can stop showing fills made through other ``Page`` objects.

   Fetch that page again, or fill through a ``FormField`` from it.

**Flattening.**
   ``flatten()`` turns all of the page's annotations into page content, links and form fields
   alike. ``formFields()`` and ``links()`` are empty afterwards and the saved page has no form
   fields. You cannot undo it.

   Without ``forPrint`` it drops annotations flagged hidden or invisible. With ``forPrint`` it
   keeps only those flagged for printing and not hidden.

   PDFium draws only annotations with an appearance stream into the page. In a document with an
   ``/AcroForm``, ``flatten()`` first builds the missing ones, so a field's value or check mark
   survives even when nothing read the form before.

   The ``Page`` you flatten through shows the new content. Objects made before do not:

   - a ``FormField`` throws "The form field is gone: its widget was removed or its page flattened";
   - an :php:class:`Annotation <Pdfium\\Annotation>` throws "The annotation was removed from its
     page";
   - a :php:class:`TextPage <Pdfium\\TextPage>` keeps the old text;
   - another ``Page`` for the same page lacks the flattened content: it renders the old fields or
     none, its ``text()`` lacks the flattened text, its ``formFields()`` and ``links()`` are empty,
     and its ``flatten()`` returns ``false``. Fetch the page again with ``Document::page()``.

   A field repeated on other pages keeps its widgets there: they still read, fill and save. Until
   you open the saved document again, their ``controlCount()`` and ``controlIndex()`` still count
   the flattened widgets.

   Flattening can remove the document's ``/AcroForm`` even while other pages still have fields, and
   ``formType()`` then gives ``None``.

**Damaged forms.**
   The form methods read what PDFium can follow, and return a value or throw ``PdfiumException``:

   - a ``/Parent`` chain that loops or is very deep gives the name and type PDFium resolves, which
     can be ``""`` and ``Unknown``;
   - a widget PDFium cannot tie to a field, such as one with no ``/FT`` anywhere, is ``Unknown``:
     ``formFieldAt()`` does not find it, and its setters, ``controlIndex()`` and ``controlCount()``
     throw;
   - the form methods skip ``/Annots`` entries that are not annotations;
   - ``/Opt`` entries that are neither strings nor pairs give options with empty labels;
   - a ``/Rect`` with fewer than four numbers gives a rectangle of ``0, 0, 0, 0``.


.. _page-editing:

Page editing
------------

``deletePage()``, ``addPage()``, ``insertPages()``, ``movePages()``, ``setRotation()`` and the box
setters change the loaded document, never the file or string it came from. ``save()`` or
``toString()`` writes the changes out.

The edits leave ``metadata()`` as it was, except that ``insertPages()`` sets the producer; see
``insertPages()`` in :doc:`php_api`.

**Held pages.**
   A :php:class:`Page <Pdfium\\Page>` taken before an edit keeps working on the same page, and
   ``index()`` reports where that page is now.

   So do the :php:class:`Annotation <Pdfium\\Annotation>` and
   :php:class:`FormField <Pdfium\\FormField>` objects of the page: a fill, for one, reaches the page
   where it is now.

   Once you delete the page, the ``Page`` and every :php:class:`TextPage <Pdfium\\TextPage>`,
   ``Annotation`` and ``FormField`` made from it throw ``PdfiumException`` ("The page was deleted
   from its document") from every method, ``index()`` included.

   So does an ``Attachment`` from one of its annotations.

   After a failed edit that leaves the page order undefined (see below), they throw
   ``PdfiumException`` saying their position is unknown instead. A page deleted before that still
   says it was deleted.

   The extension reads bookmarks, links and destinations from the document on each call. Most
   destinations follow their page, and become ``null`` once you delete it.

   In some files a destination names a page number instead. That number stays the same after an
   edit, so it can then lead to another page, or be ``null``.

**Iterating while editing.**
   A ``PageIterator``, and so ``foreach ($doc as $page)``, continues by index, like a loop over an
   array that changes under it:

   .. list-table::
      :header-rows: 1
      :widths: 55 45

      * - Edit during the loop
        - Effect
      * - Delete the current page or one before it
        - Skips the next page
      * - Add or insert pages before the current one
        - Sees a page again
      * - Move a page from one side of the current position to the other
        - Can do both

   To delete several pages, go from the highest index down:

   .. code-block:: php

      for ($i = $doc->pageCount() - 1; $i >= 0; $i--) {
          if ($i % 2) { $doc->deletePage($i); }
      }

**Inherited page attributes.**
   A PDF keeps its pages in a page tree, which can group pages and give a group a rotation, a media
   box, a crop box, or resources such as fonts and images. The pages in a group take these on
   unless they have their own.

   ``rotation()``, ``width()`` and ``height()`` include what a page takes on. A box getter returns
   ``null`` for a box the page takes on.

   ``addPage()`` gives the new page its own rotation of 0. Where the group's crop box would clip
   it, ``addPage()`` also gives it its own crop box the size of the page.

   ``insertPages()`` copies what the source page has or takes from its own document's page tree. A
   copy without a rotation or crop box then takes the ones of the group it lands in.

   A copy that still has no media box gets its crop box as its media box, or US Letter without one.

   ``movePages()`` can put a page in another group. A page that took its rotation, boxes or
   resources from its old group then takes the new group's instead.

   A page moved to the end of the document takes what is set for the whole tree.

   The size and rotation can then differ from before. To keep them, read them before the move and
   set them afterwards, with the media box and crop box.

   ``width()`` and ``height()`` are measured after rotation, hence the swap:

   .. code-block:: php

      $page = $doc->page($i);
      $r = $page->rotation();
      $media = $page->mediaBox();
      $crop = $page->cropBox();
      [$w, $h] = $r % 180 ? [$page->height(), $page->width()] : [$page->width(), $page->height()];
      $c = $crop ?? $media ?? new Pdfium\Rect(0, 0, 0, 0);
      $shown = new Pdfium\Rect($c->left, $c->bottom, $c->left + $w, $c->bottom + $h);
      $doc->movePages([$i], $to);
      $moved = $doc->page($to);
      $moved->setRotation($r);
      $moved->setMediaBox($media ?? $shown);
      $moved->setCropBox($crop ?? $shown);

   ``$shown`` is the visible area. The recipe puts it at the corner of the page's own crop box,
   else of its own media box, else at the point 0, 0, which is right in most files.

   You cannot read where a box the page takes from its group starts. So if the visible area started
   elsewhere, the content shifts.

   You cannot set fonts and images at all, so a moved page that took them from its old group can
   draw and extract its text differently.

   A ``Page`` held from before a move reports the new ``rotation()`` but keeps its old ``width()``
   and ``height()``, and ``render()`` draws it at the old size.

   That lasts until you call ``setRotation()`` or a box setter on a ``Page`` at its index.
   ``Document::page()`` gives a ``Page`` with the current size.

**Sizes and box sides.**
   PDFium stores page sizes and box sides as 32-bit floats. ``addPage()``, ``nUp()`` and the box
   setters round what you pass to the nearest float.

   A value beyond the float range becomes the largest float of the same sign, about ``±3.4e38``.

   They check the rounded values: they refuse a size that rounds to 0, and a box whose left and
   right, or bottom and top, become equal.

.. _failed-edits:

**Failed edits.**
   ``\ValueError``, ``\TypeError``, ``UnsupportedException``, ``MemoryException`` and
   ``BusyException`` come before PDFium changes anything, so the document stays as it was. When
   PDFium itself fails, the edit method throws ``PdfiumException``.

   The message tells you what happened to held pages:

   .. list-table::
      :header-rows: 1
      :widths: 55 45

      * - Message
        - Held pages
      * - Short: "Cannot delete the page at index 2", "Cannot add a page" or "Cannot import
          pages"
        - Keep working.
      * - Ends in "the document's page order is now undefined; load the document again", as every
          failed ``movePages()`` message does
        - May have moved. Every held page throws.

.. _damaged-page-trees:

**Damaged page trees.**
   When a file's list of pages is damaged, an edit can throw. The saved file can also differ from
   what the document showed (fewer or other pages, or pages that cannot be loaded), even when no
   edit threw.

   Check the saved file, load the document again after an error, or repair the file with another
   tool first. A held ``Page`` never ends up on another page: the edit throws instead.

   A file can say it has more pages than it has. ``pageCount()`` then reports the larger number,
   ``page()`` throws ``FormatException`` for the missing pages, and deleting, moving or copying a
   missing page throws.

   A file can list one page twice, so the page shows up at two indexes. ``setRotation()`` and the
   box setters then change it at both, but a ``Page`` held at the other index keeps its old size,
   as after a move.

   A file's page list can point back into itself, so PDFium shows the same pages again. Moving
   pages, ``addPage()`` and ``insertPages()`` can throw there.


.. _memory-limit:

Memory limit
------------

When PHP runs out of ``memory_limit``, it normally stops with a fatal error. This extension checks
first and throws :php:exc:`Pdfium\\MemoryException` instead, so you can catch it.

Rough memory needed:

.. list-table::
   :header-rows: 1
   :widths: 40 60

   * - Call
     - Memory
   * - ``fromFile()``
     - The size of the file; nothing with ``lazy: true``.
   * - ``render()``
     - About 7 bytes per pixel; 8 with ``transparent``, 5 with ``grayscale`` and 6 with both.
   * - ``text()``
     - About 8 bytes per character.
   * - ``textIn()``, ``textInEach()``
     - About 30 bytes per character on the page, whatever the rectangles, and about 600 bytes per
       rectangle.
   * - ``chars()``
     - About 600 bytes per character returned.
   * - ``search()``
     - About 8 bytes per character on the page, 10 bytes per byte of the needle, and about 500
       bytes per match.
   * - ``webLinks()``
     - About 500 bytes per result.
   * - ``permanentId()``, ``changingId()``
     - The length of the identifier.
   * - ``bookmarks()``
     - About 2 KB per entry, 5 bytes per character of its title, and the length of its URI or file
       path.
   * - ``openAction()``, ``closeAction()``
     - About 1 KB, and the length of the URI or file path.
   * - ``javaScripts()``
     - About 100 bytes per script and 5 bytes per character of each name and script.
   * - ``links()``, ``linkAt()``, ``Annotation::link()``
     - About 2 KB per link, 256 bytes per quad, and the length of its URI or file path.
   * - ``attachments()``
     - About 130 bytes per attachment.
   * - ``Attachment::name()``, ``metadata()``
     - About 5 bytes per character.
   * - ``Attachment::contents()``
     - The size of the file. See :ref:`attachments` for PDFium's own copy.
   * - ``addAttachment()``, ``Annotation::setAttachment()``
     - 2 bytes per character of the name and description.
   * - ``extractPages()``, ``insertPages()``
     - 4 bytes per index given.
   * - ``movePages()``
     - 12 bytes per index moved.
   * - ``annotations()``
     - About 120 bytes per annotation.
   * - ``Annotation::quadPoints()``
     - About 256 bytes per quad.
   * - ``Annotation::vertices()``, ``inkStrokes()``
     - About 140 bytes per point, and for ``inkStrokes()`` 128 bytes per stroke.
   * - ``Annotation::stringValue()``, ``appearance()``
     - About 5 bytes per character.
   * - ``Annotation::setStringValue()``, ``setAppearance()``
     - 2 bytes per character.
   * - ``Annotation::addInkStroke()``
     - 8 bytes per point.
   * - ``signatures()``
     - About 250 bytes per signature field and per byte-range pair, plus the size of each
       contents, time and sub-filter, and about 5 bytes per character of each reason.
   * - ``language()``
     - About 5 bytes per character.
   * - ``setLanguage()``
     - 2 bytes per character.
   * - ``formFields()``
     - About 100 bytes per annotation on the page.
   * - ``FormField::name()``, ``alternateName()``, ``value()``, ``exportValue()``
     - About 5 bytes per character.
   * - ``FormField::options()``
     - About 150 bytes per option and 5 bytes per character of the labels.
   * - ``FormField::setValue()``
     - 2 bytes per character of the text.
   * - ``toPng()``, ``toJpeg()``, ``toString()``, ``save*()``
     - The size of the output.

PDFium builds a field's value, and its copy of the text you pass to ``setValue()``, in its own
memory, which ``memory_limit`` does not count.

A value of hundreds of megabytes can exhaust the process's memory before ``MemoryException`` is
thrown.

For the length of the call, ``setValue()``, ``setChecked()`` and ``select()`` also load every page
that holds a widget of the field: a field repeated on thousands of pages loads them all.

With ``memory_limit = -1`` the extension checks nothing.

A ``TextPage`` keeps the page's text inside PDFium, which ``memory_limit`` does not count, for as
long as it lives. ``unset()`` it when you are done with the page.

.. note::

   A :php:class:`Bitmap <Pdfium\\Bitmap>` holds its pixels in PHP memory for as long as it lives:
   4 bytes each, 1 in grayscale, 2 in transparent grayscale.

   They count toward ``memory_limit`` and show in ``memory_get_usage()``. Keeping many large
   bitmaps can make the next ``render()`` throw ``MemoryException``.

   Save or encode a bitmap and ``unset()`` it when you are done.


.. _threads:

Threads
-------

The extension supports NTS and ZTS PHP. PDFium is not thread-safe, so on ZTS only one thread per
process can use it at a time.

A call made while another thread is using PDFium throws :php:exc:`Pdfium\\BusyException` and does
nothing, so you can safely retry. To render in parallel, use several processes, for example
PHP-FPM workers.

- ``Bitmap`` methods never throw it.
- Freeing a document, page, text page, page iterator, attachment, annotation or form field, and
  ``PageIterator::next()`` and ``rewind()``, never throw it, but may wait for the other thread to
  finish.
- ``fromFile()`` reads the file before it checks. For a stream you cannot read twice, such as
  ``php://stdin``, read it into a string yourself and retry with ``fromString()``.
- Do not ``pcntl_fork()`` while another thread may be using PDFium. The child would find PDFium
  busy forever and hang when it frees a document or page.
