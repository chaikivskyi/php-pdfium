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

A document opened with ``fromString()`` also keeps its string in memory.

These do **not** keep a document alive:

- a :php:class:`Bitmap <Pdfium\\Bitmap>` rendered from one of its pages;
- a document made from it with ``extractPages()`` or ``merge()``. The pages are copied, so you can
  free the source right away.

To free a document, ``unset()`` it and every page, text page, iterator and attachment taken from
it.


.. _pdfium-defaults:

PDFium defaults
---------------

The extension follows PDFium and adds no rules of its own, except that ``setLanguage()`` and
``addAttachment()`` refuse a few values PDFium would read back wrongly; see :ref:`accessibility`.

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
   so a right-to-left document still reads right to left. The result has no bookmarks, attachments
   or language, is not tagged, and has none of the sources' metadata: ``metadata()`` reports only
   ``creator`` and ``producer`` "PDFium" and the ``creationDate`` of the split or merge. Form fields
   still draw but can no longer be filled in. PDFium has no API to set metadata, add encryption or
   create bookmarks.

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
such a file, and ``max_execution_time`` does not stop PDFium; on ZTS PHP, other threads get
``BusyException`` meanwhile. With PDFs you do not trust, run this work where it can be killed from
outside, for example under PHP-FPM's ``request_terminate_timeout``.

Ordinary large lists can be slow too: ``attachments()`` takes about two seconds for 20,000 files
stored as a tree of nested parts of 50 entries each. A flat list stays fast.

``extractPages()`` and ``merge()`` copy pages only, so their results have no attachments.

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

**Types you can add.**
   PDFium adds Text, Link, FreeText, Square, Circle, Highlight, Underline, Squiggly, StrikeOut,
   Stamp, Ink, Popup and FileAttachment annotations. Other types, such as Line, Polygon or Widget,
   can be read and changed but not added. A new annotation has only its type and rectangle: give it
   what its type needs, such as quad points for a Highlight or strokes for an Ink annotation, and a
   colour.

**Appearance streams.**
   What a viewer draws for an annotation is its appearance stream, or appearance: a small piece of
   PDF content that ``appearance()`` reads. An annotation can have one for each mode: normal,
   rollover (under the pointer) and down (pressed). A new annotation has none.

   The next ``render()`` of a page with annotations, the default, generates a normal appearance
   for each annotation of these types that has none: Text, FreeText, Square, Circle, Highlight,
   Underline, Squiggly, StrikeOut and Ink. It skips one with ``FLAG_HIDDEN``. The appearance is
   stored in the document, so a later ``save()`` includes it. Without such a render the saved PDF
   has no appearance for them, and a viewer that relies on it shows nothing. Render the page once
   before saving when other viewers must show the annotation.

   Other types get none: Stamp, FileAttachment, Link and Popup annotations, and the types PDFium
   cannot add, such as Line or Polygon. A Stamp draws nothing until ``setAppearance()`` gives it
   one.

   Once an annotation has a normal appearance stream, whether generated, set with
   ``setAppearance()`` or written by another tool:

   - ``render()`` draws it as it is and does not generate a new one.
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
   rollover and down ones too. The next render generates a new one only for the types listed
   above. Any other type, such as a Stamp, or a Line or Polygon from another tool, is left without
   one and draws nothing.

   An appearance stream is read and written as a PDF text string. ``setAppearance()`` stores it one
   byte per character, in PDFDocEncoding, when every character has a byte there. Otherwise it
   stores all of it as UTF-16, which draws nothing. ``appearance()`` decodes a stream the same way.
   Keep streams to ASCII: bytes above 0x7F that another tool wrote, such as UTF-8 text in a string,
   do not read back as they are stored.

**Removing.**
   Removing an annotation moves the annotations after it down one index; ``index()`` always gives
   the current one. Every ``Annotation`` object for the removed annotation throws
   ``PdfiumException`` from then on, so none can change an annotation that is no longer on its
   page. Objects for the other annotations stay usable. To remove several by index, remove from the
   highest down.

**Not covered.**
   The extension does not reach the page objects inside an annotation, such as the text and images
   of a Stamp, or the form-field calls for Widget annotations; a Widget is listed with its common
   properties. PDFium has no calls to remove a key, to write a value other than a string, or to
   remove one area of the quad points.


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
- ``permanentId()`` and ``changingId()``: the length of the identifier.
- ``bookmarks()``: about 2 KB per entry, 5 bytes per character of its title, and the length of
  its URI or file path.
- ``openAction()`` and ``closeAction()``: about 1 KB, and the length of the URI or file path.
- ``links()`` and ``linkAt()``: about 2 KB per link, 256 bytes per quad, and the length of its
  URI or file path.
- ``attachments()``: about 100 bytes per attachment.
- ``Attachment::name()`` and ``metadata()``: about 5 bytes per character.
- ``Attachment::contents()``: the size of the file. See :ref:`attachments` for PDFium's own copy.
- ``addAttachment()``: 2 bytes per character of the name and description.
- ``signatures()``: about 250 bytes per signature field and per byte-range pair, plus the size of
  each contents, time and sub-filter, and about 5 bytes per character of each reason.
- ``language()``: about 5 bytes per character.
- ``setLanguage()``: 2 bytes per character.
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
