Changelog
=========

Unreleased
----------

Features:

- ``Document::formType()``: whether the document has an AcroForm, an XFA form or none.
- ``Page::formFields()`` and ``Page::formFieldAt()``: the page's form fields as ``FormField``
  objects, with their name, tooltip, type, value, checked state, rectangle, options and flags.
- ``FormField::setValue()``, ``setChecked()`` and ``select()``: fill in text fields, checkboxes,
  radio buttons, combo boxes and list boxes, in place, also in encrypted documents.
- ``Page::flatten()``: turn a page's annotations and form fields into page content, for the screen
  or for printing, in place.
- Value classes ``FormFieldOption`` and ``FormFieldFlags``, and the enums ``FormType`` and
  ``FormFieldType``.
- ``Page::annotationCount()``, ``annotations()`` and ``annotation()``: a page's annotations, as
  ``Annotation`` objects with ``type()``, ``index()``, ``rect()`` and ``flags()``, and the
  ``Annotation::FLAG_*`` constants for the flags.
- ``Annotation::color()``, ``interiorColor()``, ``border()``, ``quadPoints()``, ``vertices()``,
  ``line()`` and ``inkStrokes()``: an annotation's colours, border and geometry. ``hasKey()``,
  ``valueType()``, ``stringValue()`` and ``numberValue()``: its dictionary, by key.
  ``appearance()``: its appearance stream for one of the ``Annotation::APPEARANCE_*`` modes.
- Value classes ``Point`` and ``Border``. ``Point`` and ``Quad`` can be created with ``new``.
- ``Page::addAnnotation()``: add an annotation of one of the 13 types PDFium creates.
  ``Page::removeAnnotation()`` and ``Annotation::remove()``: take one off its page, after which
  every ``Annotation`` for it throws.
- ``Annotation::setRect()``, ``setFlags()``, ``setColor()``, ``setInteriorColor()``,
  ``setBorder()``, ``addQuadPoints()``, ``setQuadPoints()``, ``addInkStroke()``,
  ``removeInkStrokes()`` and ``setStringValue()``: change an annotation in place.
  ``setAppearance()`` and ``clearAppearance()``: write and remove its appearance stream.
  ``setColor()`` and ``setInteriorColor()`` throw while a normal appearance stream exists instead
  of dropping it.
- ``Annotation::linkedAnnotation()``: the annotation of the same page that a key such as ``Popup``
  or ``IRT`` points to. ``Annotation::link()``: a link annotation's ``Link``. ``setUri()``: make it
  open a URI.
- ``Annotation::attachment()`` and ``setAttachment()``: read and replace the file of a
  FileAttachment annotation, as an ``Attachment`` that keeps the annotation alive. It is separate
  from the document's attachment list, so its ``index()`` throws.
- What you set on annotations of an encrypted document is encrypted on save. Form fields (Widget
  annotations) can be changed and removed, and the next render shows the change. Two ``Page``
  objects for one page share its annotations. See the behaviour notes for these and for damaged
  annotation lists.
- ``Document::deletePage()``: remove a page.
- ``Document::create()`` and ``Document::addPage()``: start a document with no pages and add blank
  pages of any size at any position.
- ``Document::insertPages()``: copy pages of another document, or of the same one, into a document
  at any position.
- ``Document::movePages()``: reorder pages.
- ``Document::nUp()``: a new document with the pages scaled onto sheets of a given size, several
  to a sheet.
- ``Page::setRotation()`` and the page boxes: ``mediaBox()``, ``cropBox()``, ``bleedBox()``,
  ``trimBox()`` and ``artBox()`` with their setters, as ``Rect`` values in points.
- A ``Page`` held from before an edit follows its page to its new index, and so do the
  ``Annotation`` and ``FormField`` objects of the page. Once its page is deleted, or a failed edit
  leaves its position unknown, the ``Page`` and every ``TextPage``, ``Annotation`` and
  ``FormField`` made from it throw ``PdfiumException`` from every method.
  ``PageIterator::current()`` gives the page at ``key()`` after the edit.
- ``Document::javaScripts()``: the scripts in the document's JavaScript name tree, as ``JavaScript``
  values with a name and the source. Nothing is run.
- ``Document::save()`` and ``toString()`` options ``incremental: true`` (keep the original file
  and append the changes) and ``version`` (the PDF version written in the header).
- Ini setting ``pdfium.font_paths``: the directories PDFium scans for fonts that a PDF does not
  embed.
- ``Document::fromFile()`` option ``lazy: true``: read a local file as PDFium needs it instead of
  loading it whole. While such a document reads a file, ``Document::save()``, ``Bitmap::savePng()``
  and ``saveJpeg()`` throw ``FileException`` instead of overwriting it in place; replacing it with a
  new copy still works.

Fixes:

- ``Document::permissions()`` reports every permission for an unencrypted document PDFium built in
  memory, such as the result of ``extractPages()`` or ``merge()``; it reported none.
- ``Document::extractPages()`` throws ``MemoryException``, not a fatal error, when its page indexes
  would not fit under ``memory_limit``.

v0.3.0 (2026-11-21)
-------------------

Features:

- ``Page::render()`` option ``region``: only a rectangle of the page, given as a ``Rect`` in points,
  as for ``TextPage::textIn()``.
- ``Page::render()`` option ``annotations: false``: the page without annotations or form fields.
- ``Page::render()`` option ``transparent: true``: a transparent background, with alpha in PNG
  output; JPEG is drawn on white.
- ``Page::render()`` option ``grayscale: true``: a gray image, with one-channel PNG and JPEG output
  (two channels, gray and alpha, in a transparent PNG).
- ``Document::permanentId()`` and ``Document::changingId()``: the file's identifiers, one set when it
  was created and one that changes with each save.
- ``Document::bookmarks()``: the outline, as a tree of ``Bookmark`` values with their titles,
  colours, styles and where they lead.
- ``Page::openAction()`` and ``Page::closeAction()``: the actions a viewer runs when it opens or
  closes the page.
- ``Page::links()`` and ``Page::linkAt()``: the page's link annotations, with where they lead.
- Value classes ``Bookmark``, ``Link``, ``Quad``, ``Destination`` (a page in this document and how
  to show it) and ``Action`` (what a link, bookmark or page event does: go to a page, open a URI or
  another file).
- ``Document::attachmentCount()``, ``attachments()`` and ``attachment()`` list embedded files as
  ``Attachment`` objects with a name, contents and ``metadata()``, which gives the description,
  MIME type, relationship, dates and checksum.
- ``Document::addAttachment()`` and ``deleteAttachment()``: embed a file in the document or remove
  one, written out by ``save()`` and ``toString()``.
- ``Document::signatureCount()`` and ``signatures()`` list the document's signature fields as
  ``Signature`` values with the contents, byte range, sub-filter, reason, time and certification
  level.
- ``Document::isTagged()``: whether the document says it is a tagged PDF.
- ``Document::language()`` and ``Document::setLanguage()``: read and set the document's language,
  written out by ``save()`` and ``toString()``.

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
