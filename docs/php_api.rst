PHP API
=======

.. php:namespace:: Pdfium

All classes are in the ``Pdfium`` namespace. The exact signatures are in ``pdfium.stub.php``.

Except for :php:class:`Rect`, you cannot create these classes with ``new``. Get a
:php:class:`Document` from :php:meth:`Document::fromFile` or :php:meth:`Document::fromString`, and
everything else from the document. You create a :php:class:`Rect` to pass to
:php:meth:`TextPage::textIn`, :php:meth:`TextPage::textInEach` or :php:meth:`Page::render`.

:php:class:`Document`, :php:class:`Page`, :php:class:`TextPage`, :php:class:`PageIterator` and
:php:class:`Bitmap` cannot be cloned or serialized. The value classes (:php:class:`Metadata`,
:php:class:`Permissions`, :php:class:`Rect`, :php:class:`Char`, :php:class:`Color`,
:php:class:`Matrix`, :php:class:`TextMatch`, :php:class:`WebLink`, :php:class:`Destination` and
:php:class:`Action`) are read-only and can be cloned and serialized.


Document
--------

.. php:class:: Document

   A PDF document. Implements ``\Countable`` and ``\IteratorAggregate``, so ``count($doc)`` and
   ``foreach ($doc as $index => $page)`` both work.

   .. rubric:: Opening

   .. php:staticmethod:: fromFile(string $path, ?string $password = null) -> Document

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
      :throws BusyException: ZTS only: another thread is using PDFium.

   .. php:staticmethod:: fromString(string $bytes, ?string $password = null) -> Document

      Opens a PDF from a string. The string is not copied; the document keeps it in memory.

      :param string $bytes: The contents of a PDF file.
      :param ?string $password: Password for an encrypted document.
      :rtype: Document
      :throws \\ValueError: The string is empty, or the password contains a NUL byte.
      :throws FormatException: The data is not a PDF or is corrupted.
      :throws PasswordException: A password is needed, or the password is wrong.
      :throws UnsupportedException: The document uses an encryption PDFium does not support.
      :throws BusyException: ZTS only: another thread is using PDFium.

   .. rubric:: Information

   .. php:method:: pageCount() -> int

      The number of pages. Fast: no page is loaded.

      :rtype: int
      :throws BusyException: ZTS only: another thread is using PDFium.

   .. php:method:: count() -> int

      Same as :php:meth:`pageCount`, so ``count($doc)`` works.

      :rtype: int
      :throws BusyException: ZTS only: another thread is using PDFium.

   .. php:method:: metadata() -> Metadata

      The document's title, author and so on. See :php:class:`Metadata`.

      :rtype: Metadata
      :throws BusyException: ZTS only: another thread is using PDFium.

   .. php:method:: permissions() -> Permissions

      The document's permission flags. They are only reported, not enforced. See
      :php:class:`Permissions`.

      :rtype: Permissions
      :throws BusyException: ZTS only: another thread is using PDFium.

   .. rubric:: Pages

   .. php:method:: page(int $index) -> Page

      Loads one page.

      :param int $index: Page number, starting at 0.
      :rtype: Page
      :throws \\ValueError: The index is out of range.
      :throws FormatException: PDFium cannot load the page.
      :throws BusyException: ZTS only: another thread is using PDFium.

   .. php:method:: pages() -> PageIterator

      An iterator over all pages. It loads one page at a time.

      :rtype: PageIterator

   .. php:method:: getIterator() -> PageIterator

      Same as :php:meth:`pages`, so ``foreach ($doc as $index => $page)`` works.

      :rtype: PageIterator

   .. rubric:: Splitting, merging and saving

   .. php:method:: extractPages(array $indexes, bool $removeSecurity = false) -> Document

      A new document with the given pages, in the given order. A page can appear more than once.
      It keeps this document's viewer preferences.

      The new document is unencrypted and has no bookmarks or metadata beyond what PDFium writes.
      Form fields still draw but can no longer be filled in. See :ref:`pdfium-defaults`.

      :param array $indexes: A non-empty list of page numbers (``int[]``).
      :param bool $removeSecurity: Must be ``true`` if this document is encrypted, because PDFium
         cannot encrypt the result.
      :rtype: Document
      :throws \\ValueError: The list is empty, or an index is out of range.
      :throws \\TypeError: An element is not an ``int``.
      :throws UnsupportedException: This document is encrypted and ``$removeSecurity`` is ``false``.
      :throws PdfiumException: PDFium cannot copy the pages.
      :throws BusyException: ZTS only: another thread is using PDFium.

   .. php:staticmethod:: merge(array $documents, bool $removeSecurity = false) -> Document

      A new document with all pages of each document, one after another. It uses the first
      document's viewer preferences.

      Like :php:meth:`extractPages`, the result is unencrypted and has no bookmarks or metadata beyond
      what PDFium writes, and its form fields can no longer be filled in.

      :param array $documents: A non-empty list of ``Document`` objects.
      :param bool $removeSecurity: Must be ``true`` if any document is encrypted, because PDFium
         cannot encrypt the result. Without it, the exception names the index of the first encrypted
         document.
      :rtype: Document
      :throws \\ValueError: The list is empty.
      :throws \\TypeError: An element is not a ``Document``.
      :throws UnsupportedException: A document is encrypted and ``$removeSecurity`` is ``false``.
      :throws PdfiumException: PDFium cannot copy the pages.
      :throws BusyException: ZTS only: another thread is using PDFium.

   .. php:method:: toString(bool $removeSecurity = false) -> string

      The whole document as a PDF file in a string.

      :param bool $removeSecurity: ``true`` writes the document without encryption. By default an
         encrypted document stays encrypted.
      :rtype: string
      :throws MemoryException: The result would not fit under ``memory_limit``.
      :throws PdfiumException: PDFium cannot write the document.
      :throws BusyException: ZTS only: another thread is using PDFium.

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
      :throws PdfiumException: PDFium cannot write the document.
      :throws BusyException: ZTS only: another thread is using PDFium.


Metadata
--------

.. php:class:: Metadata

   The document information returned by :php:meth:`Document::metadata`: a final, read-only value.
   Each property is a UTF-8 string, or ``null`` when the entry is empty or missing. Dates are
   returned as they are stored in the PDF, for example ``D:20261031120000Z``.

   .. php:attr:: title

      ``?string``

   .. php:attr:: author

      ``?string``

   .. php:attr:: subject

      ``?string``

   .. php:attr:: keywords

      ``?string``

   .. php:attr:: creator

      ``?string``

   .. php:attr:: producer

      ``?string``

   .. php:attr:: creationDate

      ``?string``

   .. php:attr:: modDate

      ``?string``


Permissions
-----------

.. php:class:: Permissions

   The permission flags returned by :php:meth:`Document::permissions`: a final, read-only value.
   They are only reported, never enforced. For an unencrypted document, all of them are ``true``.

   .. php:attr:: print

      ``bool``

   .. php:attr:: modify

      ``bool``

   .. php:attr:: copy

      ``bool``

   .. php:attr:: annotate

      ``bool``

   .. php:attr:: fillForms

      ``bool``

   .. php:attr:: extractForAccessibility

      ``bool``

   .. php:attr:: assemble

      ``bool``

   .. php:attr:: printHighQuality

      ``bool``


Page
----

.. php:class:: Page

   One page of a :php:class:`Document`.

   .. php:method:: index() -> int

      The page's position in its document, starting at 0.

      :rtype: int

   .. php:method:: width() -> float

      Width of the crop box, clipped to the media box, in points (1/72 inch), after rotation. For example, ``595.28`` for A4.

      :rtype: float
      :throws BusyException: ZTS only: another thread is using PDFium.

   .. php:method:: height() -> float

      Height of the crop box, clipped to the media box, in points, after rotation. For example, ``841.89`` for A4.

      :rtype: float
      :throws BusyException: ZTS only: another thread is using PDFium.

   .. php:method:: rotation() -> int

      Rotation in degrees: ``0``, ``90``, ``180`` or ``270``.

      :rtype: int
      :throws BusyException: ZTS only: another thread is using PDFium.

   .. php:method:: text() -> string

      All text on the page, as UTF-8, with ``\r\n`` between lines. Returns ``""`` if the page has
      no text. See :ref:`text-positions` for hyphenated words and characters without Unicode. To
      also search the text or read positions, use :php:meth:`textPage`, which reads the text only
      once.

      :rtype: string
      :throws FormatException: PDFium cannot read the page's text.
      :throws MemoryException: The text would not fit under ``memory_limit``.
      :throws BusyException: ZTS only: another thread is using PDFium.

   .. php:method:: render(int $dpi = 150, ?Rect $region = null, bool $transparent = false, bool $annotations = true, bool $grayscale = false) -> Bitmap

      Renders the page to a bitmap. See :ref:`rendering`.

      :param int $dpi: Resolution, from 1 to 2400.
      :param ?Rect $region: Render only this area, in points, as for
         :ref:`text positions <text-positions>`.
      :param bool $transparent: Render on a transparent background instead of white.
      :param bool $annotations: Draw annotations and form fields. The first such render can add
         appearance streams that a later save writes; ``false`` leaves the document unchanged.
      :param bool $grayscale: Render in gray: PNG and JPEG output have one gray channel, two in a
         transparent PNG.
      :rtype: Bitmap
      :throws \\ValueError: The DPI is out of range, a limit in :ref:`rendering` is exceeded, or the
         region has no width or height, does not overlap the page, or has a side unset or one its
         constructor refuses, as in one made without its constructor.
      :throws MemoryException: The bitmap would not fit under ``memory_limit``.
      :throws BusyException: ZTS only: another thread is using PDFium.

   .. php:method:: textPage() -> TextPage

      Reads the page's text once and keeps it, for :php:meth:`TextPage::chars`,
      :php:meth:`TextPage::search` and the other text methods.

      .. code-block:: php

         $text = $page->textPage();
         foreach (['total', 'due', 'tax'] as $word) {
             $matches[$word] = $text->search($word);
         }

      :rtype: TextPage
      :throws FormatException: PDFium cannot read the page's text.
      :throws BusyException: ZTS only: another thread is using PDFium.

   .. rubric:: Navigation

   .. php:method:: openAction() -> ?Action

      The action a viewer runs when it opens the page, or ``null``. See :ref:`navigation`.

      :rtype: ?Action
      :throws MemoryException: The action would not fit under ``memory_limit``.
      :throws BusyException: ZTS only: another thread is using PDFium.

   .. php:method:: closeAction() -> ?Action

      The action a viewer runs when it closes the page, or ``null``.

      :rtype: ?Action
      :throws MemoryException: The action would not fit under ``memory_limit``.
      :throws BusyException: ZTS only: another thread is using PDFium.


TextPage
--------

.. php:class:: TextPage

   A page's text as PDFium read it once, returned by :php:meth:`Page::textPage`. It keeps its page
   and document alive. Its methods never throw ``FormatException``, because the text is already
   read. See :ref:`text-positions`.

   .. php:method:: text() -> string

      The same as :php:meth:`Page::text`, without reading the text again.

      :rtype: string
      :throws MemoryException: The text would not fit under ``memory_limit``.
      :throws BusyException: ZTS only: another thread is using PDFium.

   .. php:method:: charCount() -> int

      The number of characters: the size of :php:meth:`chars`.

      :rtype: int
      :throws BusyException: ZTS only: another thread is using PDFium.

   .. php:method:: chars(int $start = 0, ?int $length = null) -> array

      The characters of :php:meth:`text`, in the same order, with their position and font. Without
      arguments it returns all of them, and the array index is the character index that
      :php:meth:`search`, :php:meth:`webLinks` and :php:meth:`charIndexAt` use. With arguments it
      builds only the characters asked for, which costs far less on a long page. See
      :ref:`text-positions`.

      .. code-block:: php

         $index = $textPage->charIndexAt(120, 700);
         $char = $index === null ? null : $textPage->chars($index, 1)[0];

         foreach ($textPage->search('total') as $match) {
             $chars = $textPage->chars($match->index, $match->length);
         }

      :param int $start: The character index of the first character to return.
      :param ?int $length: How many characters to return; ``null`` for all that follow. Fewer come
         back when the text ends first.
      :rtype: Char[]
      :returns: A list that starts at 0 for the character at ``$start``.
      :throws \\ValueError: ``$start`` is outside ``0..charCount()``, or ``$length`` is negative.
      :throws MemoryException: The characters would not fit under ``memory_limit``.
      :throws BusyException: ZTS only: another thread is using PDFium.

   .. php:method:: charIndexAt(float $x, float $y, float $xTolerance = 0.0, float $yTolerance = 0.0) -> ?int

      The index of the character at a point, or ``null`` if there is none. The tolerances are the
      width and height of an area centred on the point, so a character up to half a tolerance away
      also counts.

      :param float $x: Horizontal position in points.
      :param float $y: Vertical position in points, from the bottom of the page.
      :param float $xTolerance: Width of the area to look in, in points.
      :param float $yTolerance: Height of the area to look in, in points.
      :rtype: ?int
      :throws \\ValueError: A value is not finite, or a tolerance is negative.
      :throws BusyException: ZTS only: another thread is using PDFium.

   .. php:method:: textIn(Rect $rect) -> string

      The text of the characters inside a rectangle, as UTF-8, written as in :php:meth:`text`.
      A character is inside when the centre of its :php:attr:`Char::box` is inside the rectangle
      or on its edge, so a glyph the rectangle only clips stays out. Returns ``""`` if there are
      none.

      .. code-block:: php

         $total = $page->textPage()->textIn(new Rect(left: 400, bottom: 90, right: 560, top: 110));

      :param Rect $rect: The area, in points.
      :rtype: string
      :throws \\ValueError: A side of the rectangle is unset or one its constructor refuses, as in
         one made without its constructor.
      :throws MemoryException: The text would not fit under ``memory_limit``.
      :throws BusyException: ZTS only: another thread is using PDFium.

   .. php:method:: textInEach(array $rects) -> array

      :php:meth:`textIn` for every rectangle. It reads the page's characters once, so it is far
      faster than one :php:meth:`textIn` call per rectangle, as for the cells of a table.

      .. code-block:: php

         $cells = $page->textPage()->textInEach([
             'date' => new Rect(left: 40, bottom: 90, right: 200, top: 110),
             'total' => new Rect(left: 400, bottom: 90, right: 560, top: 110),
         ]);
         echo $cells['total'];

      :param Rect[] $rects: The areas, in points.
      :rtype: string[]
      :returns: The texts, under the keys of ``$rects``.
      :throws \\TypeError: ``$rects`` holds something that is not a :php:class:`Rect`.
      :throws \\ValueError: A side of a rectangle is unset or one its constructor refuses, as in one
         made without its constructor.
      :throws MemoryException: The texts would not fit under ``memory_limit``.
      :throws BusyException: ZTS only: another thread is using PDFium.

   .. php:method:: search(string $needle, bool $matchCase = false, bool $wholeWord = false, bool $overlapping = false) -> array

      Every match of ``$needle`` in :php:meth:`text`, in order, with the rectangles to highlight.
      A space, no-break space or line break in the needle matches any run of them, so
      ``total due`` is found across a line break. A tab does not. A word hyphenated across two
      lines is found with its hyphen: ``hyphen-ated``, not ``hyphenated``.

      .. code-block:: php

         foreach ($page->textPage()->search('invoice') as $match) {
             echo $match->text, ' at ', $match->rects[0]->left, ',', $match->rects[0]->top, "\n";
         }

      :param string $needle: The text to find, as UTF-8.
      :param bool $matchCase: Match upper and lower case exactly. Without it, case is ignored for
         every alphabet in the Basic Multilingual Plane, one character for one: ``Σ`` matches both
         ``σ`` and ``ς``, but ``ß`` does not match ``SS``.
      :param bool $wholeWord: Only match whole words: a match that starts or ends with a letter or
         digit of any alphabet must not have another one right before or after it. The characters
         of scripts written without spaces between words, such as Chinese, Japanese and Thai, do
         not count as letters, so there every match is a whole word.
      :param bool $overlapping: Look for the next match from the second character of the last one,
         so ``aa`` is found twice in ``aaa``.
      :rtype: TextMatch[]
      :throws \\ValueError: The needle is empty, is not valid UTF-8 or contains a NUL byte.
      :throws MemoryException: The matches would not fit under ``memory_limit``.
      :throws BusyException: ZTS only: another thread is using PDFium.

   .. php:method:: webLinks() -> array

      The URLs and email addresses written in the page's text. PDFium finds them by their shape:
      ``http://``, ``https://`` and ``www.`` addresses, and email addresses, which come back as
      ``mailto:`` URLs. Link annotations, the clickable areas a PDF can define, are not included.

      :rtype: WebLink[]
      :throws MemoryException: The links would not fit under ``memory_limit``.
      :throws BusyException: ZTS only: another thread is using PDFium.


PageIterator
------------

.. php:class:: PageIterator

   Returned by :php:meth:`Document::pages`. Implements ``\Iterator``: keys are page numbers, values
   are :php:class:`Page` objects. Only the current page is kept in memory.

   .. code-block:: php

      foreach ($doc->pages() as $index => $page) { /* ... */ }

   .. php:method:: current() -> Page

      :rtype: Page
      :throws PdfiumException: The iterator is past the last page.
      :throws FormatException: PDFium cannot load the page.
      :throws BusyException: ZTS only: another thread is using PDFium.

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
      :throws BusyException: ZTS only: another thread is using PDFium.


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

      The bitmap as PNG data: RGB, RGBA, gray, or gray and alpha, following the render's options
      (see :ref:`rendering`).

      :rtype: string
      :throws MemoryException: The PNG would not fit under ``memory_limit``.
      :throws PdfiumException: Encoding fails.

   .. php:method:: toJpeg(int $quality = 85) -> string

      The bitmap as JPEG data, RGB or gray. A transparent render is drawn on white.

      :param int $quality: Quality, from 1 to 100.
      :rtype: string
      :throws \\ValueError: The quality is out of range.
      :throws MemoryException: The JPEG would not fit under ``memory_limit``.
      :throws PdfiumException: Encoding fails.

   .. php:method:: savePng(string $path) -> void

      Writes :php:meth:`toPng` to a file. Works like :php:meth:`Document::save`.

      :param string $path: File to write.
      :throws \\ValueError: The path is empty.
      :throws FileException: The file cannot be written.
      :throws MemoryException: The PNG would not fit under ``memory_limit``.
      :throws PdfiumException: Encoding fails.

   .. php:method:: saveJpeg(string $path, int $quality = 85) -> void

      Writes :php:meth:`toJpeg` to a file. Works like :php:meth:`Document::save`.

      :param string $path: File to write.
      :param int $quality: Quality, from 1 to 100.
      :throws \\ValueError: The path is empty, or the quality is out of range.
      :throws FileException: The file cannot be written.
      :throws MemoryException: The JPEG would not fit under ``memory_limit``.
      :throws PdfiumException: Encoding fails.


Rect
----

.. php:class:: Rect

   A rectangle in points, with ``y`` growing upwards: a final, read-only value. See
   :ref:`text-positions`. :php:meth:`TextPage::chars`, :php:meth:`TextPage::search` and
   :php:meth:`TextPage::webLinks` return them, and you create one to pass to
   :php:meth:`TextPage::textIn`, :php:meth:`TextPage::textInEach` or :php:meth:`Page::render`.

   .. php:method:: __construct(float $left, float $bottom, float $right, float $top)

      :throws \\ValueError: A side is not finite, ``$left`` is greater than ``$right``, or
         ``$bottom`` is greater than ``$top``.

   .. php:attr:: left

      ``float``

   .. php:attr:: bottom

      ``float``

   .. php:attr:: right

      ``float``

   .. php:attr:: top

      ``float``


Char
----

.. php:class:: Char

   One character of a page's text, returned by :php:meth:`TextPage::chars`: a final, read-only value.

   A character PDFium added (``generated``) has no font or colour: ``fontName``, ``fontWeight``,
   ``fillColor`` and ``strokeColor`` are ``null``, ``fontSize`` is ``1.0`` and both boxes are
   zero-sized. Check for ``null`` before reading a colour.

   .. php:attr:: text

      ``string``: the character as UTF-8. Empty when PDFium cannot map it to Unicode, and for the
      second index of a character that takes two. See :ref:`text-positions`.

   .. php:attr:: box

      :php:class:`Rect`: the tight bounds of the glyph's ink. Zero-sized for a space.

   .. php:attr:: looseBox

      :php:class:`Rect`: the bounds of the glyph's whole cell, from the font's descent to its ascent.
      Use this one to highlight text.

   .. php:attr:: x

      ``float``: the x of the glyph's origin, in points.

   .. php:attr:: y

      ``float``: the y of the glyph's origin, which is on the baseline, in points.

   .. php:attr:: fontSize

      ``float``: in points.

   .. php:attr:: fontName

      ``?string``: for example ``Helvetica`` or ``ABCDEF+Arial-BoldMT``.

   .. php:attr:: fontWeight

      ``?int``: from 100 to 900, 400 being normal and 700 bold; ``null`` when the font does not say.

   .. php:attr:: fontFlags

      ``int``: the font descriptor flags from the PDF specification (ISO 32000-1, 9.8.2), for example
      ``1`` fixed-pitch, ``2`` serif, ``64`` italic, ``262144`` bold; ``0`` when unknown.

   .. php:attr:: angle

      ``float``: rotation in degrees, counter-clockwise, from 0 up to but not including 360,
      rounded to four decimal places.

   .. php:attr:: generated

      ``bool``: PDFium added the character, such as a space between words or a line break.

   .. php:attr:: hyphen

      ``bool``: a hyphen that splits a word across two lines. See :ref:`text-positions`.

   .. php:attr:: unicodeMapError

      ``bool``: the font maps this character to Unicode wrongly, so ``text`` may be wrong.

   .. php:attr:: fillColor

      ``?Color``: the colour the glyph is filled with, which is the colour of ordinary text;
      ``null`` when PDFium cannot tell.

   .. php:attr:: strokeColor

      ``?Color``: the colour the glyph is outlined with. It only shows for outlined text, but is
      reported either way; ``null`` when PDFium cannot tell.

   .. php:attr:: matrix

      ``?Matrix``: the matrix of the text object the character is in: its text matrix combined with
      the page's transformation. It does not include the font size or how far along the line the
      character is, so the characters of one text object share it; use ``x`` and ``y`` for the
      character's own position. ``null`` when PDFium cannot tell.

   Characters next to each other with the same colour or matrix share one :php:class:`Color` or
   :php:class:`Matrix` object, so ``===`` on two of them depends on where the characters are.
   Compare them with ``==``.


Color
-----

.. php:class:: Color

   A colour from :php:attr:`Char::fillColor` or :php:attr:`Char::strokeColor`, as RGB with values
   from 0 to 255: a final, read-only value. PDFium converts other colour spaces, such as CMYK, to
   RGB.

   .. php:attr:: red

      ``int``

   .. php:attr:: green

      ``int``

   .. php:attr:: blue

      ``int``

   .. php:attr:: alpha

      ``int``: 255 is opaque, 0 is invisible.


Matrix
------

.. php:class:: Matrix

   A PDF transformation matrix ``[a b c d e f]``, from :php:attr:`Char::matrix`: a final, read-only
   value. A point ``x, y`` maps to ``a·x + c·y + e, b·x + d·y + f``. ``e`` and ``f`` are the
   translation; ``a b c d`` hold the scale, rotation and skew.

   .. php:attr:: a

      ``float``

   .. php:attr:: b

      ``float``

   .. php:attr:: c

      ``float``

   .. php:attr:: d

      ``float``

   .. php:attr:: e

      ``float``

   .. php:attr:: f

      ``float``


TextMatch
---------

.. php:class:: TextMatch

   One match from :php:meth:`TextPage::search`: a final, read-only value.

   .. php:attr:: index

      ``int``: the index in :php:meth:`TextPage::chars` of the first character.

   .. php:attr:: length

      ``int``: how many character indexes the match covers. This can differ from the number of
      characters in ``text``: an emoji takes two indexes.

   .. php:attr:: text

      ``string``: the matched text, as it is on the page.

   .. php:attr:: rects

      :php:class:`Rect[] <Rect>`: one per line the match covers.


WebLink
-------

.. php:class:: WebLink

   One URL from :php:meth:`TextPage::webLinks`: a final, read-only value.

   .. php:attr:: url

      ``string``: the URL. An address written as ``www.example.org`` comes back as
      ``http://www.example.org``, and an email address as ``mailto:me@example.net``.

   .. php:attr:: index

      ``int``: the index in :php:meth:`TextPage::chars` of the first character.

   .. php:attr:: length

      ``int``: how many character indexes the URL covers, as for :php:attr:`TextMatch::length`.

   .. php:attr:: rects

      :php:class:`Rect[] <Rect>`: one per line the URL covers.


Destination
-----------

.. php:class:: Destination

   Where a bookmark, link or action leads in this document: a final, read-only value.

   .. php:attr:: pageIndex

      ``int``: the 0-based index of the target page.

   .. php:attr:: view

      ``?string``: how the viewer fits the page: ``"XYZ"``, ``"Fit"``, ``"FitH"``, ``"FitV"``,
      ``"FitR"``, ``"FitB"``, ``"FitBH"`` or ``"FitBV"``, as named in the PDF specification;
      ``null`` when PDFium cannot tell.

   .. php:attr:: params

      ``float[]``: the view's parameters as PDFium gives them, at most four. For ``"FitH"`` it is
      the top edge, for ``"FitR"`` the left, bottom, right and top.

   .. php:attr:: x

      ``?float``: for ``"XYZ"``, the left edge to show, in points; ``null`` when absent, which means
      keep the current one, and for other views.

   .. php:attr:: y

      ``?float``: for ``"XYZ"``, the top edge to show, in points; ``null`` as for :php:attr:`x`.

   .. php:attr:: zoom

      ``?float``: for ``"XYZ"``, the zoom factor; ``null`` as for :php:attr:`x`.


Action
------

.. php:class:: Action

   What a bookmark, link or page event does: a final, read-only value.

   .. php:attr:: type

      ``string``: ``"GoTo"`` (a place in this document), ``"GoToR"`` (a place in another PDF file),
      ``"URI"``, ``"Launch"`` (open a file or run an application), ``"GoToE"`` (a place in an
      embedded PDF), or ``"Unsupported"`` for every type PDFium does not read, such as JavaScript.

   .. php:attr:: uri

      ``?string``: for ``"URI"``, the URI as written in the PDF. It is not checked: it may not be
      UTF-8.

   .. php:attr:: filePath

      ``?string``: for ``"GoToR"`` and ``"Launch"``, the file, as UTF-8.

   .. php:attr:: destination

      ``?Destination``: for ``"GoTo"``, where it leads; ``null`` for every other type.


.. _exceptions:

Exceptions
----------

Every error throws an exception. No method returns ``false`` or raises a warning.

Bad arguments throw PHP's own ``\ValueError`` or ``\TypeError``. Everything else throws one of
these:

.. php:exception:: PdfiumException

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

   The document uses an encryption PDFium does not support, or :php:meth:`Document::extractPages` or
   :php:meth:`Document::merge` was given an encrypted document without ``removeSecurity: true``.

.. php:exception:: MemoryException

   The operation would go over ``memory_limit``. Thrown instead of PHP's fatal out-of-memory error.
   See :ref:`memory-limit`.

.. php:exception:: BusyException

   ZTS PHP only. Another thread is using PDFium, and nothing was done. It is safe to retry, except
   that :php:meth:`Document::fromFile` has already read the file. See :ref:`threads`.

   Every :php:class:`Document`, :php:class:`Page`, :php:class:`TextPage` and
   :php:class:`PageIterator` method can throw it, including ``current()`` and ``valid()`` in the
   middle of a ``foreach``, except :php:meth:`Document::pages`, :php:meth:`Document::getIterator`,
   :php:meth:`Page::index`, :php:meth:`PageIterator::key`, :php:meth:`PageIterator::next` and
   :php:meth:`PageIterator::rewind`. :php:class:`Bitmap` and the value classes never throw it.
   :php:class:`Bitmap` methods never throw it.

The exception class says what went wrong; ``$e->getCode()`` is always 0.
