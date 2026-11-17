Changelog
=========

Unreleased
----------

Features:

- ``Page::render()`` option ``region``: only a rectangle of the page, given as a ``Rect`` in points,
  as for ``TextPage::textIn()``.
- ``Page::render()`` option ``annotations: false``: the page without annotations or form fields.
- ``Page::render()`` option ``transparent: true``: a transparent background, with alpha in PNG
  output; JPEG is drawn on white.
- ``Page::render()`` option ``grayscale: true``: a gray image, with one-channel PNG and JPEG output
  (two channels, gray and alpha, in a transparent PNG).
- ``Document::permanentId()`` and ``Document::changingId()``: the two entries of the file's ``/ID``.
- ``Document::bookmarks()``: the outline, as a tree of ``Bookmark`` values with their titles,
  colours, styles and where they lead.
- ``Page::openAction()`` and ``Page::closeAction()``: the actions a viewer runs when it opens or
  closes the page.
- ``Page::links()`` and ``Page::linkAt()``: the page's link annotations, with where they lead.
- Value classes ``Bookmark``, ``Link``, ``Quad``, ``Destination`` (a page in this document and how
  to show it) and ``Action`` (``GoTo``, ``GoToR``, ``URI``, ``Launch`` and ``GoToE`` actions).

v0.2.0 (2026-11-15)
-------------------

Features:

- ``Page::textPage()``: a ``TextPage`` that reads the page's text once and keeps it.
- ``TextPage::chars()``: every character, or a range of them, with its boxes, origin, font name,
  size, weight and flags, fill and stroke colours, angle and text matrix.
- ``TextPage::charCount()``: the number of characters.
- ``TextPage::charIndexAt()``: the character at a point.
- ``TextPage::textIn()``: the text inside a rectangle, given as a new ``Rect``.
- ``TextPage::textInEach()``: the text inside each of many rectangles, reading the page once.
- ``TextPage::search()``: matches with their character range, text and rectangles.
- ``TextPage::webLinks()``: URLs and email addresses written in the text.
- ``TextPage::text()``: the same as ``Page::text()``.
- Value classes for the results: ``Rect``, ``Char``, ``Color``, ``Matrix``, ``TextMatch`` and
  ``WebLink``.

Changes:

- ``Page::text()`` gives a hyphen that PDFium joins across a line break as ``-``, not U+FFFE.
- ``Page::text()`` leaves out a character PDFium cannot map to Unicode, not U+FFFE.

v0.1.0 (2026-11-07)
-------------------

First release, built against PDFium |pdfium_version|.

Features:

- Open PDFs from a file or a string, with an optional password.
- Read the page count, metadata, permissions, page sizes, rotation and text.
- Iterate over pages.
- Render pages to PNG or JPEG.
- Split, merge and save documents.

Supported:

- PHP 8.2 to 8.5, NTS and ZTS.
- Linux x86_64 and arm64 (glibc and musl), macOS arm64.
- Installation with ``pie install chaikivskyi/php-pdfium``.
