PHP API
=======

.. php:namespace:: Pdfium

All classes are in the ``Pdfium`` namespace. The exact signatures are in ``pdfium.stub.php``.

Except for :php:class:`Rect`, :php:class:`Quad` and :php:class:`Point`, you cannot create these
classes with ``new``. Get a :php:class:`Document` from :php:meth:`Document::fromFile`,
:php:meth:`Document::fromString` or :php:meth:`Document::create`, or from
:php:meth:`Document::extractPages`, :php:meth:`Document::merge` or :php:meth:`Document::nUp`, which
return a new one; get everything else from the document. You create a :php:class:`Rect` to pass to
:php:meth:`TextPage::textIn`, :php:meth:`TextPage::textInEach`, :php:meth:`Page::render`,
:php:meth:`Page::addAnnotation`, :php:meth:`Annotation::setRect` or a box setter such as
:php:meth:`Page::setCropBox`. A :php:class:`Quad` goes to :php:meth:`Annotation::addQuadPoints` and
:php:meth:`Annotation::setQuadPoints`, and a :php:class:`Point` to
:php:meth:`Annotation::addInkStroke`.

:php:class:`Document`, :php:class:`Page`, :php:class:`TextPage`, :php:class:`PageIterator`,
:php:class:`Bitmap`, :php:class:`Attachment`, :php:class:`Annotation` and :php:class:`FormField`
cannot be cloned or serialized. The value classes (:php:class:`Metadata`, :php:class:`Permissions`,
:php:class:`Rect`, :php:class:`Char`, :php:class:`Color`, :php:class:`Matrix`,
:php:class:`TextMatch`, :php:class:`WebLink`, :php:class:`Bookmark`, :php:class:`Link`,
:php:class:`Quad`, :php:class:`Destination`, :php:class:`Action`, :php:class:`AttachmentMetadata`,
:php:class:`Border`, :php:class:`Signature`, :php:class:`Point`, :php:class:`FormFieldOption` and
:php:class:`FormFieldFlags`) are read-only and can be cloned and serialized.


Document
--------

.. php:class:: Document

   A PDF document. Implements ``\Countable`` and ``\IteratorAggregate``, so ``count($doc)`` and
   ``foreach ($doc as $index => $page)`` both work. It can be edited: pages deleted, added, copied
   in, moved, rotated and given new boxes. The edits are written out only by :php:meth:`save` or
   :php:meth:`toString`; see :ref:`page-editing`.

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

   .. php:staticmethod:: create() -> Document

      A new document with no pages, and no metadata beyond what PDFium writes. Add pages with
      :php:meth:`addPage`; :php:meth:`save` and :php:meth:`toString` write it out, and a document
      with no pages opens again with ``pageCount()`` of ``0``.

      :rtype: Document
      :throws MemoryException: PDFium cannot create a document.
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
      :throws MemoryException: An entry would not fit under ``memory_limit``.
      :throws BusyException: ZTS only: another thread is using PDFium.

   .. php:method:: permissions() -> Permissions

      The document's permission flags. They are only reported, not enforced. See
      :php:class:`Permissions`.

      :rtype: Permissions
      :throws BusyException: ZTS only: another thread is using PDFium.

   .. php:method:: isTagged() -> bool

      Whether the document says it is a tagged PDF (its ``/MarkInfo`` has ``/Marked`` set). It does
      not check that the document has a usable structure tree. See :ref:`accessibility`.

      :rtype: bool
      :throws BusyException: ZTS only: another thread is using PDFium.

   .. php:method:: language() -> ?string

      The document's language (its ``/Lang``) as PDFium reads it, for example ``"en-US"``, as UTF-8;
      ``null`` when there is none, it is empty, or PDFium cannot read it as text. See
      :ref:`accessibility`.

      :rtype: ?string
      :throws MemoryException: It would not fit under ``memory_limit``.
      :throws BusyException: ZTS only: another thread is using PDFium.

   .. php:method:: setLanguage(string $language) -> void

      Sets the document's language, for example ``"en-US"``. It is not checked to be a language
      tag. An empty string means the language is unknown, and ``language()`` then returns ``null``.
      Changes the loaded document; ``save()`` or ``toString()`` writes it out.

      :param string $language: The language, as UTF-8.
      :throws \\ValueError: ``$language`` is not valid UTF-8, contains a null byte or the escape
         character, or starts with what a PDF reader would take for a byte order mark (see
         :ref:`accessibility`).
      :throws MemoryException: ``$language`` would not fit under ``memory_limit``.
      :throws PdfiumException: PDFium cannot set it.
      :throws BusyException: ZTS only: another thread is using PDFium.

   .. php:method:: permanentId() -> ?string

      The first of the file's two identifiers (its ``/ID``): set when the file was created and
      meant to stay the same in later versions. Raw bytes: use ``bin2hex()`` to show them.
      ``null`` when the file has none or it is empty.

      :rtype: ?string
      :throws MemoryException: The identifier would not fit under ``memory_limit``.
      :throws BusyException: ZTS only: another thread is using PDFium.

   .. php:method:: changingId() -> ?string

      The second of the file's two identifiers (its ``/ID``), which changes each time the file is
      saved. Raw bytes: use ``bin2hex()`` to show them. ``null`` when the file has none or it is
      empty.

      :rtype: ?string
      :throws MemoryException: The identifier would not fit under ``memory_limit``.
      :throws BusyException: ZTS only: another thread is using PDFium.

   .. rubric:: Navigation

   .. php:method:: bookmarks() -> array

      The document outline: the top-level :php:class:`Bookmark` entries, in order, each with its
      :php:attr:`Bookmark::children`. Empty when the document has no outline.

      .. code-block:: php

         function toc(array $bookmarks, int $level = 0): void
         {
             foreach ($bookmarks as $b) {
                 echo str_repeat('  ', $level), $b->title;
                 if ($b->destination) {
                     echo ' ... ', $b->destination->pageIndex + 1;
                 }
                 echo "\n";
                 toc($b->children, $level + 1);
             }
         }
         toc($doc->bookmarks());

      An outline that loops or nests deeper than 100 levels is cut short; see :ref:`navigation`.

      :rtype: Bookmark[]
      :throws MemoryException: The outline would not fit under ``memory_limit``.
      :throws BusyException: ZTS only: another thread is using PDFium.

   .. rubric:: Attachments

   .. php:method:: attachmentCount() -> int

      The number of files embedded in the document.

      :rtype: int
      :throws BusyException: ZTS only: another thread is using PDFium.

   .. php:method:: attachments() -> array

      Every embedded file as an :php:class:`Attachment`, keyed by the index that
      :php:meth:`attachment` and :php:meth:`deleteAttachment` take. An entry PDFium cannot read is
      left out, so keys can skip numbers.

      .. code-block:: php

         foreach ($doc->attachments() as $index => $a) {
             $bytes = $a->contents();
             if ($bytes !== null) {
                 file_put_contents("/tmp/out/$index.bin", $bytes);   // names can be empty, repeated or unsafe
             }
         }

      :rtype: Attachment[]
      :throws MemoryException: The list would not fit under ``memory_limit``.
      :throws BusyException: ZTS only: another thread is using PDFium.

   .. php:method:: attachment(int $index) -> Attachment

      One embedded file.

      :param int $index: Attachment number, starting at 0.
      :rtype: Attachment
      :throws \\ValueError: The index is out of range.
      :throws FormatException: PDFium cannot read the entry.
      :throws BusyException: ZTS only: another thread is using PDFium.

   .. php:method:: addAttachment(string $name, string $contents, ?string $description = null) -> Attachment

      Embeds a file in this document, in place: :php:meth:`save` or :php:meth:`toString` writes it
      out. PDFium sets the size, creation date and checksum; the MIME type, relationship and
      modification date stay empty. The new file can take any index, and every
      :php:class:`Attachment` fetched from the document's list before this call is now stale; an
      annotation's file is not. See :ref:`attachments`.

      :param string $name: The file name, as UTF-8.
      :param string $contents: The file's bytes.
      :param ?string $description: Shown by viewers next to the file, as UTF-8.
      :rtype: Attachment
      :throws \\ValueError: ``$name`` is empty; ``$name`` or ``$description`` is not valid UTF-8,
         contains a null byte or the escape character, or starts with what a PDF reader would take
         for a byte order mark (see :ref:`accessibility`); or ``$contents`` is longer than
         2147483647 bytes, the most PDFium can store (see :ref:`attachments`).
      :throws PdfiumException: PDFium refuses: the name may already be used, or the list of
         attachments is damaged. Also when PDFium cannot store the contents or description; the new
         entry is then removed if possible, and the message says when it stays.
      :throws MemoryException: The name or description would not fit under ``memory_limit``.
      :throws BusyException: ZTS only: another thread is using PDFium.

   .. php:method:: deleteAttachment(int $index) -> void

      Removes an embedded file from this document, in place. A saved PDF leaves out a deleted
      file that came with the loaded document, unless something else in the document still points
      to it; a file added since loading and then deleted stays in the saved PDF, unused. Every
      :php:class:`Attachment` fetched from the document's list before this call is now stale; an
      annotation's file is not. To delete several, collect their indexes first and delete from the
      highest down:

      .. code-block:: php

         $doomed = [];
         foreach ($doc->attachments() as $index => $a) {
             if (str_ends_with($a->name(), '.tmp')) {
                 $doomed[] = $index;
             }
         }
         foreach (array_reverse($doomed) as $index) {
             $doc->deleteAttachment($index);
         }

      :param int $index: Attachment number, starting at 0.
      :throws \\ValueError: The index is out of range.
      :throws PdfiumException: PDFium cannot delete the entry.
      :throws BusyException: ZTS only: another thread is using PDFium.

   .. rubric:: Signatures

   .. php:method:: signatureCount() -> int

      How many signature fields are at the top of the form's field list, signed or not.

      :rtype: int
      :throws BusyException: ZTS only: another thread is using PDFium.

   .. php:method:: signatures() -> array

      Every signature field at the top of the form's field list as a :php:class:`Signature`,
      signed or not, in order and with repeats; empty when there are none.
      See :ref:`signatures`.

      .. code-block:: php

         foreach ($doc->signatures() as $s) {
             if ($s->contents !== null) {
                 echo $s->subFilter, ' ', $s->time, ' ', $s->reason, "\n";
             }
         }

      :rtype: Signature[]
      :throws MemoryException: The list would not fit under ``memory_limit``.
      :throws BusyException: ZTS only: another thread is using PDFium.

   .. rubric:: Forms

   .. php:method:: formType() -> FormType

      Which kind of form the document has, read from its catalog; see :php:class:`FormType`. Unlike
      the other form methods, it leaves the document unchanged.

      :rtype: FormType
      :throws BusyException: ZTS only: another thread is using PDFium.

   .. rubric:: Pages

   .. php:method:: page(int $index) -> Page

      Loads one page.

      :param int $index: Page number, starting at 0.
      :rtype: Page
      :throws \\ValueError: The index is out of range.
      :throws FormatException: PDFium cannot load the page.
      :throws BusyException: ZTS only: another thread is using PDFium.

   .. php:method:: deletePage(int $index) -> void

      Removes a page from this document. Deleting the only page leaves a document with no
      pages, which :php:meth:`save` and :php:meth:`toString` can write.

      Deleting inside ``foreach`` can make the loop skip a page; see :ref:`page-editing`.

      :param int $index: Page number, starting at 0.
      :throws \\ValueError: The index is out of range.
      :throws PdfiumException: PDFium did not remove the page; held pages may then throw, see
         :ref:`failed edits <failed-edits>`.
      :throws BusyException: ZTS only: another thread is using PDFium.

   .. php:method:: addPage(float $width, float $height, ?int $index = null) -> Page

      Adds a blank page of ``$width`` by ``$height`` points and returns it.

      The page is unrotated and exactly this size, whatever rotation or crop box its place in the page
      tree would give it. Adding inside ``foreach`` can make the loop see a page again. See :ref:`page-editing`.

      :param float $width: Width in points; finite and greater than 0.
      :param float $height: Height in points; finite and greater than 0.
      :param ?int $index: Where the page goes, from 0 to ``pageCount()``; ``null`` puts it after
         the last page.
      :rtype: Page
      :throws \\ValueError: A size is not finite or not above 0 once rounded to a float, or the
         index is out of range.
      :throws PdfiumException: PDFium cannot add the page, as can happen on a :ref:`damaged page
         tree <damaged-page-trees>`; held pages may then throw, see
         :ref:`failed edits <failed-edits>`.
      :throws BusyException: ZTS only: another thread is using PDFium.

   .. php:method:: insertPages(Document $source, ?array $indexes = null, ?int $index = null, bool $removeSecurity = false) -> void

      Copies pages of ``$source`` into this document at ``$index``. ``$indexes`` are int
      page indexes of ``$source`` in the order wanted, duplicates kept; ``null`` copies every page.
      ``$source`` may be this document; the pages then go through a temporary copy, so they are
      held twice in memory during the call. Bookmarks, attachments and viewer preferences are not
      copied, and copied form fields can no longer be filled in. It sets the document's producer to
      ``"PDFium"``, replacing the old one, if the file has document information (what
      :php:meth:`metadata` reads), as most files do.

      A copy can take its rotation and crop box from where it lands in this document's page tree, so
      its size and rotation can differ from the source page's. Inserting inside ``foreach`` can make
      the loop see a page again. See :ref:`page-editing`.

      :param Document $source: The document to copy from.
      :param ?array $indexes: Page indexes of ``$source``; ``null`` means every page.
      :param ?int $index: Where the pages go, from 0 to ``pageCount()``; ``null`` puts them after
         the last page.
      :param bool $removeSecurity: Needed only when ``$source`` is another, encrypted document:
         ``true`` lets its pages go without the source's password. They take this document's
         encryption, if any.
      :throws \\ValueError: ``$source`` has no pages, ``$indexes`` is empty or holds an index
         outside ``$source``'s pages, or ``$index`` is out of range.
      :throws \\TypeError: An element of ``$indexes`` is not an int.
      :throws UnsupportedException: ``$source`` is another, encrypted document and
         ``$removeSecurity`` is false.
      :throws MemoryException: The indexes would not fit under ``memory_limit``, or PDFium cannot
         make the temporary copy that copying within this document needs.
      :throws PdfiumException: PDFium cannot copy the pages, as can happen on a :ref:`damaged page
         tree <damaged-page-trees>`; held pages may then throw, see
         :ref:`failed edits <failed-edits>`.
      :throws BusyException: ZTS only: another thread is using PDFium.

   .. php:method:: movePages(array $indexes, int $to) -> void

      Moves the pages at ``$indexes`` so that they sit at ``$to``, ``$to + 1``, ... in the order
      listed.

      A moved page can take its rotation, boxes, fonts and images from its new place in the page
      tree, so its size, rotation and look can change. Moving pages inside ``foreach`` can make the
      loop skip or repeat a page. See :ref:`page-editing`.

      :param array $indexes: Distinct int page indexes to move.
      :param int $to: Where the first listed page goes, from 0 to ``pageCount() - count($indexes)``.
      :throws \\ValueError: ``$indexes`` is empty, holds an index twice or one outside the pages, or
         ``$to`` is out of range.
      :throws \\TypeError: An element of ``$indexes`` is not an int.
      :throws MemoryException: The indexes would not fit under ``memory_limit``.
      :throws PdfiumException: PDFium fails, as it can on a :ref:`damaged page tree
         <damaged-page-trees>`; every held page then throws, see :ref:`failed edits <failed-edits>`.
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
      :throws MemoryException: The indexes would not fit under ``memory_limit``, or PDFium cannot
         create the new document.
      :throws PdfiumException: PDFium cannot copy the pages.
      :throws BusyException: ZTS only: another thread is using PDFium.

   .. php:method:: nUp(float $width, float $height, int $columns, int $rows, bool $removeSecurity = false) -> Document

      A new document with this document's pages scaled onto sheets of ``$width`` by ``$height``
      points, ``$columns`` across and ``$rows`` down, in page order left to right, then top to
      bottom. Each page is scaled up or down to fit its cell, keeping its proportions, and centred
      in it. The last sheet may be partly empty. The sheets show the pages' content only, without
      links, form fields or other annotations. With one column and one row, PDFium instead copies
      the pages whole and ignores ``$width`` and ``$height``: links stay, and form fields draw but
      can no longer be filled in.

      The new document is unencrypted and has no viewer preferences, bookmarks, attachments or
      metadata beyond what PDFium writes.

      :param float $width: Sheet width in points, finite and greater than 0.
      :param float $height: Sheet height in points, finite and greater than 0.
      :param int $columns: Pages across a sheet, at least 1.
      :param int $rows: Pages down a sheet, at least 1.
      :param bool $removeSecurity: Must be ``true`` if this document is encrypted, because PDFium
         cannot encrypt the result.
      :rtype: Document
      :throws \\ValueError: A size is not finite or not above 0 once rounded to a float, or a count
         is below 1.
      :throws UnsupportedException: This document is encrypted and ``$removeSecurity`` is ``false``.
      :throws PdfiumException: This document has no pages, or ``$columns`` and ``$rows`` are too
         large to lay out.
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


AttachmentMetadata
------------------

.. php:class:: AttachmentMetadata

   An embedded file's details, returned by :php:meth:`Attachment::metadata`: a final, read-only
   value. Each property is a UTF-8 string, or ``null`` when the entry is empty or missing. Dates are
   as stored in the PDF, for example ``D:20261031120000Z``. There is no size, as PDFium does not
   read the stored one; use ``strlen($attachment->contents())``.

   .. php:attr:: description

      ``?string``

   .. php:attr:: subtype

      ``?string``: the MIME type, such as ``application/pdf``.

   .. php:attr:: afRelationship

      ``?string``: how the file relates to the document: ``Source``, ``Data``, ``Alternative``,
      ``Supplement``, ``Unspecified`` or another name.

   .. php:attr:: creationDate

      ``?string``

   .. php:attr:: modDate

      ``?string``

   .. php:attr:: checksum

      ``?string``: the MD5 the PDF stores for the file, as 32 lowercase hex digits. It is not checked
      against :php:meth:`Attachment::contents`. ``null`` when missing or not stored as a hex
      string.


Signature
---------

.. php:class:: Signature

   A signature field's details, returned by :php:meth:`Document::signatures`: a final, read-only
   value. A value that is missing or empty comes back as ``null`` (``byteRange`` as an empty
   array), so an unsigned field is all nulls. The signer's name, location and contact details and
   the signature handler (``/Filter``) are not available.

   .. php:attr:: contents

      ``?string``: the signature's bytes as stored, usually a DER-encoded PKCS#7 or PKCS#1 object,
      with any padding the signer left. ``null`` when missing or empty.

   .. php:attr:: byteRange

      ``array``: the signed parts of the file as ``[offset, length]`` pairs, counted in the file as
      it was opened, not in :php:meth:`Document::save` or :php:meth:`Document::toString` output.
      Unchecked; a number that does not fit in 32 bits comes back wrong.

   .. php:attr:: subFilter

      ``?string``: the signature's format, such as ``adbe.pkcs7.detached`` or
      ``ETSI.CAdES.detached``; raw bytes, not necessarily valid UTF-8.

   .. php:attr:: reason

      ``?string``: the signer's stated reason, as UTF-8.

   .. php:attr:: time

      ``?string``: the signing time the file claims, such as ``D:20261118120000+02'00'``; raw
      bytes, not necessarily valid UTF-8.

   .. php:attr:: docMdpPermission

      ``?int``: the certification level the signature claims: 1 no changes, 2 form filling and
      signing, 3 also annotations. ``null`` for an ordinary signature.


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

   One page of a :php:class:`Document`. It follows its page when pages are deleted, added, copied
   in or moved, and :php:meth:`index` reports where the page is now. Once its page is deleted, or a
   failed edit leaves its position unknown, every method throws ``PdfiumException``; see
   :ref:`page-editing`.

   .. php:method:: index() -> int

      The page's position in its document, starting at 0.

      :rtype: int
      :throws PdfiumException: The page was deleted from its document, or its position is unknown after a failed edit.

   .. php:method:: width() -> float

      Width of the area where the media box and crop box overlap, in points (1/72 inch), after
      rotation. For example, ``595.28`` for A4.

      :rtype: float
      :throws PdfiumException: The page was deleted from its document, or its position is unknown after a failed edit.
      :throws BusyException: ZTS only: another thread is using PDFium.

   .. php:method:: height() -> float

      Height of the area where the media box and crop box overlap, in points, after rotation. For
      example, ``841.89`` for A4.

      :rtype: float
      :throws PdfiumException: The page was deleted from its document, or its position is unknown after a failed edit.
      :throws BusyException: ZTS only: another thread is using PDFium.

   .. php:method:: rotation() -> int

      Rotation in degrees: ``0``, ``90``, ``180`` or ``270``. Unlike the box getters, it includes a
      rotation the page takes from its page tree; see :ref:`page-editing`.

      :rtype: int
      :throws PdfiumException: The page was deleted from its document, or its position is unknown after a failed edit.
      :throws BusyException: ZTS only: another thread is using PDFium.

   .. php:method:: setRotation(int $degrees) -> void

      Sets the rotation, clockwise and absolute, not added to the current one.
      :php:meth:`width` and :php:meth:`height` follow, also on every other :php:class:`Page`
      object at the same index.

      :param int $degrees: ``0``, ``90``, ``180`` or ``270``.
      :throws \\ValueError: ``$degrees`` is another value.
      :throws PdfiumException: The page was deleted from its document, or its position is unknown after a failed edit.
      :throws BusyException: ZTS only: another thread is using PDFium.

   .. php:method:: mediaBox() -> ?Rect
                   cropBox() -> ?Rect
                   bleedBox() -> ?Rect
                   trimBox() -> ?Rect
                   artBox() -> ?Rect

      The media box is the paper size, and the crop box the part of it that is shown and printed.
      The bleed, trim and art boxes are used in print production; most files leave them unset.

      The page's own box, in points, as stored, with the corners ordered so that ``left <= right``
      and ``bottom <= top``; a box other than the media box is not clipped to it. ``null`` when
      the page has none of its own, also when it takes one from its page tree; see
      :ref:`page-editing`.

      :rtype: ?Rect
      :throws PdfiumException: The page was deleted from its document, or its position is unknown after a failed edit.
      :throws BusyException: ZTS only: another thread is using PDFium.

   .. php:method:: setMediaBox(Rect $box) -> void
                   setCropBox(Rect $box) -> void
                   setBleedBox(Rect $box) -> void
                   setTrimBox(Rect $box) -> void
                   setArtBox(Rect $box) -> void

      Writes the page's own box as given. A box other than the media box is not checked against
      it; viewers clip it to the media box. Every other :php:class:`Page` object at the same index
      reports the new box too. After :php:meth:`setMediaBox` or :php:meth:`setCropBox`,
      :php:meth:`width` and :php:meth:`height` follow, on every such object; the bleed, trim and
      art boxes do not affect them. A crop box and media box that do not overlap leave the page
      with zero width and height, and :php:meth:`render` then returns a 1 × 1 image of just the
      background.

      :param Rect $box: The box, in points.
      :throws \\ValueError: A side is unset or not finite, or ``left`` is not less than ``right``
         or ``bottom`` not less than ``top`` once rounded to floats.
      :throws PdfiumException: The page was deleted from its document, or its position is unknown after a failed edit.
      :throws BusyException: ZTS only: another thread is using PDFium.

   .. php:method:: text() -> string

      All text on the page, as UTF-8, with ``\r\n`` between lines. Returns ``""`` if the page has
      no text. See :ref:`text-positions` for hyphenated words and characters without Unicode. To
      also search the text or read positions, use :php:meth:`textPage`, which reads the text only
      once.

      :rtype: string
      :throws FormatException: PDFium cannot read the page's text.
      :throws MemoryException: The text would not fit under ``memory_limit``.
      :throws PdfiumException: The page was deleted from its document, or its position is unknown after a failed edit.
      :throws BusyException: ZTS only: another thread is using PDFium.

   .. php:method:: render(int $dpi = 150, ?Rect $region = null, bool $transparent = false, bool $annotations = true, bool $grayscale = false) -> Bitmap

      Renders the page to a bitmap. See :ref:`rendering`.

      :param int $dpi: Resolution, from 1 to 2400.
      :param ?Rect $region: Render only this area, in points, as for
         :ref:`text positions <text-positions>`.
      :param bool $transparent: Render on a transparent background instead of white.
      :param bool $annotations: Draw annotations and form fields. Form fields, and annotations of
         most types, that have no appearance stream get one, stored in the document; on a form with
         ``/NeedAppearances true`` the fields' streams can be replaced too. A later save writes
         them. ``false`` leaves the document unchanged. See :ref:`annotations`.
      :param bool $grayscale: Render in gray: PNG and JPEG output have one gray channel, two in a
         transparent PNG.
      :rtype: Bitmap
      :throws \\ValueError: The DPI is out of range, a limit in :ref:`rendering` is exceeded, or the
         region has no width or height, does not overlap the page, or has a side unset or one its
         constructor refuses, as in one made without its constructor.
      :throws MemoryException: The bitmap would not fit under ``memory_limit``.
      :throws PdfiumException: The page was deleted from its document, or its position is unknown after a failed edit.
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
      :throws PdfiumException: The page was deleted from its document, or its position is unknown after a failed edit.
      :throws BusyException: ZTS only: another thread is using PDFium.

   .. rubric:: Navigation

   .. php:method:: openAction() -> ?Action

      The action a viewer runs when it opens the page, or ``null``. See :ref:`navigation`.

      :rtype: ?Action
      :throws MemoryException: The action would not fit under ``memory_limit``.
      :throws PdfiumException: The page was deleted from its document, or its position is unknown after a failed edit.
      :throws BusyException: ZTS only: another thread is using PDFium.

   .. php:method:: closeAction() -> ?Action

      The action a viewer runs when it closes the page, or ``null``. See :ref:`navigation`.

      :rtype: ?Action
      :throws MemoryException: The action would not fit under ``memory_limit``.
      :throws PdfiumException: The page was deleted from its document, or its position is unknown after a failed edit.
      :throws BusyException: ZTS only: another thread is using PDFium.

   .. php:method:: links() -> array

      The page's link annotations, as :php:class:`Link` objects, back to front. These are the
      areas a viewer makes clickable; URLs that are only written in the text are in
      :php:meth:`TextPage::webLinks`.

      .. code-block:: php

         foreach ($page->links() as $link) {
             if ($link->action?->type === 'URI') {
                 echo $link->action->uri, "\n";
             } elseif ($link->destination) {
                 echo 'page ', $link->destination->pageIndex + 1, "\n";
             }
         }

      :rtype: Link[]
      :throws MemoryException: The links would not fit under ``memory_limit``.
      :throws PdfiumException: The page was deleted from its document, or its position is unknown after a failed edit.
      :throws BusyException: ZTS only: another thread is using PDFium.

   .. php:method:: linkAt(float $x, float $y) -> ?Link

      The topmost link annotation at a point, or ``null`` if there is none.

      Once ``linkAt()`` has looked at a page of a document, it keeps finding that page's link
      annotations as they were then: a link removed since with :php:meth:`removeAnnotation` or
      :php:meth:`Annotation::remove` is still found, and one added with :php:meth:`addAnnotation` is
      not. :php:meth:`links` and :php:meth:`Annotation::link` see the change; use them after adding
      or removing annotations. See :ref:`annotations`.

      :param float $x: Horizontal position in points.
      :param float $y: Vertical position in points, from the bottom of the page.
      :rtype: ?Link
      :throws \\ValueError: ``$x`` or ``$y`` is not finite.
      :throws MemoryException: The link would not fit under ``memory_limit``.
      :throws PdfiumException: The page was deleted from its document, or its position is unknown after a failed edit.
      :throws BusyException: ZTS only: another thread is using PDFium.

   .. rubric:: Annotations

   .. php:method:: annotationCount() -> int

      The number of annotations on the page, of every type, including form fields (Widget
      annotations).

      :rtype: int
      :throws BusyException: ZTS only: another thread is using PDFium.

   .. php:method:: annotations() -> array

      Every annotation on the page as an :php:class:`Annotation`, back to front, keyed by the index
      that :php:meth:`annotation` takes. An entry of a damaged list that is not an annotation is
      left out, so keys can skip numbers. Each call returns new objects.

      .. code-block:: php

         foreach ($page->annotations() as $index => $a) {
             echo $index, ' ', $a->type(), "\n";
         }

      :rtype: Annotation[]
      :throws MemoryException: The list would not fit under ``memory_limit``.
      :throws BusyException: ZTS only: another thread is using PDFium.

   .. php:method:: annotation(int $index) -> Annotation

      One annotation, as a new object.

      :param int $index: Annotation number, starting at 0.
      :rtype: Annotation
      :throws \\ValueError: The index is out of range.
      :throws FormatException: The entry is not an annotation, in a damaged list.
      :throws BusyException: ZTS only: another thread is using PDFium.

   .. php:method:: addAnnotation(string $type, Rect $rect) -> Annotation

      Adds an annotation at the end of the page's list, in place: :php:meth:`Document::save` or
      :php:meth:`Document::toString` writes it out. It has no colour, contents or appearance yet,
      and its flags are 0: add :php:const:`FLAG_PRINT <Annotation::FLAG_PRINT>` for viewers to
      print it. PDFium creates ``"Text"``, ``"Link"``, ``"FreeText"``, ``"Square"``, ``"Circle"``,
      ``"Highlight"``, ``"Underline"``, ``"Squiggly"``, ``"StrikeOut"``, ``"Stamp"``, ``"Ink"``,
      ``"Popup"`` and ``"FileAttachment"`` annotations.

      .. code-block:: php

         $note = $page->addAnnotation('Text', new Pdfium\Rect(100, 650, 120, 670));
         echo $note->index();   // the last index

      :param string $type: A type name as :php:meth:`Annotation::type` gives it, with the same case.
      :param Rect $rect: Where the annotation goes on the page, in points.
      :rtype: Annotation
      :throws \\ValueError: ``$type`` is not such a name, or is ``"Unknown"``; or a side of
         ``$rect`` is unset or one its constructor refuses, as in one made without its constructor.
      :throws UnsupportedException: PDFium cannot create annotations of that type, such as
         ``"Line"`` or ``"Widget"``.
      :throws PdfiumException: PDFium cannot add the annotation.
      :throws BusyException: ZTS only: another thread is using PDFium.

   .. php:method:: removeAnnotation(int $index) -> void

      Removes an annotation from the page, in place. The annotations after it move down one index,
      and every :php:class:`Annotation` for the removed one throws from then on; the others stay
      usable. So does every :php:class:`FormField` of another widget, while one of a removed Widget
      throws. On a damaged page that lists the annotation twice, nothing throws; see
      :ref:`annotations`. To remove several by index, remove from the highest down. A Widget's form
      field stays in the document.

      :param int $index: Annotation number, starting at 0.
      :throws \\ValueError: The index is out of range.
      :throws PdfiumException: PDFium cannot remove the annotation.
      :throws BusyException: ZTS only: another thread is using PDFium.

   .. rubric:: Forms

   .. php:method:: formFields() -> array

      A :php:class:`FormField` for every widget on the page, back to front. A field with several
      widgets, such as a radio group, gives one for each. Links and other annotations are not
      listed. Empty when the page has no widgets or PDFium cannot set up its form. Like
      :php:meth:`render`, this can add appearance streams that a later save writes; see
      :ref:`pdfium-defaults`.

      .. code-block:: php

         foreach ($page->formFields() as $field) {
             echo $field->name(), ' (', $field->type()->name, '): ', $field->value(), "\n";
         }

      :rtype: FormField[]
      :throws MemoryException: The fields would not fit under ``memory_limit``.
      :throws BusyException: ZTS only: another thread is using PDFium.

   .. php:method:: formFieldAt(float $x, float $y) -> ?FormField

      The form field whose widget is at a point, or ``null`` if there is none. A widget that PDFium
      cannot tie to a field, which :php:meth:`formFields` lists with the type ``Unknown``, is not
      found; see :ref:`forms`.

      :param float $x: Horizontal position in points.
      :param float $y: Vertical position in points, from the bottom of the page.
      :rtype: ?FormField
      :throws \\ValueError: ``$x`` or ``$y`` is not finite.
      :throws BusyException: ZTS only: another thread is using PDFium.

   .. php:method:: flatten(bool $forPrint = false) -> bool

      Turns every annotation of the page, form fields with their current values and links included,
      into page content, in place and for good. This ``Page`` shows the new content, but objects made
      before the flatten do not; see :ref:`forms`.

      .. code-block:: php

         $page->formFields()[0]->setValue('Jane');
         $page->flatten();
         $doc->save('flat.pdf');

      :param bool $forPrint: Flatten for printing instead of for the screen.
      :returns: ``false`` when the page has nothing to flatten.
      :rtype: bool
      :throws PdfiumException: PDFium cannot flatten the page.
      :throws FormatException: PDFium cannot load the flattened page again; every later call on this
         ``Page`` but :php:meth:`index` throws it too, so fetch the page again with
         :php:meth:`Document::page`.
      :throws BusyException: ZTS only: another thread is using PDFium.


Attachment
----------

.. php:class:: Attachment

   A file embedded in a :php:class:`Document`, from :php:meth:`Document::attachments`,
   :php:meth:`Document::attachment` or :php:meth:`Document::addAttachment`. It keeps its document
   alive. Once a file is added to or deleted from the document, it is stale and must be fetched
   again; see :ref:`attachments`.

   One from :php:meth:`Annotation::attachment` or :php:meth:`Annotation::setAttachment` is the file
   of a FileAttachment annotation instead. It keeps its annotation alive, is separate from the
   document's list, and its :php:meth:`index` always throws. It reads the annotation's current
   file on every call, also after :php:meth:`Annotation::setAttachment` or
   :php:meth:`Annotation::setStringValue` with the key ``FS`` replaces it. Adding or deleting a
   document file does not affect it. Every method throws once the annotation is removed from its
   page or has no file. See :ref:`annotations`.

   .. php:method:: index() -> int

      The attachment's position in its document, starting at 0. For one returned by
      :php:meth:`Document::addAttachment`, each call searches the list, so keep the result.

      :rtype: int
      :throws PdfiumException: The document's attachments changed since this one was fetched, or
         PDFium cannot find it in a damaged list, or the file belongs to an annotation.
      :throws BusyException: ZTS only: another thread is using PDFium.

   .. php:method:: name() -> string

      The file name the entry gives, as UTF-8; empty when it gives none. It can differ from the key
      the list is sorted by, and on macOS each ``/`` shows as ``:``; see :ref:`attachments`.

      :rtype: string
      :throws PdfiumException: The document's attachments changed since this one was fetched.
         For an annotation's file: its annotation was removed (see above), or PDFium cannot find
         the file.
      :throws MemoryException: The name would not fit under ``memory_limit``.
      :throws BusyException: ZTS only: another thread is using PDFium.

   .. php:method:: contents() -> ?string

      The file's bytes, decoded. ``null`` when there is no embedded file PDFium can read, for
      example when the entry only points to a file outside the PDF. When PDFium cannot decode the
      file, these are the bytes as stored; see :ref:`attachments`.

      :rtype: ?string
      :throws PdfiumException: The document's attachments changed since this one was fetched, or
         reading the file fails partway.
         For an annotation's file: its annotation was removed (see above), or PDFium cannot find
         the file.
      :throws MemoryException: The file would not fit under ``memory_limit``.
      :throws BusyException: ZTS only: another thread is using PDFium.

   .. php:method:: metadata() -> AttachmentMetadata

      The file's description, MIME type, relationship to the document, dates and checksum.

      :rtype: AttachmentMetadata
      :throws PdfiumException: The document's attachments changed since this one was fetched.
         For an annotation's file: its annotation was removed (see above), or PDFium cannot find
         the file.
      :throws MemoryException: An entry would not fit under ``memory_limit``.
      :throws BusyException: ZTS only: another thread is using PDFium.


Annotation
----------

.. php:class:: Annotation

   An annotation on a :php:class:`Page`, from :php:meth:`Page::annotations`,
   :php:meth:`Page::annotation`, :php:meth:`Page::addAnnotation` or :php:meth:`linkedAnnotation`. It
   keeps its page and document alive. Two objects for the same annotation see each other's
   changes, but they are not ``==``; compare :php:meth:`index` instead. Once the annotation is
   removed from its page, by :php:meth:`remove` or :php:meth:`Page::removeAnnotation`, or its page
   is flattened with :php:meth:`Page::flatten`, every method throws :php:class:`PdfiumException`.
   It follows its page when pages are moved, added or deleted, and throws as the
   :php:class:`Page`'s methods do once the page is deleted or its position is unknown; see
   :ref:`page-editing`.

   The setters change the loaded document, in place: :php:meth:`Document::save` or
   :php:meth:`Document::toString` writes the change out. What :php:meth:`Page::render` draws for an
   annotation is its appearance stream, which blocks some changes; see :ref:`annotations`.

   The bits of :php:meth:`flags`, as the PDF standard defines them:

   .. php:const:: FLAG_INVISIBLE

      ``1``

   .. php:const:: FLAG_HIDDEN

      ``2``

   .. php:const:: FLAG_PRINT

      ``4``

   .. php:const:: FLAG_NO_ZOOM

      ``8``

   .. php:const:: FLAG_NO_ROTATE

      ``16``

   .. php:const:: FLAG_NO_VIEW

      ``32``

   .. php:const:: FLAG_READ_ONLY

      ``64``

   .. php:const:: FLAG_LOCKED

      ``128``

   .. php:const:: FLAG_TOGGLE_NO_VIEW

      ``256``


   Appearance modes, for how the annotation looks normally, under the pointer and when pressed:

   .. php:const:: APPEARANCE_NORMAL

      ``0``

   .. php:const:: APPEARANCE_ROLLOVER

      ``1``

   .. php:const:: APPEARANCE_DOWN

      ``2``


   .. php:method:: index() -> int

      The annotation's current position on its page, starting at 0. For an annotation that a
      damaged page lists twice, the first; see :ref:`annotations`.

      :rtype: int
      :throws PdfiumException: The annotation was removed from its page.
      :throws BusyException: ZTS only: another thread is using PDFium.

   .. php:method:: type() -> string

      The PDF subtype: ``"Text"``, ``"Link"``, ``"FreeText"``, ``"Line"``, ``"Square"``,
      ``"Circle"``, ``"Polygon"``, ``"PolyLine"``, ``"Highlight"``, ``"Underline"``,
      ``"Squiggly"``, ``"StrikeOut"``, ``"Stamp"``, ``"Caret"``, ``"Ink"``, ``"Popup"``,
      ``"FileAttachment"``, ``"Sound"``, ``"Movie"``, ``"Widget"``, ``"Screen"``,
      ``"PrinterMark"``, ``"TrapNet"``, ``"Watermark"``, ``"3D"``, ``"RichMedia"``,
      ``"XFAWidget"``, ``"Redact"``, or ``"Unknown"`` for any other.

      :rtype: string
      :throws PdfiumException: The annotation was removed from its page.
      :throws BusyException: ZTS only: another thread is using PDFium.

   .. php:method:: rect() -> ?Rect

      Where the annotation is on the page, in points, with left below right and bottom below top.
      PDFium gives 0, 0, 0, 0 for an annotation without a rectangle; ``null`` when it cannot read
      one.

      :rtype: ?Rect
      :throws PdfiumException: The annotation was removed from its page.
      :throws BusyException: ZTS only: another thread is using PDFium.

   .. php:method:: setRect(Rect $rect) -> void

      Moves or resizes the annotation, in place. An appearance stream stays, and
      :php:meth:`Page::render` draws it in the new rectangle, except for a Highlight, Underline,
      Squiggly or StrikeOut whose appearance PDFium generated: that one is drawn at its quad points,
      so change those too, and call :php:meth:`clearAppearance` for the next render to draw them.

      :param Rect $rect: The new rectangle, in points.
      :throws \\ValueError: A side of ``$rect`` is unset or one its constructor refuses, as in one
         made without its constructor.
      :throws PdfiumException: The annotation was removed from its page, or PDFium refuses the
         rectangle.
      :throws BusyException: ZTS only: another thread is using PDFium.

   .. php:method:: flags() -> int

      The annotation flags, a combination of the ``FLAG_*`` constants, from 0 to 4294967295.

      :rtype: int
      :throws PdfiumException: The annotation was removed from its page.
      :throws BusyException: ZTS only: another thread is using PDFium.

   .. php:method:: setFlags(int $flags) -> void

      Replaces the annotation flags.

      .. code-block:: php

         $a->setFlags($a->flags() | Pdfium\Annotation::FLAG_HIDDEN);

      :param int $flags: A combination of the ``FLAG_*`` constants; other bits are written as given.
      :throws \\ValueError: ``$flags`` is outside 0 to 4294967295.
      :throws PdfiumException: The annotation was removed from its page, or PDFium refuses the
         flags.
      :throws BusyException: ZTS only: another thread is using PDFium.

   .. php:method:: color() -> ?Color

      The colour (``/C``), with the opacity ``/CA`` as alpha, rounded down: 0.5 is 127. A
      component below 0 in the file reads as 0, one above 1 as 255; one far above 1 may read as 0
      instead. Without ``/C``, PDFium gives yellow (255, 255, 0) for a Highlight and black for other
      types. ``null`` while the annotation has a normal appearance stream, whatever ``/C`` holds;
      see :ref:`annotations`.

      :rtype: ?Color
      :throws PdfiumException: The annotation was removed from its page.
      :throws BusyException: ZTS only: another thread is using PDFium.

   .. php:method:: interiorColor() -> ?Color

      The interior colour (``/IC``), read as :php:meth:`color` reads ``/C``: with ``/CA`` as alpha,
      yellow for a Highlight and black for other types when there is no ``/IC``, and ``null`` while
      the annotation has a normal appearance stream.

      :rtype: ?Color
      :throws PdfiumException: The annotation was removed from its page.
      :throws BusyException: ZTS only: another thread is using PDFium.

   .. php:method:: setColor(int $red, int $green, int $blue, int $alpha = 255) -> void

      Sets the colour (``/C``) and the opacity (``/CA``). PDFium refuses while the annotation has a
      normal appearance stream, which most types get from a :php:meth:`Page::render` with
      annotations. Call :php:meth:`clearAppearance` first; the extension never drops one for you.
      The next render then draws the new colour for the types listed in :ref:`annotations`; any
      other type, except a form field, is left with no appearance and draws nothing.

      :param int $red: From 0 to 255.
      :param int $green: From 0 to 255.
      :param int $blue: From 0 to 255.
      :param int $alpha: The opacity, from 0 (transparent) to 255 (opaque).
      :throws \\ValueError: A component is outside 0 to 255.
      :throws PdfiumException: The annotation was removed from its page, it has a normal
         appearance stream (the message says to call :php:meth:`clearAppearance` first), or PDFium
         refuses the colour.
      :throws BusyException: ZTS only: another thread is using PDFium.

   .. php:method:: setInteriorColor(int $red, int $green, int $blue, int $alpha = 255) -> void

      Sets the interior colour (``/IC``) and the opacity (``/CA``), as :php:meth:`setColor` does,
      with the same refusal while the annotation has a normal appearance stream. Both colours share
      ``/CA``, so ``$alpha``, 255 by default, replaces an opacity :php:meth:`setColor` set.

      :param int $red: From 0 to 255.
      :param int $green: From 0 to 255.
      :param int $blue: From 0 to 255.
      :param int $alpha: The opacity, from 0 (transparent) to 255 (opaque).
      :throws \\ValueError: A component is outside 0 to 255.
      :throws PdfiumException: The annotation was removed from its page, it has a normal
         appearance stream, or PDFium refuses the colour.
      :throws BusyException: ZTS only: another thread is using PDFium.

   .. php:method:: border() -> ?Border

      The border (``/Border``); ``null`` when the annotation has none, or one with fewer than three
      numbers.

      :rtype: ?Border
      :throws PdfiumException: The annotation was removed from its page.
      :throws BusyException: ZTS only: another thread is using PDFium.

   .. php:method:: setBorder(float $horizontalRadius, float $verticalRadius, float $width) -> void

      Sets the border (``/Border``), in points. This also removes the annotation's appearance
      streams of every mode, including one another tool wrote. The next :php:meth:`Page::render`
      generates a new one only for the types listed in :ref:`annotations` and for form fields; an
      annotation of another type, such as a Stamp, then draws nothing.

      :param float $horizontalRadius: The horizontal radius of the rounded corners.
      :param float $verticalRadius: The vertical radius of the rounded corners.
      :param float $width: The line width.
      :throws \\ValueError: A value is not finite or is negative.
      :throws PdfiumException: The annotation was removed from its page, or PDFium refuses the
         border.
      :throws BusyException: ZTS only: another thread is using PDFium.

   .. php:method:: quadPoints() -> array

      The quad points of a Highlight, Underline, Squiggly, StrikeOut or Link annotation: the areas
      it covers. Empty when the annotation has none.

      :rtype: Quad[]
      :throws PdfiumException: The annotation was removed from its page.
      :throws MemoryException: The list would not fit under ``memory_limit``.
      :throws BusyException: ZTS only: another thread is using PDFium.

   .. php:method:: addQuadPoints(Quad $quad) -> void

      Adds an area to the quad points of a Highlight, Underline, Squiggly, StrikeOut or Link
      annotation. A new highlight needs one before it shows:

      .. code-block:: php

         $h = $page->addAnnotation('Highlight', new Pdfium\Rect(72, 700, 300, 714));
         $h->addQuadPoints(new Pdfium\Quad(72, 714, 300, 714, 72, 700, 300, 700));

      While the annotation has an appearance stream, :php:meth:`Page::render` keeps drawing it;
      the area shows after :php:meth:`clearAppearance`.

      :param Quad $quad: The area, corners in the order upper left, upper right, lower left, lower
         right.
      :throws \\ValueError: A corner of ``$quad`` is unset or not finite, as in one made without its
         constructor.
      :throws PdfiumException: The annotation was removed from its page, or PDFium refuses: its type
         has no quad points.
      :throws BusyException: ZTS only: another thread is using PDFium.

   .. php:method:: setQuadPoints(int $index, Quad $quad) -> void

      Replaces one area of the quad points. PDFium has no call to remove one. While the annotation
      has an appearance stream, :php:meth:`Page::render` keeps drawing it; the new area shows
      after :php:meth:`clearAppearance`. See :ref:`annotations`.

      :param int $index: The area's index in :php:meth:`quadPoints`.
      :param Quad $quad: The new area.
      :throws \\ValueError: ``$index`` is out of range, or a corner of ``$quad`` is unset or not
         finite.
      :throws PdfiumException: The annotation was removed from its page, or PDFium refuses: its type
         has no quad points.
      :throws BusyException: ZTS only: another thread is using PDFium.

   .. php:method:: vertices() -> array

      The vertices (``/Vertices``) of a Polygon or PolyLine annotation; empty for other types.

      :rtype: Point[]
      :throws PdfiumException: The annotation was removed from its page.
      :throws MemoryException: The list would not fit under ``memory_limit``.
      :throws BusyException: ZTS only: another thread is using PDFium.

   .. php:method:: line() -> ?array

      The start and end points (``/L``) of a Line annotation, in that order; ``null`` for other
      types and for a Line without a valid ``/L``.

      :rtype: ?Point[]
      :throws PdfiumException: The annotation was removed from its page.
      :throws BusyException: ZTS only: another thread is using PDFium.

   .. php:method:: inkStrokes() -> array

      The strokes (``/InkList``) of an Ink annotation, one list of :php:class:`Point` per stroke;
      empty for other types.

      :rtype: Point[][]
      :throws PdfiumException: The annotation was removed from its page.
      :throws MemoryException: The strokes would not fit under ``memory_limit``.
      :throws BusyException: ZTS only: another thread is using PDFium.

   .. php:method:: addInkStroke(array $points) -> int

      Adds a stroke to an Ink annotation and returns its index in :php:meth:`inkStrokes`. While the
      annotation has an appearance stream, :php:meth:`Page::render` keeps drawing it; the stroke
      shows after :php:meth:`clearAppearance`.

      .. code-block:: php

         $ink = $page->addAnnotation('Ink', new Pdfium\Rect(100, 650, 300, 700));
         $ink->addInkStroke([
             new Pdfium\Point(110, 690), new Pdfium\Point(200, 660), new Pdfium\Point(290, 690),
         ]);

      :param Point[] $points: The stroke's points, in order.
      :rtype: int
      :throws \\ValueError: ``$points`` is empty, or a point has a coordinate unset or not finite.
      :throws \\TypeError: An element of ``$points`` is not a :php:class:`Point`.
      :throws PdfiumException: The annotation was removed from its page, or PDFium refuses: it is not
         an Ink annotation.
      :throws MemoryException: The points would not fit under ``memory_limit``.
      :throws BusyException: ZTS only: another thread is using PDFium.

   .. php:method:: removeInkStrokes() -> void

      Removes every stroke of an Ink annotation. An appearance stream stays, and
      :php:meth:`Page::render` keeps drawing the old strokes until :php:meth:`clearAppearance`.

      :throws PdfiumException: The annotation was removed from its page, or PDFium refuses: it is not
         an Ink annotation.
      :throws BusyException: ZTS only: another thread is using PDFium.

   .. php:method:: hasKey(string $key) -> bool

      Whether the annotation's dictionary has the key.

      :param string $key: A PDF key without the slash, such as ``"Contents"``, ``"T"`` (the author),
         ``"M"`` (the modification date), ``"Subj"``, ``"NM"`` or ``"CreationDate"``.
      :rtype: bool
      :throws \\ValueError: ``$key`` is empty or contains a null byte.
      :throws PdfiumException: The annotation was removed from its page.
      :throws BusyException: ZTS only: another thread is using PDFium.

   .. php:method:: valueType(string $key) -> string

      The type of the key's value: ``"Boolean"``, ``"Number"``, ``"String"``, ``"Name"``,
      ``"Array"``, ``"Dictionary"``, ``"Stream"``, ``"Null"``, ``"Reference"`` (an indirect
      reference, such as ``/Popup``, even when the object it names does not exist), or
      ``"Unknown"`` when the key is missing.

      :param string $key: A PDF key without the slash, as for :php:meth:`hasKey`.
      :rtype: string
      :throws \\ValueError: ``$key`` is empty or contains a null byte.
      :throws PdfiumException: The annotation was removed from its page.
      :throws BusyException: ZTS only: another thread is using PDFium.

   .. php:method:: stringValue(string $key) -> ?string

      The key's string or name value as UTF-8; ``null`` when the key is missing, the value is
      empty, or it is of another type, such as a number.

      :param string $key: A PDF key without the slash, as for :php:meth:`hasKey`.
      :rtype: ?string
      :throws \\ValueError: ``$key`` is empty or contains a null byte.
      :throws PdfiumException: The annotation was removed from its page.
      :throws MemoryException: The value would not fit under ``memory_limit``.
      :throws BusyException: ZTS only: another thread is using PDFium.

   .. php:method:: numberValue(string $key) -> ?float

      The key's number value; ``null`` when the key is missing or not a number.

      :param string $key: A PDF key without the slash, as for :php:meth:`hasKey`.
      :rtype: ?float
      :throws \\ValueError: ``$key`` is empty or contains a null byte.
      :throws PdfiumException: The annotation was removed from its page.
      :throws BusyException: ZTS only: another thread is using PDFium.

   .. php:method:: linkedAnnotation(string $key) -> ?Annotation

      The annotation the key points to, as a new object: ``"Popup"`` gives a markup annotation's
      pop-up, ``"IRT"`` the annotation a reply answers, ``"Parent"`` a pop-up's annotation. It
      follows one step only. It returns ``null`` when the key is missing or not a dictionary, or
      when the annotation it points to is not in this page's annotation list, such as one of
      another page. It also returns ``null`` when that dictionary has no ``/Type /Annot``, which
      PDF makes optional, so some files leave it out. See :ref:`annotations`.

      .. code-block:: php

         foreach ($page->annotations() as $a) {
             $answers = $a->linkedAnnotation('IRT');
             if ($answers !== null) {
                 echo $a->stringValue('Contents'), ' answers ', $answers->index(), "\n";
             }
         }

      :param string $key: A PDF key without the slash, as for :php:meth:`hasKey`.
      :rtype: ?Annotation
      :throws \\ValueError: ``$key`` is empty or contains a null byte.
      :throws PdfiumException: The annotation was removed from its page.
      :throws BusyException: ZTS only: another thread is using PDFium.

   .. php:method:: setStringValue(string $key, string $value) -> void

      Sets the key to a string, replacing any value it had. PDFium has no call to remove a key or to
      write another type. A key whose value must be a name, such as ``Subtype`` or ``AS``, then
      holds a string that PDFium and other viewers ignore: ``setStringValue('Subtype', 'Square')``
      makes :php:meth:`type` return ``"Unknown"``. Setting ``FS`` replaces the file of a
      FileAttachment annotation, as :php:meth:`setAttachment` does.

      The value is stored as given, so text that a PDF reader decodes differently does not read
      back as written. A value that starts with ``þÿ`` or ``ÿþ``, which a reader takes for a UTF-16
      byte order mark, reads back as UTF-16: ``"þÿAB"`` gives ``"䅂"``. One that starts with ``ï»¿``,
      a UTF-8 byte order mark, loses those three characters, and one with the escape character
      U+001B is cut there: ``"a\x1bb"`` gives ``"a"``.

      .. code-block:: php

         $note->setStringValue('Contents', 'Check this figure');
         $note->setStringValue('T', 'Reviewer');

      :param string $key: A PDF key without the slash, as for :php:meth:`hasKey`.
      :param string $value: The text, as UTF-8.
      :throws \\ValueError: ``$key`` is empty or contains a null byte, or ``$value`` is not valid
         UTF-8 or contains a null byte.
      :throws PdfiumException: The annotation was removed from its page, or PDFium refuses the
         value.
      :throws MemoryException: The value would not fit under ``memory_limit``.
      :throws BusyException: ZTS only: another thread is using PDFium.

   .. php:method:: appearance(int $mode = Annotation::APPEARANCE_NORMAL) -> ?string

      The annotation's appearance stream for one mode, as UTF-8 text; ``null`` when there is none
      or it is empty. The stream is decoded as a PDF text string: as UTF-16 when it starts with a
      UTF-16 byte order mark (FE FF or FF FE), as UTF-8 after a UTF-8 one (EF BB BF), otherwise one
      character per byte in PDFDocEncoding. ASCII comes back unchanged, other bytes do not: 0xE9
      reads as ``é``, and ``é`` stored as UTF-8 reads as ``Ã©``.

      :param int $mode: One of the ``APPEARANCE_*`` constants.
      :rtype: ?string
      :throws \\ValueError: ``$mode`` is not one of the ``APPEARANCE_*`` constants.
      :throws PdfiumException: The annotation was removed from its page.
      :throws MemoryException: The stream would not fit under ``memory_limit``.
      :throws BusyException: ZTS only: another thread is using PDFium.

   .. php:method:: setAppearance(string $stream, int $mode = Annotation::APPEARANCE_NORMAL) -> void

      Sets the appearance stream for one mode: PDF content operators that
      :php:meth:`Page::render` draws instead of what PDFium would generate. This is how a Stamp
      gets a picture. PDFium gives the stream the annotation's rectangle as its bounding box, so it
      draws in page coordinates:

      .. code-block:: php

         $stamp = $page->addAnnotation('Stamp', new Pdfium\Rect(100, 650, 300, 700));
         $stamp->setAppearance('1 0 0 rg 100 650 200 50 re f');   // fills the rectangle red

      The stream is stored one byte per character, in PDFDocEncoding. If any character is not in
      PDFDocEncoding, all of it is stored as UTF-16 and draws nothing. Keep it to ASCII, and write
      other bytes in strings as octal escapes or hex strings.

      On a form with ``/NeedAppearances true``, the next :php:meth:`Page::render` of the page with
      annotations replaces a Widget's stream with one PDFium generates, and a later save writes
      that; on other forms the stream stays. See :ref:`annotations`.

      :param string $stream: The content stream, as UTF-8.
      :param int $mode: One of the ``APPEARANCE_*`` constants.
      :throws \\ValueError: ``$stream`` is not valid UTF-8 or contains a null byte, or ``$mode`` is
         not one of the ``APPEARANCE_*`` constants.
      :throws PdfiumException: The annotation was removed from its page, or PDFium refuses the
         appearance: the annotation's rectangle has no width or height.
      :throws MemoryException: The stream would not fit under ``memory_limit``.
      :throws BusyException: ZTS only: another thread is using PDFium.

   .. php:method:: clearAppearance(int $mode = Annotation::APPEARANCE_NORMAL) -> void

      Removes the appearance stream of one mode; for ``APPEARANCE_NORMAL``, of all three. The next
      :php:meth:`Page::render` with annotations generates a new normal appearance only for the
      types listed in :ref:`annotations` and for form fields. An annotation of any other type, such
      as a Stamp, or a Line or Polygon from another tool, is left with no appearance and draws
      nothing.

      :param int $mode: One of the ``APPEARANCE_*`` constants.
      :throws \\ValueError: ``$mode`` is not one of the ``APPEARANCE_*`` constants.
      :throws PdfiumException: The annotation was removed from its page, or PDFium refuses.
      :throws BusyException: ZTS only: another thread is using PDFium.

   .. php:method:: link() -> ?Link

      The link of a Link annotation: the same :php:class:`Link` that :php:meth:`Page::links` gives
      for it. ``null`` for an annotation of any other type.

      :rtype: ?Link
      :throws PdfiumException: The annotation was removed from its page.
      :throws MemoryException: The link would not fit under ``memory_limit``.
      :throws BusyException: ZTS only: another thread is using PDFium.

   .. php:method:: setUri(string $uri) -> void

      Makes a Link annotation open a URI, in place: its action becomes a URI action, replacing
      any action it had. A destination (``/Dest``) the link had stays: :php:meth:`link` and
      :php:meth:`Page::links` report the action, but another viewer may follow the destination.
      The URI is stored as its bytes, as PDF URIs are, so keep it to ASCII and percent-encode other
      characters.

      .. code-block:: php

         $link = $page->addAnnotation('Link', new Pdfium\Rect(72, 700, 200, 720));
         $link->setUri('https://example.com/');
         echo $link->link()->action->uri;   // https://example.com/

      :param string $uri: The URI.
      :throws \\ValueError: ``$uri`` is empty or contains a null byte.
      :throws PdfiumException: The annotation was removed from its page, or it is not a Link
         annotation.
      :throws BusyException: ZTS only: another thread is using PDFium.

   .. php:method:: attachment() -> ?Attachment

      The file of a FileAttachment annotation, as an :php:class:`Attachment`; ``null`` when it has
      none, and for an annotation of any other type. The file need not be in the document's
      attachment list; this :php:class:`Attachment` is the annotation's own and has no index.

      :rtype: ?Attachment
      :throws PdfiumException: The annotation was removed from its page.
      :throws BusyException: ZTS only: another thread is using PDFium.

   .. php:method:: setAttachment(string $name, string $contents, ?string $description = null) -> Attachment

      Embeds a file in a FileAttachment annotation, in place, replacing the file it had:
      :php:meth:`Document::save` or :php:meth:`Document::toString` writes it out. As for
      :php:meth:`Document::addAttachment`, PDFium sets the size, creation date and checksum.

      .. code-block:: php

         $clip = $page->addAnnotation('FileAttachment', new Pdfium\Rect(72, 700, 92, 720));
         $file = $clip->setAttachment('data.csv', "a,b\n1,2\n", 'Data for the figure');
         echo $clip->attachment()->name();   // data.csv

      An :php:class:`Attachment` fetched earlier from this annotation reads the new file. When
      PDFium cannot store the contents or description, the annotation's previous file is already
      gone, and PDFium has no undo.

      :param string $name: The file name, as UTF-8.
      :param string $contents: The file's bytes.
      :param ?string $description: A description, as UTF-8.
      :rtype: Attachment
      :throws \\ValueError: ``$name`` is empty; ``$name`` or ``$description`` is not valid UTF-8,
         contains a null byte or the escape character, or starts with what a PDF reader would take
         for a byte order mark (see :ref:`accessibility`); or ``$contents`` is longer than
         2147483647 bytes, the most PDFium can store (see :ref:`attachments`).
      :throws PdfiumException: The annotation was removed from its page, or it is not a
         FileAttachment annotation; or PDFium cannot store the contents or description, and the
         annotation's previous file is gone.
      :throws MemoryException: The name or description would not fit under ``memory_limit``.
      :throws BusyException: ZTS only: another thread is using PDFium.

   .. php:method:: remove() -> void

      Removes the annotation from its page, in place, as :php:meth:`Page::removeAnnotation` does
      with its index. The annotations after it move down one index; this object and every other
      one for the same annotation throw from then on. One exception: when a damaged page lists the
      annotation twice, the first entry goes and only this object throws; the others find the entry
      that is left.

      .. code-block:: php

         foreach ($page->annotations() as $a) {
             if ($a->type() === 'Popup') {
                 $a->remove();
             }
         }

      :throws PdfiumException: The annotation was already removed from its page, or PDFium cannot
         remove it.
      :throws BusyException: ZTS only: another thread is using PDFium.


FormField
---------

.. php:class:: FormField

   One widget of a form field, from :php:meth:`Page::formFields` or :php:meth:`Page::formFieldAt`.
   A field with several widgets, such as a radio group, gives one ``FormField`` for each;
   :php:meth:`controlIndex` tells them apart. It keeps its page and document alive and reads the
   field's current state on every call. Every method throws :php:class:`PdfiumException` once its
   widget is removed from the page, by :php:meth:`Page::removeAnnotation` or
   :php:meth:`Annotation::remove`, or its page is flattened, because the field is gone. Removing
   other annotations of the page does not affect it. It follows its page when pages are moved,
   added or deleted, and throws as the :php:class:`Page`'s methods do once the page is deleted or
   its position is unknown; see :ref:`page-editing`.

   .. php:method:: name() -> string

      The field's fully qualified name, as UTF-8, such as ``parent.notes``. Empty when it has none.

      :rtype: string
      :throws PdfiumException: The field is gone.
      :throws MemoryException: The name would not fit under ``memory_limit``.
      :throws BusyException: ZTS only: another thread is using PDFium.

   .. php:method:: alternateName() -> ?string

      The field's tooltip, as UTF-8. ``null`` when it has none.

      :rtype: ?string
      :throws PdfiumException: The field is gone.
      :throws MemoryException: The text would not fit under ``memory_limit``.
      :throws BusyException: ZTS only: another thread is using PDFium.

   .. php:method:: type() -> FormFieldType

      The field's type.

      :rtype: FormFieldType
      :throws PdfiumException: The field is gone.
      :throws BusyException: ZTS only: another thread is using PDFium.

   .. php:method:: value() -> string

      The field's value exactly as PDFium reports it, as UTF-8; empty when there is none. Combo
      boxes, list boxes and buttons give export values; see :ref:`forms`.

      :rtype: string
      :throws PdfiumException: The field is gone.
      :throws MemoryException: The value would not fit under ``memory_limit``.
      :throws BusyException: ZTS only: another thread is using PDFium.

   .. php:method:: exportValue() -> ?string

      What the field's value is when this checkbox or radio button is checked, as UTF-8. ``null``
      for any other type, and when the button gives none.

      :rtype: ?string
      :throws PdfiumException: The field is gone.
      :throws MemoryException: The value would not fit under ``memory_limit``.
      :throws BusyException: ZTS only: another thread is using PDFium.

   .. php:method:: isChecked() -> bool

      Whether this checkbox or radio button is checked. ``false`` for any other type.

      :rtype: bool
      :throws PdfiumException: The field is gone.
      :throws BusyException: ZTS only: another thread is using PDFium.

   .. php:method:: rect() -> Rect

      The widget's rectangle on its page, in points.

      :rtype: Rect
      :throws PdfiumException: The field is gone.
      :throws BusyException: ZTS only: another thread is using PDFium.

   .. php:method:: controlIndex() -> int

      This widget's place among the field's widgets, starting at 0.

      :rtype: int
      :throws PdfiumException: The field is gone, or PDFium cannot read the field's group.
      :throws BusyException: ZTS only: another thread is using PDFium.

   .. php:method:: controlCount() -> int

      The number of widgets the field has.

      :rtype: int
      :throws PdfiumException: The field is gone, or PDFium cannot read the field's group.
      :throws BusyException: ZTS only: another thread is using PDFium.

   .. php:method:: options() -> array

      The options of a combo box or list box, in order, as :php:class:`FormFieldOption` objects.
      An empty array for any other type. PDFium does not give an option's export value, only its
      label; :php:meth:`value` gives the export value of the selected option.

      :rtype: FormFieldOption[]
      :throws PdfiumException: The field is gone.
      :throws MemoryException: The options would not fit under ``memory_limit``.
      :throws BusyException: ZTS only: another thread is using PDFium.

   .. php:method:: flags() -> FormFieldFlags

      The field's flags. A flag that does not apply to the field's type is ``false``.

      :rtype: FormFieldFlags
      :throws PdfiumException: The field is gone.
      :throws BusyException: ZTS only: another thread is using PDFium.

   .. php:method:: setValue(string $value) -> void

      Sets the text of a text field or of a combo box with the ``editable`` flag, as if a user typed
      it, in place; an empty string clears the field. PDFium cuts text to the field's length limit,
      changes read-only fields and does not store characters above U+FFFF correctly; see
      :ref:`forms`.

      :param string $value: The text, as UTF-8.
      :throws \\ValueError: ``$value`` is not valid UTF-8 or contains a null byte.
      :throws PdfiumException: The field is gone, it is not a text field or a combo box with the
         ``editable`` flag, or PDFium refuses to edit it.
      :throws MemoryException: The text would not fit under ``memory_limit``.
      :throws BusyException: ZTS only: another thread is using PDFium.

   .. php:method:: setChecked(bool $checked = true) -> void

      Checks or unchecks a checkbox or radio button, as if a user clicked it, in place. Nothing is
      done when the button is already in the requested state. PDFium does not change a read-only
      button or uncheck a radio button, so those throw; see :ref:`forms`.

      :param bool $checked: The state to leave the button in.
      :throws PdfiumException: The field is gone, the field is not a checkbox or radio button, or
         PDFium did not leave the button in the requested state.
      :throws BusyException: ZTS only: another thread is using PDFium.

   .. php:method:: select(int $option, bool $selected = true) -> void

      Selects or deselects one option of a combo box or list box, as if a user picked it, in place.
      It replaces the selection, except in a list box with ``multiSelect``. PDFium changes read-only
      fields too, but does not deselect a combo box's option, so that throws; see :ref:`forms`.

      :param int $option: The index into :php:meth:`options`.
      :param bool $selected: Whether the option ends up selected.
      :throws \\ValueError: ``$option`` is not an option index of the field.
      :throws PdfiumException: The field is gone, the field is not a combo box or list box, or
         PDFium does not change the selection.
      :throws BusyException: ZTS only: another thread is using PDFium.


FormFieldOption
---------------

.. php:class:: FormFieldOption

   One option of a combo box or list box, from :php:meth:`FormField::options`. Read-only.

   .. php:attr:: label

      ``string``

      The text shown for the option, as UTF-8.

   .. php:attr:: selected

      ``bool``

      Whether the option is selected.


FormFieldFlags
--------------

.. php:class:: FormFieldFlags

   The flags of a form field, from :php:meth:`FormField::flags`. Read-only. Each flag is ``false``
   unless it applies to the field's type.

   .. php:attr:: readOnly

      ``bool``

      The field is read-only.

   .. php:attr:: required

      ``bool``

      The field must have a value before the form is submitted.

   .. php:attr:: noExport

      ``bool``

      The field is left out when the form is submitted.

   .. php:attr:: multiline

      ``bool``

      Text fields only: the text can have several lines.

   .. php:attr:: password

      ``bool``

      Text fields only: the text is hidden.

   .. php:attr:: editable

      ``bool``

      Combo boxes only: the user can type a value that is not an option.

   .. php:attr:: multiSelect

      ``bool``

      List boxes only: several options can be selected.


FormType
--------

.. php:class:: FormType

   An enum, returned by :php:meth:`Document::formType`. XFA forms are told apart but not
   supported; see :ref:`forms`.

   - ``None``: no form.
   - ``AcroForm``: an ordinary form.
   - ``XfaFull``: a full XFA form (the catalog has ``/NeedsRendering true``).
   - ``XfaForeground``: an AcroForm that also carries XFA data (an ``/XFA`` entry).


FormFieldType
-------------

.. php:class:: FormFieldType

   An enum, returned by :php:meth:`FormField::type`, with the cases ``Unknown``, ``PushButton``,
   ``Checkbox``, ``RadioButton``, ``ComboBox``, ``ListBox``, ``Text`` and ``Signature``. A type
   PDFium reports that is not in this list is ``Unknown``.


TextPage
--------

.. php:class:: TextPage

   A page's text as PDFium read it once, returned by :php:meth:`Page::textPage`. It keeps its page
   and document alive. Its methods never throw ``FormatException``, because the text is already
   read. Once its page is removed with :php:meth:`Document::deletePage`, or a failed edit leaves
   the page's position unknown, every method throws ``PdfiumException`` with the same message as
   the :php:class:`Page`'s methods. See :ref:`text-positions`.

   .. php:method:: text() -> string

      The same as :php:meth:`Page::text`, without reading the text again.

      :rtype: string
      :throws MemoryException: The text would not fit under ``memory_limit``.
      :throws PdfiumException: The page was deleted from its document, or its position is unknown after a failed edit.
      :throws BusyException: ZTS only: another thread is using PDFium.

   .. php:method:: charCount() -> int

      The number of characters: the size of :php:meth:`chars`.

      :rtype: int
      :throws PdfiumException: The page was deleted from its document, or its position is unknown after a failed edit.
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
      :throws PdfiumException: The page was deleted from its document, or its position is unknown after a failed edit.
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
      :throws PdfiumException: The page was deleted from its document, or its position is unknown after a failed edit.
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
      :throws PdfiumException: The page was deleted from its document, or its position is unknown after a failed edit.
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
      :throws PdfiumException: The page was deleted from its document, or its position is unknown after a failed edit.
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
      :throws PdfiumException: The page was deleted from its document, or its position is unknown after a failed edit.
      :throws BusyException: ZTS only: another thread is using PDFium.

   .. php:method:: webLinks() -> array

      The URLs and email addresses written in the page's text. PDFium finds them by their shape:
      ``http://``, ``https://`` and ``www.`` addresses, and email addresses, which come back as
      ``mailto:`` URLs. Link annotations, the clickable areas a PDF can define, are not included:
      :php:meth:`Page::links` gives those.

      :rtype: WebLink[]
      :throws MemoryException: The links would not fit under ``memory_limit``.
      :throws PdfiumException: The page was deleted from its document, or its position is unknown after a failed edit.
      :throws BusyException: ZTS only: another thread is using PDFium.


PageIterator
------------

.. php:class:: PageIterator

   Returned by :php:meth:`Document::pages`. Implements ``\Iterator``: keys are page numbers, values
   are :php:class:`Page` objects. Only the current page is kept in memory.

   .. code-block:: php

      foreach ($doc->pages() as $index => $page) { /* ... */ }

   .. php:method:: current() -> Page

      The page at :php:meth:`key`, loaded on the first call and again when an edit has deleted it,
      moved it away from :php:meth:`key` or left its position unknown. A :php:class:`Page` returned
      earlier keeps following its own page.

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
   :ref:`text-positions`. :php:meth:`TextPage::chars`, :php:meth:`TextPage::search`,
   :php:meth:`TextPage::webLinks` and :php:meth:`Annotation::rect` return them, as do
   :php:attr:`Link::rect` and the box getters such as :php:meth:`Page::cropBox`. You create one to
   pass to :php:meth:`TextPage::textIn`, :php:meth:`TextPage::textInEach`, :php:meth:`Page::render`,
   :php:meth:`Page::addAnnotation`, :php:meth:`Annotation::setRect` or a box setter such as
   :php:meth:`Page::setCropBox`. The box setters need ``left`` less than ``right`` and ``bottom``
   less than ``top``; the constructor also accepts them equal.

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

   A colour from :php:attr:`Char::fillColor`, :php:attr:`Char::strokeColor`,
   :php:attr:`Bookmark::color`, :php:meth:`Annotation::color` or
   :php:meth:`Annotation::interiorColor`, as RGB with values from 0 to 255: a final, read-only
   value. PDFium converts other colour spaces, such as CMYK, to RGB.

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


Bookmark
--------

.. php:class:: Bookmark

   One entry of the outline from :php:meth:`Document::bookmarks`: a final, read-only value.

   .. php:attr:: title

      ``string``: the title, as UTF-8.

   .. php:attr:: open

      ``bool``: whether a viewer shows the children at first.

   .. php:attr:: color

      ``?Color``: the colour of the title, with alpha 255; ``null`` when the entry sets none
      (viewers then use black) or sets an invalid one.

   .. php:attr:: italic

      ``bool``: whether the title is shown in italics.

   .. php:attr:: bold

      ``bool``: whether the title is shown in bold.

   .. php:attr:: destination

      ``?Destination``: the page in this document the entry leads to, directly or through a
      ``"GoTo"`` action; ``null`` otherwise. See :ref:`navigation`.

   .. php:attr:: action

      ``?Action``: what the entry does, when it has an action rather than a plain destination;
      ``null`` when it has none.

   .. php:attr:: children

      :php:class:`Bookmark[] <Bookmark>`: the entries under this one, in order.


Link
----

.. php:class:: Link

   One link annotation from :php:meth:`Page::links`, :php:meth:`Page::linkAt` or
   :php:meth:`Annotation::link`: a final, read-only value.

   .. php:attr:: rect

      :php:class:`Rect`: the annotation's rectangle, in points.

   .. php:attr:: quadPoints

      :php:class:`Quad[] <Quad>`: the areas that activate the link, when the annotation gives them,
      such as one per line of a link that wraps; empty otherwise, and then :php:attr:`rect` is the
      area.

   .. php:attr:: destination

      ``?Destination``: the page in this document the link leads to, directly or through a
      ``"GoTo"`` action; ``null`` otherwise. See :ref:`navigation`.

   .. php:attr:: action

      ``?Action``: what the link does, when it has an action rather than a plain destination;
      ``null`` when it has none.


Quad
----

.. php:class:: Quad

   The four corners of a quadrilateral, in points, from :php:attr:`Link::quadPoints` or
   :php:meth:`Annotation::quadPoints`: a final, read-only value. The corners are ``x1, y1`` to
   ``x4, y4``, in the order the PDF gives them.

   .. php:method:: __construct(float $x1, float $y1, float $x2, float $y2, float $x3, float $y3, float $x4, float $y4)

      :throws \\ValueError: A coordinate is not finite.

   .. php:attr:: x1

      ``float``

   .. php:attr:: y1

      ``float``

   .. php:attr:: x2

      ``float``

   .. php:attr:: y2

      ``float``

   .. php:attr:: x3

      ``float``

   .. php:attr:: y3

      ``float``

   .. php:attr:: x4

      ``float``

   .. php:attr:: y4

      ``float``


Point
-----

.. php:class:: Point

   A point, with ``x`` and ``y`` in points and ``y`` growing upwards: a final, read-only value.
   :php:meth:`Annotation::vertices`, :php:meth:`Annotation::line` and
   :php:meth:`Annotation::inkStrokes` return them.

   .. php:method:: __construct(float $x, float $y)

      :throws \\ValueError: A coordinate is not finite.

   .. php:attr:: x

      ``float``

   .. php:attr:: y

      ``float``


Border
------

.. php:class:: Border

   An annotation's border, from :php:meth:`Annotation::border`: a final, read-only value, in points.

   .. php:attr:: horizontalRadius

      ``float``: the horizontal radius of the rounded corners.

   .. php:attr:: verticalRadius

      ``float``: the vertical radius of the rounded corners.

   .. php:attr:: width

      ``float``: the line width.


Destination
-----------

.. php:class:: Destination

   Where a bookmark, link or action leads in this document: a final, read-only value.

   .. php:attr:: pageIndex

      ``int``: the 0-based index of the target page.

   .. php:attr:: view

      ``?string``: how the viewer shows the page: ``"XYZ"`` (a point at a zoom), ``"Fit"`` (the
      whole page), ``"FitH"`` (full width, from a top edge), ``"FitV"`` (full height, from a left
      edge), ``"FitR"`` (a rectangle), or ``"FitB"``, ``"FitBH"``, ``"FitBV"`` (the same, for the
      page's content only); ``null`` when PDFium cannot tell.

   .. php:attr:: params

      ``float[]``: the view's numbers, at most four: none for ``"Fit"``, the top edge for
      ``"FitH"``, the left, bottom, right and top for ``"FitR"``. For ``"XYZ"`` a missing number is
      0 here; use :php:attr:`x`, :php:attr:`y` and :php:attr:`zoom`, which are ``null`` instead.

   .. php:attr:: x

      ``?float``: for ``"XYZ"``, the left edge to show, in points; ``null`` for other views, and
      when the PDF leaves it out, which means keep the current one.

   .. php:attr:: y

      ``?float``: for ``"XYZ"``, the top edge to show, in points; ``null`` as for :php:attr:`x`.

   .. php:attr:: zoom

      ``?float``: for ``"XYZ"``, the zoom factor (1 is 100%); ``null`` as for :php:attr:`x`.


Action
------

.. php:class:: Action

   What a bookmark, link or page event does: a final, read-only value.

   .. php:attr:: type

      ``string``: ``"GoTo"`` (a place in this document), ``"GoToR"`` (a place in another PDF file),
      ``"URI"`` (open a web address), ``"Launch"`` (open a file or run an application),
      ``"GoToE"`` (a place in an embedded PDF), or ``"Unsupported"`` for every type PDFium does not
      read, such as JavaScript.

   .. php:attr:: uri

      ``?string``: for ``"URI"``, the URI as written in the PDF, with the document's base URI in
      front when it is relative and the document sets one. It may not be UTF-8; see
      :ref:`navigation`. ``null`` when the action names none, and for every other type.

   .. php:attr:: filePath

      ``?string``: the file, as UTF-8, for ``"GoToR"`` and ``"Launch"``, ``null`` when the action
      names none; for ``"GoToE"``, the PDF file that holds the embedded one, ``null`` when that is
      this document. ``null`` for every other type.

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

   Also thrown on its own when PDFium cannot copy pages, edit pages, write a document, encode a
   bitmap or flatten a page, or refuses a change to an annotation; when :php:meth:`Document::nUp`
   cannot lay out the pages; when a :php:class:`FormField` cannot be read or changed; by a
   :php:class:`Page`, :php:class:`TextPage`, :php:class:`Annotation` or :php:class:`FormField` whose
   page was deleted or whose position is unknown after a failed edit; by an :php:class:`Annotation`
   once its annotation is removed from its page; by a stale :php:class:`Attachment`; and by
   :php:meth:`Attachment::index` for an annotation's file.

.. php:exception:: FileException

   The file cannot be read or written, the path is too long, or ``open_basedir`` blocks it.

   When opening or saving fails, the PHP warning that says why (for example ``No such file or
   directory``) is attached as the previous exception (``$e->getPrevious()``). No warning is shown
   or logged. Through stream wrappers the call behaves like ``@fopen()``: your error handler, if you
   set one, still sees the wrapper's warnings, and fatal errors are shown as usual.

.. php:exception:: FormatException

   The data is not a PDF, or it is too damaged to read. Also thrown when a page or its text cannot
   be loaded, or when an entry of a page's annotation list is not an annotation.

.. php:exception:: PasswordException

   A password is needed, or the password is wrong.

.. php:exception:: UnsupportedException

   The document uses an encryption PDFium does not support, or :php:meth:`Document::extractPages`,
   :php:meth:`Document::insertPages`, :php:meth:`Document::merge` or :php:meth:`Document::nUp` was
   given an encrypted document without ``removeSecurity: true``, or :php:meth:`Page::addAnnotation`
   was given a type PDFium cannot create.

.. php:exception:: MemoryException

   The operation would go over ``memory_limit``. Thrown instead of PHP's fatal out-of-memory error.
   See :ref:`memory-limit`.

   Also thrown when PDFium cannot create a document; raising ``memory_limit`` does not help then.
   Thrown this way by :php:meth:`Document::create`, :php:meth:`Document::extractPages`,
   :php:meth:`Document::merge`, and :php:meth:`Document::insertPages` when it copies pages within
   one document.

.. php:exception:: BusyException

   ZTS PHP only. Another thread is using PDFium, and nothing was done. It is safe to retry, except
   that :php:meth:`Document::fromFile` has already read the file. See :ref:`threads`.

   Every :php:class:`Document`, :php:class:`Page`, :php:class:`TextPage`, :php:class:`PageIterator`,
   :php:class:`Attachment`, :php:class:`Annotation` and :php:class:`FormField` method can throw it,
   including ``current()`` and ``valid()`` in the middle of a ``foreach``, except
   :php:meth:`Document::pages`, :php:meth:`Document::getIterator`, :php:meth:`Page::index`,
   :php:meth:`PageIterator::key`, :php:meth:`PageIterator::next` and
   :php:meth:`PageIterator::rewind`. :php:class:`Bitmap` and the value classes never throw it.

The exception class says what went wrong; ``$e->getCode()`` is always 0.
