Behaviour
=========

Things worth knowing that are easy to miss.


Lifetimes
---------

There is no ``close()``. PHP frees a document when nothing refers to it any more. These keep a
document alive:

- the :php:class:`Document <Pdfium\\Document>` object itself;
- any :php:class:`Page <Pdfium\\Page>`, :php:class:`TextPage <Pdfium\\TextPage>`,
  :php:class:`PageIterator <Pdfium\\PageIterator>`, :php:class:`Attachment <Pdfium\\Attachment>`,
  :php:class:`Annotation <Pdfium\\Annotation>` or :php:class:`FormField <Pdfium\\FormField>` from it.

A :php:class:`TextPage <Pdfium\\TextPage>`, :php:class:`Annotation <Pdfium\\Annotation>` or
:php:class:`FormField <Pdfium\\FormField>` also keeps its page loaded. An ``Attachment`` from an
annotation keeps that annotation alive.

A document opened with ``fromString()`` also keeps its string in memory, and one opened with
``fromFile(lazy: true)`` keeps its file open.

These do **not** keep a document alive:

- a :php:class:`Bitmap <Pdfium\\Bitmap>` rendered from one of its pages;
- a document made from it with ``extractPages()``, ``merge()`` or ``nUp()``, or a document its
  pages were copied into with ``insertPages()``. The pages are copied, so you can free the source
  right away.

To free a document, ``unset()`` it and every page, text page, iterator, attachment, annotation and
form field taken from it.


.. _pdfium-defaults:

PDFium defaults
---------------

The extension follows PDFium and adds few rules of its own. ``extractPages()``, ``insertPages()``,
``merge()`` and ``nUp()`` refuse an encrypted source unless you pass ``removeSecurity: true``
(below), and ``setLanguage()`` and ``addAttachment()`` refuse a few values PDFium would read back
wrongly; see :ref:`accessibility`. For a document opened with ``fromFile(lazy: true)``, a save will
not overwrite its file in place, and once its file could not be read in full it will not be saved,
split, merged, copied from with ``insertPages()`` or laid out with ``nUp()``; see
:ref:`large-files`.

**Permissions are not enforced.**
   ``permissions()`` tells you what the document allows, but nothing stops you. For example, you can
   read the text of a document that forbids copying. Page edits work whatever ``permissions()`` says
   about modifying or assembling the document, and do not change what it reports.

**Removing encryption is always explicit.**
   ``save()`` and ``toString()`` keep the encryption. Pass ``removeSecurity: true`` to save without
   it. PDFium cannot encrypt a new document, so ``extractPages()``, ``merge()`` and ``nUp()`` throw
   :php:exc:`Pdfium\\UnsupportedException` for an encrypted source, whether it was opened with a user
   password or only has an owner password, unless you pass ``removeSecurity: true``. Then the result
   is an ordinary unencrypted document. ``insertPages()`` does the same when the source is another,
   encrypted document, because the copied pages are no longer protected by the source's password.
   They take the encryption of the document they are copied into, if it has any.

**Split, merge and nUp() output keeps little but the pages.**
   ``extractPages()`` keeps its document's viewer preferences and ``merge()`` the first document's,
   so a right-to-left document still reads right to left; ``nUp()`` keeps none of them. The result
   has no bookmarks, attachments or language, is not tagged, and has none of the sources' metadata:
   ``metadata()`` reports only ``creator`` and ``producer`` "PDFium" and ``creationDate``, the
   creation date of the new document. ``extractPages()``, ``merge()`` and ``nUp()`` with one page
   per sheet copy form fields: they still draw and can be filled in, but the result has no
   ``/AcroForm``, so ``formType()`` is ``None``. ``nUp()`` with several pages per sheet leaves out
   links, form fields and other annotations. PDFium has no API to set metadata, add encryption or
   create bookmarks.

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

**Incremental saves.**
   ``incremental: true`` writes the original file as it was, from its ``%PDF`` header on, and
   appends a section after it; bytes before the header, which some files have, are dropped. The
   appended section holds the changes and also unchanged copies of the objects PDFium has read, such
   as the document catalog, the metadata once you call ``metadata()``, and a page's objects once you
   open it. An encrypted document stays encrypted.

   PDFium writes a full copy instead, and reports success, in two cases: with
   ``removeSecurity: true``, and for a document from ``create()``, ``extractPages()``, ``merge()`` or
   ``nUp()``, which has no original bytes. To check that a save was incremental, test that it starts
   with the original from its ``%PDF`` on:

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

**The version option.**
   ``version`` sets the PDF version in the file header, from ``10`` for 1.0 to ``17`` for 1.7.
   Nothing in the document is converted to suit an older version. PDFium ignores any other value
   without an error, as if ``version`` were not given, and writes the source's 1.x version, or 1.7
   for a document from ``create()``, ``extractPages()``, ``merge()`` or ``nUp()``. An incremental
   save keeps the original header, so ``version`` only applies when the save falls back to a full
   copy.

   A full save of a PDF 2.0 file writes a ``%PDF-1.0`` header unless you pass a ``version`` from 10
   to 17; ``version: 20`` is outside the range and ignored like any other value.

**File identifiers.**
   Every save, full or incremental, writes a new changing identifier (see ``changingId()``), so two
   saves of the same document are normally not byte-identical. An incremental save keeps the
   permanent identifier. Compare documents by their content, not their bytes.

**Rendering or reading a form can change what saving writes.**
   The first time any page of a document is rendered with annotations (the default), or any form
   method other than ``Document::formType()`` is called, PDFium sets up form support for the
   document. For form fields without an appearance stream, PDFium then generates one, and a later
   ``save()`` or ``toString()`` includes it. A render with annotations does the same for annotations
   of most types. On a form that asks for new appearances (``/NeedAppearances true``), it can
   replace the streams the fields have. See :ref:`annotations`. ``annotations: false`` leaves the
   document unchanged.


.. _rendering:

Rendering
---------

``render()`` draws the page the way a PDF viewer does:

- on a white background, with no transparency;
- with annotations and form fields;
- the area where the media box and crop box overlap, at its size after rotation.

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
  ``save()``, ``toString()``, ``extractPages()``, ``merge()`` and ``nUp()`` throw
  :php:exc:`Pdfium\\FormatException` rather than produce an incomplete copy, and so does
  ``insertPages()`` when it reads such a document, as source or as target. It first removes the
  pages it copied into a target that is not such a document, so that target is left as it was; a
  target that is such a document can be left with an undefined page order, as after a
  :ref:`failed edit <failed-edits>`.

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

This holds for a page with no rotation whose visible area, where the media box and crop box
overlap, starts at 0, 0. ``mediaBox()`` and ``cropBox()`` show where it starts, unless the page
takes its boxes from its page tree. For a ``region`` image, measure from the region's top left
corner instead, which works on any page with no rotation:

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
string it came from; ``save()`` or ``toString()`` writes the change out. A saved PDF leaves out a
deleted file that came with the loaded document, unless something else in the document still
points to it, such as a FileAttachment annotation. A file added since it was loaded and then
deleted stays in the saved PDF, unused.

The list is kept sorted by name, so adding or deleting a file can shift the indexes of the others.
After either call, every :php:class:`Attachment <Pdfium\\Attachment>` of this list fetched before
it is stale: its methods throw ``PdfiumException`` instead of reading a different file. To read a
file again, call ``attachments()`` or ``attachment()`` for a new ``Attachment``, or use the one
``addAttachment()`` returned: it is not stale. To delete several files, see
``deleteAttachment()`` in :doc:`php_api`.

Strictly, the list is sorted and checked by a key PDFium does not show. PDFium sets it to the file
name, but other tools may not, so two entries can have the same ``name()``, and ``addAttachment()``
can refuse a name that no ``name()`` shows.

On macOS, ``name()`` shows each ``/`` in a file name as ``:``, and drops the first ``/`` of a name
that starts with ``/Mac``, because PDFium converts file names to the platform's form.

When PDFium cannot decode a file, ``contents()`` returns its bytes as stored, still compressed or
encoded. This happens when the stream is damaged, or when it is ``/RunLengthDecode`` and would
decode to 20 MB or more.

PDFium decodes a file in its own memory, which ``memory_limit`` does not count, before
``contents()`` can check the size. A small compressed file that expands to gigabytes can exhaust
the process's memory before ``MemoryException`` is thrown. ``addAttachment()`` also gives PDFium a
copy of ``$contents``, and copies of the name and description several times their size, outside
``memory_limit``; if PDFium cannot allocate them, it ends the process.

A file added to an encrypted document is encrypted with it on save, unless you pass
``removeSecurity: true``.

``addAttachment()`` refuses some names and descriptions with ``ValueError``, by the same rules as
``setLanguage()``; see :ref:`accessibility`. It also throws ``ValueError`` for ``$contents`` longer
than 2147483647 bytes. The limit is PDFium's: it stores a file's size as a 32-bit number and refuses
anything longer, so raising ``memory_limit`` does not help.

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

An ``Attachment`` from a FileAttachment annotation is separate from this list: ``attachments()``
lists only the files the document's list names. When the list names the same file, it shows up in
both, and only the ``Attachment`` from ``attachments()`` has an index. See :ref:`annotations`.

``extractPages()``, ``merge()``, ``nUp()`` and ``insertPages()`` copy pages only, not the attachment
list. A copied page keeps its annotations, and a FileAttachment annotation keeps its file, except
in ``nUp()`` with several pages per sheet, which leaves annotations out.

.. _accessibility:

Accessibility
-------------

``isTagged()`` reports what the document says about itself (``/Marked`` in its ``/MarkInfo``). It
does not check that the document has a usable structure tree.

``language()`` returns the language as PDFium reads it. It is not checked to be a language tag, and
it is ``null`` when the document has none, when it is empty, and when it is not text (a number, for
example). What PDFium reads can differ from what the file stores:

- It ends at the first NUL character. In a language stored as single bytes, bytes 0x7F, 0x9F and
  0xAD also count as NUL.
- In a language stored as UTF-16 or UTF-8, PDFium drops the escape character (U+001B) and anything
  between a pair of them.
- A language stored as a name is returned as text, and one stored as a stream as the stream's
  decoded contents.

``setLanguage()`` changes the loaded document, never the file or string it came from; ``save()`` or
``toString()`` writes the change out. It works even when :php:class:`Permissions
<Pdfium\\Permissions>` says ``modify`` is ``false``. In an encrypted document the language is saved
encrypted, unless you pass ``removeSecurity: true``. An empty string marks the language as unknown,
and ``language()`` then returns ``null``; the entry cannot be removed.

``setLanguage()`` throws ``ValueError`` for a value PDFium would read back wrongly, and
``addAttachment()`` does the same for names and descriptions. Such a value:

- contains the escape character (U+001B), or
- starts with ``þÿ``, ``ÿþ`` or ``ï»¿`` (U+00FE U+00FF, U+00FF U+00FE, U+00EF U+00BB U+00BF) and
  has only characters PDF can store as single bytes: most of ASCII and Latin-1, and a few symbols
  such as ``€`` and ``•``. A reader takes that start as a byte order mark. One other character, such
  as a Cyrillic letter, makes the value acceptable.

``setLanguage()`` gives PDFium copies of the language, several times its size, that
``memory_limit`` does not count; if PDFium cannot allocate them, it ends the process. PDFium
decodes a language stored as a stream before ``language()`` can check its size, so a crafted PDF
under a megabyte can exhaust the process's memory before ``MemoryException`` is thrown;
``Document::metadata()`` has the same weakness. With PDFs you do not trust, limit the process's
memory from outside.

``extractPages()`` and ``merge()`` copy pages only, so their results have no language and are not
tagged.

The structure tree (headings, paragraphs, alternative text) is not offered: to read it, PDFium (as
of ``chromium/8125``) follows the document's ``/ParentTree`` with no depth limit or loop check, so a
crafted file can crash PHP. It will be offered once PDFium bounds that walk.

.. _signatures:

Signatures
----------

The list holds every signature field at the top of the form's field list, in order:

- Unsigned fields are listed too, with ``null`` properties and an empty ``byteRange``. So
  ``signatureCount() > 0`` does not mean the document is signed; look for a ``contents`` that is
  not ``null``.
- Fields nested under another field are not listed, so a signature stored on one is missed and the
  document can look unsigned. If the parent is a signature field itself, it is listed as unsigned.
- A field the form lists twice is returned twice.

``byteRange`` offsets count bytes in the file or string the document was opened from.
``save()`` and ``toString()`` write a different file, which the offsets do not fit.

Treat every value as untrusted input:

- ``byteRange`` numbers are not checked against the file: they can be negative, point past its
  end or come in any number of pairs. A number that does not fit in 32 bits comes back wrong, a
  fraction is cut, an entry that is not a number comes back as 0, and an odd last number is
  dropped.
- An empty value, or one of the wrong type, reads as missing, including a ``subFilter`` stored as
  a string instead of a name. The exception is ``contents``: a number or name there comes back as
  its text, so ``/Contents 5`` gives ``"5"``.
- ``contents`` keeps the zero bytes signers pad it with.
- ``subFilter`` and ``time`` are the file's bytes and can hold null bytes or invalid UTF-8, which
  ``json_encode()`` and databases refuse.
- ``time`` is the file's claim and is not parsed; a signed time, if any, is inside ``contents``.
- ``reason`` is converted to UTF-8 and ends at its first null character.
- ``docMdpPermission`` is 2 when the file names a certification without a level. A fraction is
  cut to a whole number; a level outside 1 to 3, or one that is not a number, gives ``null``.

In an encrypted document the PDF standard leaves ``/Contents`` unencrypted. PDFium reads it as
stored only when the signature dictionary has ``/Type /Sig``, or no ``/Type`` and ``/FT /Sig``. In
any other dictionary, document timestamps (``/Type /DocTimeStamp``) included, it decrypts
``contents`` like every other string, which garbles a correctly written one.

``signatures()`` gets slower with the square of the number of fields: about 3 seconds for 5,000
and a minute for 20,000, which a small crafted file can hold by listing one field many times.
``signatureCount()`` stays fast, so check it first on PDFs you do not trust.
``max_execution_time`` stops ``signatures()`` between two fields; until then, on ZTS PHP, other
threads get ``BusyException``.

``extractPages()`` and ``merge()`` copy pages only, so their results have no signatures.


.. _annotations:

Annotations
-----------

**Changes.**
   ``addAnnotation()``, ``removeAnnotation()`` and the setters of
   :php:class:`Annotation <Pdfium\\Annotation>` change the loaded document, never the file or string
   it came from; ``save()`` or ``toString()`` writes the change out. Two ``Annotation`` objects for
   the same annotation see each other's changes.

   So do two :php:class:`Page <Pdfium\\Page>` objects for the same page, from two ``page()`` calls.
   An annotation added, changed or removed through one is counted, read and drawn by the other. An
   ``Annotation`` from one throws once its annotation is removed through the other.

**Adding.**
   ``addAnnotation()`` adds ``"Text"``, ``"Link"``, ``"FreeText"``, ``"Square"``, ``"Circle"``,
   ``"Highlight"``, ``"Underline"``, ``"Squiggly"``, ``"StrikeOut"``, ``"Stamp"``, ``"Ink"``,
   ``"Popup"`` and ``"FileAttachment"`` annotations. Other types, such as ``"Line"``, ``"Polygon"``
   or ``"Widget"``, can be read and changed but not added. A new annotation has only its type and
   rectangle: give it what its type needs, such as quad points for a Highlight with
   ``addQuadPoints()``, strokes for an Ink annotation with ``addInkStroke()``, a URI for a Link with
   ``setUri()`` or a file for a FileAttachment with ``setAttachment()``, and a colour with
   ``setColor()``.

**Appearance streams.**
   What a viewer draws for an annotation is its appearance stream, or appearance: a small piece of
   PDF content that ``appearance()`` reads. An annotation can have one for each mode: normal,
   rollover (under the pointer) and down (pressed). A new annotation has none.

   The next ``render()`` of a page with annotations, the default, generates a normal appearance for
   each annotation of these types that has none: ``"Text"``, ``"FreeText"``, ``"Square"``,
   ``"Circle"``, ``"Highlight"``, ``"Underline"``, ``"Squiggly"``, ``"StrikeOut"`` and ``"Ink"``. It
   skips one with ``FLAG_HIDDEN``. The appearance is stored in the document, so a later ``save()``
   includes it. Without such a render the saved PDF has no appearance for them, and a viewer that
   relies on it shows nothing. Render the page once before saving when other viewers must show the
   annotation.

   Other types get none: ``"Stamp"``, ``"FileAttachment"``, ``"Link"`` and ``"Popup"`` (even a Popup
   that belongs to another annotation), and the types PDFium cannot add, such as ``"Line"`` or
   ``"Polygon"``. Form fields are the exception; see Form fields below. A Stamp draws nothing until
   ``setAppearance()`` gives it one. PDFium gives that stream the annotation's rectangle as its
   bounding box, so the stream draws in page coordinates.

   Once an annotation has a normal appearance stream, whether generated, set with
   ``setAppearance()`` or written by another tool:

   - ``render()`` draws it as it is and does not generate a new one, except for a Widget on a form
     that asks for new appearances (see below).
   - ``color()`` and ``interiorColor()`` return ``null``.
   - ``setColor()`` and ``setInteriorColor()`` throw ``PdfiumException``, saying to call
     ``clearAppearance()`` first. The extension never drops an appearance by itself.
   - ``addInkStroke()`` and ``removeInkStrokes()`` change the strokes, but ``render()`` keeps
     drawing the old ones.
   - ``setRect()`` is accepted. ``render()`` draws the appearance in the new rectangle, except a
     Highlight, Underline, Squiggly or StrikeOut whose appearance PDFium generated, which stays at
     its quad points.
   - ``addQuadPoints()`` and ``setQuadPoints()`` change the areas, but ``render()`` keeps drawing
     the old appearance, also in a saved PDF, until ``clearAppearance()``. It stays in place,
     except one PDFium generated for a Highlight, Underline, Squiggly or StrikeOut: when the box
     around the new areas no longer holds it, it is moved and stretched to that box.
   - ``setBorder()`` removes the appearance streams of every mode, including one another tool
     wrote, with the same result as ``clearAppearance()`` below.

   An empty normal stream counts too: ``appearance()`` then returns ``null``, but ``hasKey('AP')``
   is ``true``. Rollover and down streams alone do not count.

   ``clearAppearance()`` removes the appearance stream of one mode; for the normal mode, the
   rollover and down ones too. The next render generates a new one only for the types listed above
   and for form fields. Any other type, such as ``"Stamp"``, or ``"Line"`` or ``"Polygon"`` from
   another tool, is left without one and draws nothing.

   An appearance stream is read and written as a PDF text string. ``setAppearance()`` stores it one
   byte per character, in PDFDocEncoding, when every character has a byte there. Otherwise it
   stores all of it as UTF-16, which draws nothing. ``appearance()`` decodes a stream the same way.
   Keep streams to ASCII: bytes above 0x7F that another tool wrote, such as UTF-8 text in a string,
   do not read back as they are stored.

**Removing.**
   Removing an annotation moves the annotations after it down one index; ``index()`` always gives
   the current one. Every ``Annotation`` object for the removed annotation throws
   ``PdfiumException`` from then on, so none can change an annotation that is no longer on its
   page. Objects for the other annotations stay usable, and so are ``FormField`` objects for the
   other widgets; a ``FormField`` for a removed Widget throws. To remove several by index, remove
   from the highest down.

   A saved PDF leaves out a removed annotation, and anything from the loaded document that only it
   used, such as its file. Whatever something else still points to stays, such as an annotation
   that a Popup names as its ``/Parent``, or the form field of a removed Widget.

**Links.**
   ``linkAt()`` remembers a page's links. The first time it is called for a page, through any
   ``Page`` object, it reads the page's link annotations and keeps that list while the document is
   loaded. After ``addAnnotation()``, ``removeAnnotation()`` or ``remove()`` on that page, it still
   finds a removed link and does not find an added one. It does see a new URI or rectangle of a
   link it already listed. A document loaded from the saved PDF starts with the current links.

   ``links()`` and ``Annotation::link()`` always read the page as it is, so use them after adding
   or removing annotations.

**Linked annotations.**
   ``linkedAnnotation()`` returns only annotations in this page's annotation list, so ``index()``
   and ``remove()`` always work on what it returns. It returns ``null`` when the key points to an
   annotation of another page, to a dictionary in no page's list, or to an annotation removed from
   this page. Take an annotation of another page from that page's ``annotations()``.

   It also returns ``null`` when the dictionary the key points to has no ``/Type /Annot``. PDF
   makes that entry optional, so some files leave it out. A key that points back to the annotation
   itself gives a second object for it.

**File attachments.**
   A FileAttachment annotation holds one file, read with ``attachment()`` and replaced with
   ``setAttachment()``. The file need not be in the document's attachment list, and the
   ``Attachment`` from the annotation throws from ``index()`` even when it is. That ``Attachment``
   keeps the annotation alive and asks the annotation for its file on each call, so it always reads
   the annotation's current file: after ``setAttachment()`` or ``setStringValue()`` with the key
   ``FS``, an ``Attachment`` fetched before reads the new file. Adding or deleting a document file
   does not affect it. Once its annotation is removed from the page, or has no file, the
   ``Attachment`` throws ``PdfiumException``.

   ``setAttachment()`` replaces the file in steps. When PDFium cannot store the contents or
   description, it has already dropped the previous file and cannot undo it: the
   ``PdfiumException`` says so, and the annotation is left with a file entry without that content.

   A saved PDF leaves out a replaced file that came with the loaded document. A file an earlier
   ``setAttachment()`` set stays in it, unused.

**Encrypted documents.**
   In an encrypted document, the string values, appearance streams and files you set on
   annotations are encrypted on save, unless you pass ``removeSecurity: true``.

**Form fields.**
   Widget annotations, the form fields, are listed, read, changed and removed like the others.
   Removing a Widget only takes it off its page: its field stays in the document's form, and in a
   saved PDF.

   After a Widget of the document is changed or removed, the next ``render()`` with annotations of
   each :php:class:`Page <Pdfium\\Page>` object reloads the page's form fields, as for a newly
   loaded ``Page``; a change to any other annotation does not.

   That render, and the first render with annotations of each ``Page`` object, generates an
   appearance stream for every field on the page that has none. On a form that asks for new
   appearances (``/NeedAppearances true`` in the document's ``/AcroForm``), it generates one for
   every field on the page, replacing the one the field has. There, a stream that
   ``setAppearance()`` gives a Widget lasts until the next such render of its page: a ``save()`` or
   ``toString()`` before that render writes your stream, one after it writes PDFium's. On other
   forms the stream from ``setAppearance()`` stays. Fields on pages that are not rendered keep the
   appearance they have.

**Damaged lists.**
   A damaged annotation list is read as far as PDFium can:

   - An entry that is not a dictionary, such as ``null`` or a reference to a missing object,
     counts in ``annotationCount()`` but is left out of ``annotations()``. ``annotation()`` throws
     ``FormatException`` for it.
   - An ``/Annots`` that is not an array counts as no annotations.
   - An annotation listed twice gives two objects whose ``index()`` is its first position.
     ``remove()`` takes out that entry. The object it was called on throws from then on, and the
     others find the entry that is left. ``removeAnnotation()`` takes out the entry at its index,
     and every object finds the entry that is left.
   - Values PDFium cannot use read as missing: quad points with fewer than 8 numbers give none, an
     ink stroke that is not a list of coordinate pairs is empty, and a border that is not an array
     is ``null``.
   - A rectangle that does not have exactly 4 numbers reads as 0, 0, 0, 0, and a colour with 5
     components as black.
   - A ``/Popup`` or ``/IRT`` that loops back, to the annotation itself or between two, does not
     hang ``linkedAnnotation()``: each call follows one step.

**Not covered.**
   The extension does not reach the page objects inside an annotation, such as the text and images
   of a Stamp. A Widget is listed with its common properties; its field's value, options and flags
   come from ``formFields()``, see :ref:`forms`. PDFium has no calls to remove a key, to write a
   value other than a string, or to remove one area of the quad points.


.. _forms:

Forms
-----

**A FormField is one widget.**
   A field is drawn by one or more widgets: a radio group has one per button, and a field can be
   repeated on one page or on several. ``Page::formFields()`` gives a
   :php:class:`FormField <Pdfium\\FormField>` for each widget on that page, back to front, and
   ``controlIndex()`` and ``controlCount()`` place it among its field's widgets. List the other
   pages to reach the widgets there. Widgets are listed even when the document has no ``/AcroForm``
   or the field is missing from its ``/Fields``.

**Changes stay in the loaded document.**
   ``setValue()``, ``setChecked()``, ``select()`` and ``flatten()`` never touch the file or string
   the document came from; ``save()`` or ``toString()`` writes them out. An encrypted document is
   saved encrypted with the same password, unless you pass ``removeSecurity: true``. Reading a form
   can also change the saved file, as rendering does; see :ref:`pdfium-defaults`.

**What** ``value()`` **returns.**
   The value PDFium stores, as UTF-8, or ``""`` when there is none:

   - a text field gives its text: a password field's in clear, and a rich-text field's plain
     ``/V`` rather than ``/RV``;
   - a combo box gives the selected option's export value, not its label, while ``options()``
     gives labels only. A list box with several options selected gives only the first;
   - a checkbox or radio group gives the checked button's export value, or ``Off``.
     ``isChecked()`` tells one button's state.

   Field values are not page text: ``text()`` and ``search()`` find them only after ``flatten()``.

**What** ``setValue()`` **stores.**
   PDFium takes the text as if it were typed:

   - a ``/MaxLen`` above 0 silently cuts longer text; compare ``value()`` when it matters;
   - a multi-line field stores ``\n``, ``\r\n`` and ``\r`` as ``\r\n``, a single-line field drops
     line breaks, and a tab becomes a space;
   - a character above U+FFFF, such as most emoji, is stored wrongly: U+1F600 reads back as U+F600.
     Such characters already in the document read correctly.

**Read-only is not uniformly enforced.**
   Forms fill even when ``permissions()->fillForms`` is ``false``. PDFium still changes a
   read-only text field, combo box or list box; check ``flags()->readOnly`` to refuse. It ignores
   a read-only checkbox or radio button, so ``setChecked()`` throws ``PdfiumException`` ending in
   "; it is read-only".

**Scripts do not run.**
   Calculated, formatted and validated fields are not updated when another field changes, and a
   field's own format script does not reshape what ``setValue()`` stores.

**XFA is not supported.**
   The AcroForm fields of an XFA document are read and filled like any others; the XFA data is
   not. ``Document::formType()`` tells such documents apart.

**Combo boxes and list boxes.**
   ``select()`` replaces the selection, except in a list box with ``multiSelect``, where the other
   options keep their state. Selecting a selected option changes nothing. PDFium does not deselect
   a combo box's option, so ``select($i, false)`` on one throws "PDFium did not change the
   selection". ``setValue()`` works on a combo box with ``editable`` only, and selects the option
   whose export value equals the text, or none.

**Checkboxes and radio buttons.**
   Checking a radio button unchecks the others of its group. PDFium does not uncheck a radio
   button, so ``setChecked(false)`` on a checked one throws; check another button instead.
   Checkboxes of one field that share an export value change together, and so do radio buttons
   with the same export value in a group with ``RadiosInUnison``.

**Filling shows on every page.**
   Filling rebuilds the appearance of every widget of the field, on every page, so the saved file
   shows the new value with or without ``/NeedAppearances``. One exception while the document is
   loaded: once a second ``Page`` object for a page has drawn or listed its fields, the first one
   can stop showing fills made through other ``Page`` objects. Fetch that page again, or fill
   through a ``FormField`` from it.

**Flattening.**
   ``flatten()`` turns all of the page's annotations into page content, links and form fields
   alike, so ``formFields()`` and ``links()`` are empty afterwards and the saved page has no form
   fields. It cannot be undone. Without ``forPrint`` it drops annotations flagged hidden or
   invisible; with ``forPrint`` it keeps only those flagged for printing and not hidden.

   Only annotations with an appearance stream are drawn into the page. In a document with an
   ``/AcroForm``, ``flatten()`` first builds the missing ones, so a field's value or check mark is
   kept even when nothing read the form before.

   The ``Page`` you flatten through shows the new content. Objects made before do not:

   - a ``FormField`` throws "The form field is gone: its widget was removed or its page flattened";
   - an :php:class:`Annotation <Pdfium\\Annotation>` throws "The annotation was removed from its
     page";
   - a :php:class:`TextPage <Pdfium\\TextPage>` keeps the old text;
   - another ``Page`` for the same page lacks the flattened content: it renders the old fields or
     none, its ``text()`` lacks the flattened text, its ``formFields()`` and ``links()`` are empty,
     and its ``flatten()`` returns ``false``. Fetch the page again with ``Document::page()``.

   A field repeated on other pages keeps its widgets there: they still read, fill and save. Until
   the saved document is opened again, their ``controlCount()`` and ``controlIndex()`` still count
   the flattened widgets. Flattening can remove the document's ``/AcroForm`` even while other pages
   still have fields, and ``formType()`` then gives ``None``.

**Damaged forms.**
   The form methods read what PDFium can follow, and return a value or throw ``PdfiumException``:

   - a ``/Parent`` chain that loops or is very deep gives the name and type PDFium resolves, which
     can be ``""`` and ``Unknown``;
   - a widget PDFium cannot tie to a field, such as one with no ``/FT`` anywhere, is ``Unknown``:
     ``formFieldAt()`` does not find it, and its setters, ``controlIndex()`` and ``controlCount()``
     throw;
   - ``/Annots`` entries that are not annotations are skipped;
   - ``/Opt`` entries that are neither strings nor pairs give options with empty labels;
   - a ``/Rect`` with fewer than four numbers gives a rectangle of ``0, 0, 0, 0``.


.. _page-editing:

Page editing
------------

``deletePage()``, ``addPage()``, ``insertPages()``, ``movePages()``, ``setRotation()`` and the box
setters change the loaded document, never the file or string it came from. ``save()`` or
``toString()`` writes the changes out. The edits leave ``metadata()`` as it was, except that
``insertPages()`` sets the producer; see ``insertPages()`` in :doc:`php_api`.

**Held pages follow their page.**
   A :php:class:`Page <Pdfium\\Page>` taken before an edit keeps working on the same page, and
   ``index()`` reports where that page is now. So do the
   :php:class:`Annotation <Pdfium\\Annotation>` and :php:class:`FormField <Pdfium\\FormField>`
   objects of the page: a fill, for one, reaches the page where it is now. Once the page is deleted,
   the ``Page`` and every :php:class:`TextPage <Pdfium\\TextPage>`, ``Annotation`` and
   ``FormField`` made from it throw ``PdfiumException`` ("The page was deleted from its document")
   from every method, ``index()`` included, and so does an ``Attachment`` from one of its
   annotations. After a failed edit that leaves the page order undefined (see below), they throw
   ``PdfiumException`` saying their position is unknown instead. A page deleted before that still
   says it was deleted.

   Bookmarks, links and destinations are read from the document on each call. Most destinations
   follow their page, and become ``null`` once it is deleted. In some files a destination names a
   page number instead. That number stays the same after an edit, so it can then lead to another
   page, or be ``null``.

**Iterating while editing.**
   A ``PageIterator``, and so ``foreach ($doc as $page)``, continues by index, like a loop over an
   array that changes under it. Deleting the current page or one before it makes the loop skip the
   next page; adding or inserting pages before the current one makes it see a page again. Moving a
   page from one side of the current position to the other can do both. To delete several pages, go
   from the highest index down:

   .. code-block:: php

      for ($i = $doc->pageCount() - 1; $i >= 0; $i--) {
          if ($i % 2) { $doc->deletePage($i); }
      }

**Inherited page attributes.**
   A PDF keeps its pages in a page tree, which can group pages and give a group a rotation, a media
   box, a crop box, or resources such as fonts and images. The pages in a group take these on
   unless they have their own. ``rotation()``, ``width()`` and ``height()`` include what a page
   takes on; a box getter returns ``null`` for a box the page takes on.

   ``addPage()`` gives the new page its own rotation of 0 and, where the group's crop box would
   clip it, its own crop box the size of the page.

   ``insertPages()`` copies what the source page has or takes from its own document's page tree. A
   copy without a rotation or crop box then takes the ones of the group it lands in. A copy that
   still has no media box gets its crop box as its media box, or US Letter without one.

   ``movePages()`` can put a page in another group. A page that took its rotation, boxes or
   resources from its old group then takes the new group's instead. A page moved to the end of the
   document takes what is set for the whole tree.

   The size and rotation can then differ from before. To keep them, read them before the move and
   set them afterwards, with the media box and crop box. ``width()`` and ``height()`` are measured
   after rotation, hence the swap:

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

   ``$shown`` is the visible area. The recipe puts it at the corner of the page's own crop box, else
   of its own media box, else at the point 0, 0, which is right in most files. Where a box the page
   takes from its group starts cannot be read, so if the visible area started elsewhere, the content
   shifts.

   Fonts and images cannot be set at all, so a moved page that took them from its old group can draw
   and extract its text differently.

   A ``Page`` held from before a move reports the new ``rotation()`` but keeps its old ``width()``
   and ``height()``, and ``render()`` draws it at the old size, until ``setRotation()`` or a box
   setter is called on a ``Page`` at its index. ``Document::page()`` gives a ``Page`` with the
   current size.

**Sizes and sides are floats.**
   PDFium stores page sizes and box sides as 32-bit floats, so ``addPage()``, ``nUp()`` and the box
   setters round what you pass to the nearest float, and a value beyond the float range becomes the
   largest float of the same sign, about ``±3.4e38``. They check the rounded values: a size that
   rounds to 0 is refused, and so is a box whose left and right, or bottom and top, become equal.

.. _failed-edits:

**Failed edits.**
   ``\ValueError``, ``\TypeError``, ``UnsupportedException``, ``MemoryException`` and
   ``BusyException`` are thrown before PDFium changes anything, so the document stays as it was.
   When PDFium itself fails, the edit method throws ``PdfiumException``.

   The message tells what happened to held pages. A short one, "Cannot delete the page at index 2",
   "Cannot add a page" or "Cannot import pages", means they keep working. A message that ends in
   "the document's page order is now undefined; load the document again", as every failed
   ``movePages()`` message does, means pages may have moved. Every held page then throws.

.. _damaged-page-trees:

**Damaged page trees.**
   When a file's list of pages is damaged, an edit can throw, and the saved file can differ from
   what the document showed (fewer or other pages, or pages that cannot be loaded), even when no
   edit threw. Check the saved file, load the document again after an error, or repair the file
   with another tool first. A held ``Page`` is never left on another page: the edit throws instead.

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
- ``links()``, ``linkAt()`` and ``Annotation::link()``: about 2 KB per link, 256 bytes per quad,
  and the length of its URI or file path.
- ``attachments()``: about 130 bytes per attachment.
- ``Attachment::name()`` and ``metadata()``: about 5 bytes per character.
- ``Attachment::contents()``: the size of the file. See :ref:`attachments` for PDFium's own copy.
- ``addAttachment()`` and ``Annotation::setAttachment()``: 2 bytes per character of the name and
  description.
- ``extractPages()`` and ``insertPages()``: 4 bytes per index given.
- ``movePages()``: 12 bytes per index moved.
- ``annotations()``: about 120 bytes per annotation.
- ``Annotation::quadPoints()``: about 256 bytes per quad.
- ``Annotation::vertices()`` and ``inkStrokes()``: about 140 bytes per point, and for
  ``inkStrokes()`` 128 bytes per stroke.
- ``Annotation::stringValue()`` and ``appearance()``: about 5 bytes per character.
- ``Annotation::setStringValue()`` and ``setAppearance()``: 2 bytes per character;
  ``addInkStroke()``: 8 bytes per point.
- ``signatures()``: about 250 bytes per signature field and per byte-range pair, plus the size of
  each contents, time and sub-filter, and about 5 bytes per character of each reason.
- ``language()``: about 5 bytes per character.
- ``setLanguage()``: 2 bytes per character.
- ``formFields()``: about 100 bytes per annotation on the page.
- ``FormField::name()``, ``alternateName()``, ``value()`` and ``exportValue()``: about 5 bytes per
  character.
- ``FormField::options()``: about 150 bytes per option and 5 bytes per character of the labels.
- ``FormField::setValue()``: 2 bytes per character of the text.
- ``toPng()``, ``toJpeg()``, ``toString()`` and the ``save*()`` methods: the size of the output.

PDFium builds a field's value, and its copy of the text you pass to ``setValue()``, in its own
memory, which ``memory_limit`` does not count. A value of hundreds of megabytes can exhaust the
process's memory before ``MemoryException`` is thrown. For the length of the call, ``setValue()``,
``setChecked()`` and ``select()`` also load every page that holds a widget of the field: a field
repeated on thousands of pages loads them all.

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
- Freeing a document, page, text page, page iterator, attachment, annotation or form field, and
  ``PageIterator::next()`` and ``rewind()``, never throw it, but may wait for the other thread to
  finish.
- ``fromFile()`` reads the file before it checks. For a stream you cannot read twice, such as
  ``php://stdin``, read it into a string yourself and retry with ``fromString()``.
- Do not ``pcntl_fork()`` while another thread may be using PDFium. The child would find PDFium
  busy forever and hang when it frees a document or page.
