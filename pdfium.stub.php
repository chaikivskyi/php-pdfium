<?php

/**
 * @generate-class-entries
 * @generate-legacy-arginfo 80200
 */

namespace Pdfium;

class PdfiumException extends \RuntimeException {}

class FileException extends PdfiumException {}

class FormatException extends PdfiumException {}

class PasswordException extends PdfiumException {}

class UnsupportedException extends PdfiumException {}

class MemoryException extends PdfiumException {}

class BusyException extends PdfiumException {}

/**
 * A PDF document. It can be edited: pages deleted, added, copied in, moved, rotated and given new
 * boxes. The edits are written out only by save() and toString().
 *
 * @strict-properties
 * @not-serializable
 */
final class Document implements \Countable, \IteratorAggregate
{
    private function __construct() {}

    /**
     * By default reads the whole file through PHP streams, so stream wrappers and open_basedir
     * apply. The document keeps the bytes in PHP memory and does not touch the file again.
     *
     * With $lazy true, the document keeps the file open and PDFium reads the parts it needs, when
     * it needs them. The file must not change while the document exists. Only local files can be
     * opened this way.
     *
     * @param bool $lazy read the file on demand instead of loading it whole
     * @throws \ValueError when $path is empty
     * @throws FileException when the path cannot be read, is a local path of MAXPATHLEN or longer, or is outside open_basedir;
     *         the PHP warning saying why, if there is one, is its previous exception
     * @throws MemoryException when the file would not fit under memory_limit
     * @throws FormatException when the file is not a PDF or is corrupted
     * @throws PasswordException when the password is missing or wrong
     * @throws UnsupportedException when the document uses a security handler PDFium does not support,
     *         or when $lazy is true and the path goes through a stream wrapper
     * @throws BusyException on ZTS PHP when another thread is using PDFium
     */
    public static function fromFile(string $path, ?string $password = null, bool $lazy = false): Document {}

    /**
     * A new document with no pages, and no metadata beyond the creator and creation date PDFium
     * sets.
     *
     * @throws MemoryException when PDFium cannot create a document
     * @throws BusyException on ZTS PHP when another thread is using PDFium
     */
    public static function create(): Document {}

    /**
     * The document keeps $bytes alive for its whole life.
     *
     * @throws \ValueError when $bytes is empty
     * @throws FormatException when the bytes are not a PDF or are corrupted
     * @throws PasswordException when the password is missing or wrong
     * @throws UnsupportedException when the document uses a security handler PDFium does not support
     * @throws BusyException on ZTS PHP when another thread is using PDFium
     */
    public static function fromString(string $bytes, ?string $password = null): Document {}

    /**
     * The number of pages.
     *
     * @throws BusyException on ZTS PHP when another thread is using PDFium
     */
    public function pageCount(): int {}

    /**
     * @implementation-alias Pdfium\Document::pageCount
     *
     * @throws BusyException on ZTS PHP when another thread is using PDFium
     */
    public function count(): int {}

    /**
     * The document information entries; empty or missing ones are null.
     *
     * @throws MemoryException when an entry would not fit under memory_limit
     * @throws BusyException on ZTS PHP when another thread is using PDFium
     */
    public function metadata(): Metadata {}

    /**
     * The document's permission flags; they are reported, never enforced.
     *
     * @throws BusyException on ZTS PHP when another thread is using PDFium
     */
    public function permissions(): Permissions {}

    /**
     * Whether the document says it is a tagged PDF (its /MarkInfo has /Marked set). It does not
     * check that the document has a usable structure tree.
     *
     * @throws BusyException on ZTS PHP when another thread is using PDFium
     */
    public function isTagged(): bool {}

    /**
     * The document's language (its /Lang, for example "en-US") as PDFium reads it, as UTF-8; null
     * when there is none, it is empty, or PDFium cannot read it as text.
     *
     * @throws MemoryException when it would not fit under memory_limit
     * @throws BusyException on ZTS PHP when another thread is using PDFium
     */
    public function language(): ?string {}

    /**
     * Sets the document's language (its /Lang), for example "en-US"; it is not checked to be a
     * language tag. An empty string means the language is unknown, and language() then returns
     * null. Changes the loaded document; save() or toString() writes it out.
     *
     * @throws \ValueError when $language is not valid UTF-8, contains a null byte or the escape
     *         character, or starts with what a PDF reader would take for a byte order mark
     * @throws MemoryException when $language would not fit under memory_limit
     * @throws PdfiumException when PDFium cannot set it
     * @throws BusyException on ZTS PHP when another thread is using PDFium
     */
    public function setLanguage(string $language): void {}

    /**
     * The first of the file's two identifiers (its /ID), set when the file was created, as raw
     * bytes; null when there is none or it is empty.
     *
     * @throws MemoryException when the identifier would not fit under memory_limit
     * @throws BusyException on ZTS PHP when another thread is using PDFium
     */
    public function permanentId(): ?string {}

    /**
     * The second of the file's two identifiers (its /ID), changed each time the file is saved, as
     * raw bytes; null when there is none or it is empty.
     *
     * @throws MemoryException when the identifier would not fit under memory_limit
     * @throws BusyException on ZTS PHP when another thread is using PDFium
     */
    public function changingId(): ?string {}

    /**
     * The top level of the outline, in order, each with its children; empty when there is none. A
     * broken outline that loops or nests deeper than 100 levels is cut short.
     *
     * @return Bookmark[]
     * @throws MemoryException when the outline would not fit under memory_limit
     * @throws BusyException on ZTS PHP when another thread is using PDFium
     */
    public function bookmarks(): array {}

    /**
     * The scripts in the document's /JavaScript name tree, in the order PDFium lists them; empty
     * when there are none. The document's open action and document actions, and scripts on pages,
     * links, annotations and form fields, are not included, so an empty result does not mean the
     * file has no JavaScript. Nothing is run. A crafted script list can keep this call busy for
     * minutes.
     *
     * @return JavaScript[]
     * @throws MemoryException when the scripts would not fit under memory_limit
     * @throws BusyException on ZTS PHP when another thread is using PDFium
     */
    public function javaScripts(): array {}

    /**
     * @throws BusyException on ZTS PHP when another thread is using PDFium
     */
    public function attachmentCount(): int {}

    /**
     * Every embedded file, keyed by the index attachment() and deleteAttachment() take. An entry
     * PDFium cannot read is left out, so keys can skip numbers.
     *
     * @return array<int, Attachment>
     * @throws MemoryException when the list would not fit under memory_limit
     * @throws BusyException on ZTS PHP when another thread is using PDFium
     */
    public function attachments(): array {}

    /**
     * @throws \ValueError when $index is outside 0..attachmentCount()-1
     * @throws FormatException when PDFium cannot read the entry
     * @throws BusyException on ZTS PHP when another thread is using PDFium
     */
    public function attachment(int $index): Attachment {}

    /**
     * Embeds a file in this document, in place: save() or toString() writes it out. PDFium sets the
     * size, creation date and checksum; the MIME type, relationship and modification date stay empty.
     * The new file can take any index, and every Attachment fetched from the document's list before
     * this call is now stale; an annotation's file is not.
     *
     * @throws \ValueError when $name is empty; $name or $description is not valid UTF-8, contains a
     *         null byte or the escape character, or starts with what a PDF reader would take for a
     *         byte order mark; or $contents is longer than 2147483647 bytes, the most PDFium can store
     * @throws PdfiumException when PDFium refuses: the name may already be used, or the attachment list is damaged;
     *         or when PDFium cannot store the contents or description (the message says if the entry stays)
     * @throws MemoryException when the name or description would not fit under memory_limit
     * @throws BusyException on ZTS PHP when another thread is using PDFium
     */
    public function addAttachment(string $name, string $contents, ?string $description = null): Attachment {}

    /**
     * Removes an embedded file from this document, in place. A saved PDF leaves it out, unless it was
     * added since loading (it then stays, unused) or something else still points to it. Attachments
     * fetched earlier from the document's list are now stale, but not an annotation's file. To delete
     * several, delete from the highest index down.
     *
     * @throws \ValueError when $index is outside 0..attachmentCount()-1
     * @throws PdfiumException when PDFium cannot delete the entry
     * @throws BusyException on ZTS PHP when another thread is using PDFium
     */
    public function deleteAttachment(int $index): void {}

    /**
     * How many signature fields are at the top of the form's field list, signed or not.
     *
     * @throws BusyException on ZTS PHP when another thread is using PDFium
     */
    public function signatureCount(): int {}

    /**
     * Every signature field at the top of the form's field list, signed or not, in order and with
     * repeats; empty when there are none.
     *
     * @return Signature[]
     * @throws MemoryException when the list would not fit under memory_limit
     * @throws BusyException on ZTS PHP when another thread is using PDFium
     */
    public function signatures(): array {}

    /**
     * Which kind of form the document has, from its catalog. Unlike the other form methods, it leaves
     * the document unchanged. XFA forms are told apart but not supported.
     *
     * @throws BusyException on ZTS PHP when another thread is using PDFium
     */
    public function formType(): FormType {}

    /**
     * @throws \ValueError when $index is outside 0..pageCount()-1
     * @throws FormatException when PDFium cannot load the page
     * @throws BusyException on ZTS PHP when another thread is using PDFium
     */
    public function page(int $index): Page {}

    /**
     * Removes the page at $index from this document. The change is written by save() and toString().
     * Pages held from before keep working and report their new index(); a Page or TextPage of the
     * deleted page throws from every method. Deleting the only page leaves a document with no
     * pages. Deleting inside foreach can make the loop skip a page.
     *
     * @throws \ValueError when $index is outside 0..pageCount()-1
     * @throws PdfiumException when PDFium does not remove the page; held pages may then throw, see
     *         "Failed edits" in the behaviour documentation
     * @throws BusyException on ZTS PHP when another thread is using PDFium
     */
    public function deletePage(int $index): void {}

    /**
     * Adds a blank page of $width by $height points and returns it. $index is where it goes, between 0
     * and pageCount(); null means after the last page. Pages held from before report their new index().
     * The page is unrotated and exactly this size, whatever rotation or crop box its place in the page tree
     * would give it.
     * Adding inside foreach can make the loop see a page again. The change is written by save() and
     * toString().
     *
     * @throws \ValueError when a size is not finite or not above 0 once rounded to a float, or $index
     *         is outside 0..pageCount()
     * @throws PdfiumException when PDFium cannot add the page; held pages may then throw, see "Failed
     *         edits" in the behaviour documentation
     * @throws BusyException on ZTS PHP when another thread is using PDFium
     */
    public function addPage(float $width, float $height, ?int $index = null): Page {}

    /**
     * Copies pages of $source into this document at $index (between 0 and pageCount(); null means
     * after the last page). $indexes are int page indexes of $source in the order wanted, duplicates
     * kept; null means every page. $source may be this document; the copied pages are then held twice
     * in memory during the call. Pages held from before report their new index(). Bookmarks, attachments
     * and viewer preferences are not copied, and copied form fields can no longer be filled in. A copy
     * can take its rotation and crop box from where it lands in the page tree; see "Inherited page
     * attributes" in the behaviour documentation. It sets the producer to "PDFium" if the file has
     * document information (what metadata() reads), as most files do. Inserting inside foreach can
     * make the loop see a page again. The change is written by save() and toString().
     *
     * @param null|int[] $indexes
     * @param bool $removeSecurity needed only when $source is another, encrypted document: true lets its
     *        pages go without the source's password; they take this document's encryption, if any
     * @throws \ValueError when $source has no pages, $indexes is empty or holds an index outside
     *         $source's pages, or $index is outside 0..pageCount()
     * @throws \TypeError when an element of $indexes is not an int
     * @throws UnsupportedException when $source is another, encrypted document and $removeSecurity is false
     * @throws MemoryException when the indexes would not fit under memory_limit, or PDFium cannot make the
     *         temporary copy that copying within this document needs
     * @throws PdfiumException when PDFium cannot copy the pages; held pages may then throw, see
     *         "Failed edits" in the behaviour documentation
     * @throws BusyException on ZTS PHP when another thread is using PDFium
     */
    public function insertPages(Document $source, ?array $indexes = null, ?int $index = null, bool $removeSecurity = false): void {}

    /**
     * Moves the pages at $indexes so that they sit at $to, $to+1, ... in the order listed. Pages held
     * from before report their new index(). A moved page can take its rotation, boxes, fonts and images
     * from its new place in the page tree; see "Inherited page attributes" in the behaviour
     * documentation. Moving pages inside foreach can make the loop skip or repeat a page. The change
     * is written by save() and toString().
     *
     * @param int[] $indexes
     * @throws \ValueError when $indexes is empty, holds an index twice or one outside
     *         0..pageCount()-1, or $to is outside 0..pageCount()-count($indexes)
     * @throws \TypeError when an element of $indexes is not an int
     * @throws MemoryException when the indexes would not fit under memory_limit
     * @throws PdfiumException when PDFium fails, as it can on a damaged page tree; every held page then
     *         throws. See "Damaged page trees" and "Failed edits" in the behaviour documentation
     * @throws BusyException on ZTS PHP when another thread is using PDFium
     */
    public function movePages(array $indexes, int $to): void {}

    /**
     * Loads each page on demand and holds only the current one; rewind() restarts at 0.
     */
    public function pages(): PageIterator {}

    /**
     * The same as pages(), so foreach ($doc as $index => $page) works.
     *
     * @implementation-alias Pdfium\Document::pages
     */
    public function getIterator(): PageIterator {}

    /**
     * A new document with the pages at these int indexes, in this order, duplicates kept, and this
     * document's viewer preferences. It is unencrypted, has no bookmarks or metadata beyond the
     * creator, producer and creation date PDFium sets, and its form fields can no longer be filled in.
     * It does not keep this document alive.
     *
     * @param int[] $indexes
     * @param bool $removeSecurity must be true to extract from an encrypted document, because
     *        PDFium cannot encrypt the result
     * @throws \ValueError when $indexes is empty or an index is outside 0..pageCount()-1
     * @throws \TypeError when an element is not an int
     * @throws UnsupportedException when this document is encrypted and $removeSecurity is false
     * @throws FormatException when this document was opened lazily and its file could not be read in full
     * @throws MemoryException when the indexes would not fit under memory_limit, or PDFium cannot create
     *         the new document
     * @throws PdfiumException when PDFium cannot import the pages
     * @throws BusyException on ZTS PHP when another thread is using PDFium
     */
    public function extractPages(array $indexes, bool $removeSecurity = false): Document {}

    /**
     * A new document with this document's pages scaled onto sheets of $width by $height points,
     * $columns across and $rows down, in page order left to right, then top to bottom. Each page is
     * scaled up or down to fit its cell, keeping its proportions, and centred in it. The sheets have
     * no links, form fields or other annotations. With one column and one row, PDFium instead copies
     * the pages whole, annotations included, and ignores $width and $height. The new document is
     * unencrypted, has no viewer preferences, bookmarks, attachments or metadata beyond the creator, producer and
     * creation date PDFium sets, and does not keep this document alive.
     *
     * @param bool $removeSecurity must be true for an encrypted document, because PDFium cannot
     *        encrypt the result
     * @throws \ValueError when a size is not finite or not above 0 once rounded to a float, or a count
     *         is below 1
     * @throws UnsupportedException when this document is encrypted and $removeSecurity is false
     * @throws PdfiumException when this document has no pages, or $columns and $rows are too large to
     *         lay out
     * @throws BusyException on ZTS PHP when another thread is using PDFium
     */
    public function nUp(float $width, float $height, int $columns, int $rows, bool $removeSecurity = false): Document {}

    /**
     * A new document with every page of each Document in turn, duplicates kept, and the first one's
     * viewer preferences. It is unencrypted, has no bookmarks or metadata beyond the creator, producer
     * and creation date PDFium sets, and its form fields can no longer be filled in. It does not keep
     * the given documents alive.
     *
     * @param Document[] $documents
     * @param bool $removeSecurity must be true to merge an encrypted document, because PDFium cannot
     *        encrypt the result
     * @throws \ValueError when $documents is empty
     * @throws \TypeError when an element is not a Document
     * @throws UnsupportedException when a document is encrypted and $removeSecurity is false
     * @throws FormatException when a document was opened lazily and its file could not be read in full
     * @throws PdfiumException when PDFium cannot import the pages
     * @throws BusyException on ZTS PHP when another thread is using PDFium
     */
    public static function merge(array $documents, bool $removeSecurity = false): Document {}

    /**
     * Writes the bytes toString() returns through PHP streams, so open_basedir and stream
     * wrappers apply. A new file at a plain local path left partly written by a failed write is
     * removed; an existing one is replaced by renaming a complete copy over it where possible.
     *
     * @param bool $incremental true keeps the original file from its %PDF header on and appends
     *        the changes, with copies of the objects PDFium has read. PDFium writes a full copy
     *        instead when $removeSecurity is true, or when the document came from extractPages()
     *        or merge() and has no original bytes.
     * @param int|null $version the PDF version for the header, from 10 for 1.0 to 17 for 1.7;
     *        PDFium ignores other values. null lets PDFium choose. An incremental save keeps the
     *        original header unless it falls back to a full copy.
     * @throws \ValueError when $path is empty or $version does not fit a 32-bit integer
     * @throws FileException when $path cannot be written, or could only be overwritten in place while a
     *         lazily opened document reads it; the PHP warning saying why is its previous exception
     * @throws MemoryException when the serialised document would not fit under memory_limit
     * @throws FormatException when the document was opened lazily and its file could not be read in full
     * @throws PdfiumException when PDFium cannot serialise the document
     * @throws BusyException on ZTS PHP when another thread is using PDFium
     */
    public function save(string $path, bool $removeSecurity = false, bool $incremental = false, ?int $version = null): void {}

    /**
     * The document as a PDF file: a full copy unless $incremental is true. Encryption is kept
     * unless $removeSecurity is true.
     *
     * @param bool $incremental true keeps the original file from its %PDF header on and appends
     *        the changes, with copies of the objects PDFium has read. PDFium writes a full copy
     *        instead when $removeSecurity is true, or when the document came from extractPages()
     *        or merge() and has no original bytes.
     * @param int|null $version the PDF version for the header, from 10 for 1.0 to 17 for 1.7;
     *        PDFium ignores other values. null lets PDFium choose. An incremental save keeps the
     *        original header unless it falls back to a full copy.
     * @throws \ValueError when $version does not fit a 32-bit integer
     * @throws MemoryException when the serialised document would not fit under memory_limit
     * @throws FormatException when the document was opened lazily and its file could not be read in full
     * @throws PdfiumException when PDFium cannot serialise the document
     * @throws BusyException on ZTS PHP when another thread is using PDFium
     */
    public function toString(bool $removeSecurity = false, bool $incremental = false, ?int $version = null): string {}
}

/**
 * The document information dictionary, as UTF-8. Dates stay in raw PDF form ("D:...").
 */
final readonly class Metadata
{
    public ?string $title;
    public ?string $author;
    public ?string $subject;
    public ?string $keywords;
    public ?string $creator;
    public ?string $producer;
    public ?string $creationDate;
    public ?string $modDate;

    private function __construct() {}
}

/**
 * An embedded file's details as UTF-8 strings; null when missing or empty.
 */
final readonly class AttachmentMetadata
{
    public ?string $description;
    /**
     * The MIME type, such as "application/pdf".
     */
    public ?string $subtype;
    /**
     * How the file relates to the document: "Source", "Data", "Alternative", "Supplement",
     * "Unspecified" or another name.
     */
    public ?string $afRelationship;
    /**
     * As stored in the PDF, such as "D:20261031120000Z", like the Metadata dates.
     */
    public ?string $creationDate;
    public ?string $modDate;
    /**
     * The MD5 the PDF stores for the file, as 32 lowercase hex digits; null when missing or not stored
     * as a hex string. It is not checked against contents().
     */
    public ?string $checksum;

    private function __construct() {}
}

/**
 * A signature field's details. A value that is missing or empty comes back as null (byteRange as
 * an empty array), so an unsigned field is all nulls. The signer's name, location and contact
 * details and the signature handler (/Filter) are not available.
 */
final readonly class Signature
{
    /**
     * The signature's bytes as stored, usually a DER-encoded PKCS#7 or PKCS#1 object, with any
     * padding the signer left; null when missing or empty.
     */
    public ?string $contents;
    /**
     * The signed parts of the file as [offset, length] pairs, counted in the file as it was
     * opened, not in save() or toString() output. Unchecked; a number that does not fit in 32 bits
     * comes back wrong.
     *
     * @var list<array{int, int}>
     */
    public array $byteRange;
    /**
     * The signature's format, such as "adbe.pkcs7.detached" or "ETSI.CAdES.detached"; raw bytes,
     * not necessarily valid UTF-8.
     */
    public ?string $subFilter;
    /**
     * The signer's stated reason, as UTF-8.
     */
    public ?string $reason;
    /**
     * The signing time the file claims, such as "D:20261118120000+02'00'"; raw bytes, not
     * necessarily valid UTF-8.
     */
    public ?string $time;
    /**
     * The certification level the signature claims: 1 no changes, 2 form filling and signing,
     * 3 also annotations; null for an ordinary signature.
     */
    public ?int $docMdpPermission;

    private function __construct() {}
}

/**
 * The document's permission flags. They are reported, never enforced.
 */
final readonly class Permissions
{
    public bool $print;
    public bool $modify;
    public bool $copy;
    public bool $annotate;
    public bool $fillForms;
    public bool $extractForAccessibility;
    public bool $assemble;
    public bool $printHighQuality;

    private function __construct() {}
}

/**
 * One option of a combo box or list box. PDFium does not give the option's export value.
 */
final readonly class FormFieldOption
{
    public string $label;
    public bool $selected;

    private function __construct() {}
}

/**
 * The flags of a form field that apply to its type; the others are false.
 */
final readonly class FormFieldFlags
{
    public bool $readOnly;
    public bool $required;
    public bool $noExport;
    /**
     * Text fields only.
     */
    public bool $multiline;
    /**
     * Text fields only.
     */
    public bool $password;
    /**
     * Combo boxes only: the user can type a value that is not an option.
     */
    public bool $editable;
    /**
     * List boxes only.
     */
    public bool $multiSelect;

    private function __construct() {}
}

/**
 * A rectangle in PDF points, with y growing upwards.
 */
final readonly class Rect
{
    public float $left;
    public float $bottom;
    public float $right;
    public float $top;

    /**
     * @throws \ValueError when a side is not finite, $left > $right or $bottom > $top
     */
    public function __construct(float $left, float $bottom, float $right, float $top) {}
}

/**
 * An RGB colour with alpha, each from 0 to 255; alpha 255 is opaque.
 */
final readonly class Color
{
    public int $red;
    public int $green;
    public int $blue;
    public int $alpha;

    private function __construct() {}
}

/**
 * A PDF transformation matrix [a b c d e f]: x' = a*x + c*y + e, y' = b*x + d*y + f.
 */
final readonly class Matrix
{
    public float $a;
    public float $b;
    public float $c;
    public float $d;
    public float $e;
    public float $f;

    private function __construct() {}
}

/**
 * One character of a page's text, as TextPage::chars() returns it.
 */
final readonly class Char
{
    /**
     * The character as UTF-8; empty when PDFium cannot map it to Unicode, and for the second half of
     * a character outside the Basic Multilingual Plane, which PDFium counts as two.
     */
    public string $text;
    /**
     * The tight bounds of the glyph; zero-sized for a space.
     */
    public Rect $box;
    /**
     * The bounds of the glyph's whole cell, from the font's ascent to its descent.
     */
    public Rect $looseBox;
    /**
     * The x coordinate of the glyph origin.
     */
    public float $x;
    /**
     * The y coordinate of the glyph origin, on the baseline.
     */
    public float $y;
    public float $fontSize;
    public ?string $fontName;
    public ?int $fontWeight;
    /**
     * The PDF font descriptor flags (PDF 1.7, 9.8.2); 0 when unknown.
     */
    public int $fontFlags;
    /**
     * The colour the glyph is filled with; null when PDFium cannot tell.
     */
    public ?Color $fillColor;
    /**
     * The colour the glyph is outlined with; null when PDFium cannot tell.
     */
    public ?Color $strokeColor;
    /**
     * Counter-clockwise rotation in degrees, from 0 up to 360, rounded to four decimal places.
     */
    public float $angle;
    /**
     * The matrix of the text object the character is in: its text matrix and the page transformation,
     * without the font size or the character's offset along the line; null when PDFium cannot tell.
     */
    public ?Matrix $matrix;
    /**
     * Whether PDFium added the character, such as a space between words or a line break.
     */
    public bool $generated;
    /**
     * Whether this is a hyphen that splits a word across two lines; PDFium joins the word and its
     * text is "-".
     */
    public bool $hyphen;
    /**
     * Whether the font maps this character to Unicode wrongly.
     */
    public bool $unicodeMapError;

    private function __construct() {}
}

/**
 * One match of TextPage::search().
 */
final readonly class TextMatch
{
    /**
     * The index in TextPage::chars() of the first character.
     */
    public int $index;
    /**
     * How many character indexes it covers.
     */
    public int $length;
    public string $text;
    /**
     * @var Rect[] one per line the match covers
     */
    public array $rects;

    private function __construct() {}
}

/**
 * A URL written in a page's text, as TextPage::webLinks() returns it.
 */
final readonly class WebLink
{
    public string $url;
    /**
     * The index in TextPage::chars() of the first character.
     */
    public int $index;
    /**
     * How many character indexes it covers.
     */
    public int $length;
    /**
     * @var Rect[] one per line the URL covers
     */
    public array $rects;

    private function __construct() {}
}

/**
 * One entry of the document outline, as Document::bookmarks() returns it.
 */
final readonly class Bookmark
{
    /**
     * The title as UTF-8.
     */
    public string $title;
    /**
     * Whether its children are shown by default.
     */
    public bool $open;
    /**
     * The colour of the title; null when there is none (viewers then use black) or it is invalid.
     */
    public ?Color $color;
    public bool $italic;
    public bool $bold;
    /**
     * Where the entry leads in this document; null when it leads elsewhere or nowhere.
     */
    public ?Destination $destination;
    /**
     * What the entry does, when it has an action rather than a plain destination; null when it has
     * none.
     */
    public ?Action $action;
    /**
     * @var Bookmark[]
     */
    public array $children;

    private function __construct() {}
}

/**
 * A link annotation on a page, as Page::links(), linkAt() and Annotation::link() return it.
 * Coordinates are in points, as TextPage gives them.
 */
final readonly class Link
{
    /**
     * The annotation's rectangle.
     */
    public Rect $rect;
    /**
     * @var Quad[] the areas that activate the link; empty when the annotation has none
     */
    public array $quadPoints;
    /**
     * Where the link leads in this document; null when it leads elsewhere or nowhere.
     */
    public ?Destination $destination;
    /**
     * What the link does, when it has an action rather than a plain destination; null when it has
     * none.
     */
    public ?Action $action;

    private function __construct() {}
}

/**
 * The four corners of a quadrilateral, in points.
 */
final readonly class Quad
{
    public float $x1;
    public float $y1;
    public float $x2;
    public float $y2;
    public float $x3;
    public float $y3;
    public float $x4;
    public float $y4;

    /**
     * @throws \ValueError when a coordinate is not finite
     */
    public function __construct(float $x1, float $y1, float $x2, float $y2, float $x3, float $y3, float $x4, float $y4) {}
}

/**
 * A point, with x and y in points and y growing upwards.
 */
final readonly class Point
{
    public float $x;
    public float $y;

    /**
     * @throws \ValueError when a coordinate is not finite
     */
    public function __construct(float $x, float $y) {}
}

/**
 * An annotation's border, in points: the corner radii and the line width.
 */
final readonly class Border
{
    public float $horizontalRadius;
    public float $verticalRadius;
    public float $width;

    private function __construct() {}
}

/**
 * Where a bookmark, link or action leads in this document.
 */
final readonly class Destination
{
    /**
     * The 0-based index of the target page.
     */
    public int $pageIndex;
    /**
     * "XYZ", "Fit", "FitH", "FitV", "FitR", "FitB", "FitBH" or "FitBV"; null when PDFium cannot
     * tell.
     */
    public ?string $view;
    /**
     * @var float[] the view's numbers, at most 4; 0 for an absent XYZ value
     */
    public array $params;
    /**
     * The left edge to show, in points, for an XYZ view; null when absent or for another view.
     */
    public ?float $x;
    /**
     * The top edge to show, in points, for an XYZ view; null when absent or for another view.
     */
    public ?float $y;
    /**
     * The zoom factor, for an XYZ view; null when absent or for another view.
     */
    public ?float $zoom;

    private function __construct() {}
}

/**
 * What a bookmark, link or page event does.
 */
final readonly class Action
{
    /**
     * "GoTo", "GoToR", "URI", "Launch", "GoToE", or "Unsupported" for every type PDFium does not
     * read.
     */
    public string $type;
    /**
     * The URI, for a URI action: the bytes as written, which may not be UTF-8, with the document's
     * base URI in front when it is relative and the document sets one; null when the action names
     * none, and for every other type.
     */
    public ?string $uri;
    /**
     * The file, as UTF-8, for a GoToR or Launch action, and for a GoToE action the PDF file that holds
     * the embedded one; null when the action names none, and for every other type.
     */
    public ?string $filePath;
    /**
     * Where a GoTo action leads; null for every other type.
     */
    public ?Destination $destination;

    private function __construct() {}
}

/**
 * One script, as Document::javaScripts() returns it.
 */
final readonly class JavaScript
{
    /**
     * The name the document gives the script, as UTF-8.
     */
    public string $name;
    /**
     * The source, as UTF-8.
     */
    public string $script;

    private function __construct() {}
}

/**
 * One page of a Document. It follows its page when pages are deleted, added, copied in or moved,
 * and index() reports where the page is now. Once its page is deleted, or a failed edit leaves its
 * position unknown, every method throws PdfiumException saying so; after a failed edit, load the
 * document again.
 *
 * @strict-properties
 * @not-serializable
 */
final class Page
{
    private function __construct() {}

    /**
     * The page's 0-based position in its document.
     *
     * @throws PdfiumException when the page was deleted from its document, or its position is unknown after a failed edit
     */
    public function index(): int {}

    /**
     * Width in points of the area where the media box and crop box overlap, after rotation, as the
     * shortest decimal PDFium's float holds (595.28).
     *
     * @throws PdfiumException when the page was deleted from its document, or its position is unknown after a failed edit
     * @throws BusyException on ZTS PHP when another thread is using PDFium
     */
    public function width(): float {}

    /**
     * Height in points of the area where the media box and crop box overlap, after rotation, as the
     * shortest decimal PDFium's float holds (841.89).
     *
     * @throws PdfiumException when the page was deleted from its document, or its position is unknown after a failed edit
     * @throws BusyException on ZTS PHP when another thread is using PDFium
     */
    public function height(): float {}

    /**
     * Page rotation in degrees: 0, 90, 180 or 270, including a rotation the page takes from its page
     * tree.
     *
     * @throws PdfiumException when the page was deleted from its document, or its position is unknown after a failed edit
     * @throws BusyException on ZTS PHP when another thread is using PDFium
     */
    public function rotation(): int {}

    /**
     * Sets the page's rotation to $degrees clockwise, absolute and not added to the current one:
     * 0, 90, 180 or 270. width() and height() follow, also on other Page objects at the same
     * index. The change is written by save() and toString().
     *
     * @throws \ValueError when $degrees is not 0, 90, 180 or 270
     * @throws PdfiumException when the page was deleted from its document, or its position is unknown after a failed edit
     * @throws BusyException on ZTS PHP when another thread is using PDFium
     */
    public function setRotation(int $degrees): void {}

    /**
     * The page's own media box, the paper size, in points, with left <= right and bottom <= top.
     * Returns null when the page has none of its own, also when it takes one from its page tree; see
     * "Inherited page attributes" in the behaviour documentation.
     *
     * @throws PdfiumException when the page was deleted from its document, or its position is unknown after a failed edit
     * @throws BusyException on ZTS PHP when another thread is using PDFium
     */
    public function mediaBox(): ?Rect {}

    /**
     * Writes the page's own media box as given; other Page objects at the same index report it too.
     * width() and height() follow; a media box that does not overlap the crop box leaves them 0.
     * The change is written by save() and toString().
     *
     * @throws \ValueError when a side is unset or not finite, or left is not less than right or bottom
     *         not less than top once rounded to floats
     * @throws PdfiumException when the page was deleted from its document, or its position is unknown after a failed edit
     * @throws BusyException on ZTS PHP when another thread is using PDFium
     */
    public function setMediaBox(Rect $box): void {}

    /**
     * The page's own crop box, the part of the page shown and printed, in points, with left <= right
     * and bottom <= top. Returns null when the page has none of its own, also when it takes one from
     * its page tree; see "Inherited page attributes" in the behaviour documentation.
     *
     * @throws PdfiumException when the page was deleted from its document, or its position is unknown after a failed edit
     * @throws BusyException on ZTS PHP when another thread is using PDFium
     */
    public function cropBox(): ?Rect {}

    /**
     * Writes the page's own crop box as given, without checking that it lies inside the media box;
     * other Page objects at the same index report it too. width() and height() follow; a crop box
     * that does not overlap the media box leaves them 0. The change is written by save() and
     * toString().
     *
     * @throws \ValueError when a side is unset or not finite, or left is not less than right or bottom
     *         not less than top once rounded to floats
     * @throws PdfiumException when the page was deleted from its document, or its position is unknown after a failed edit
     * @throws BusyException on ZTS PHP when another thread is using PDFium
     */
    public function setCropBox(Rect $box): void {}

    /**
     * The page's own bleed box in points, with left <= right and bottom <= top: for print, the
     * finished page plus the margin printed past it so that colour reaches the cut edge; unset in
     * most files. Returns null when the page has none of its own. It does not affect width() and
     * height().
     *
     * @throws PdfiumException when the page was deleted from its document, or its position is unknown after a failed edit
     * @throws BusyException on ZTS PHP when another thread is using PDFium
     */
    public function bleedBox(): ?Rect {}

    /**
     * Writes the page's own bleed box as given; other Page objects at the same index report it too.
     * It does not affect width() and height(). The change is written by save() and toString().
     *
     * @throws \ValueError when a side is unset or not finite, or left is not less than right or bottom
     *         not less than top once rounded to floats
     * @throws PdfiumException when the page was deleted from its document, or its position is unknown after a failed edit
     * @throws BusyException on ZTS PHP when another thread is using PDFium
     */
    public function setBleedBox(Rect $box): void {}

    /**
     * The page's own trim box in points, with left <= right and bottom <= top: for print, the
     * finished page after cutting; unset in most files. Returns null when the page has none of its
     * own. It does not affect width() and height().
     *
     * @throws PdfiumException when the page was deleted from its document, or its position is unknown after a failed edit
     * @throws BusyException on ZTS PHP when another thread is using PDFium
     */
    public function trimBox(): ?Rect {}

    /**
     * Writes the page's own trim box as given; other Page objects at the same index report it too.
     * It does not affect width() and height(). The change is written by save() and toString().
     *
     * @throws \ValueError when a side is unset or not finite, or left is not less than right or bottom
     *         not less than top once rounded to floats
     * @throws PdfiumException when the page was deleted from its document, or its position is unknown after a failed edit
     * @throws BusyException on ZTS PHP when another thread is using PDFium
     */
    public function setTrimBox(Rect $box): void {}

    /**
     * The page's own art box in points, with left <= right and bottom <= top: the page's meaningful
     * content, as its creator marked it; unset in most files. Returns null when the page has none of
     * its own. It does not affect width() and height().
     *
     * @throws PdfiumException when the page was deleted from its document, or its position is unknown after a failed edit
     * @throws BusyException on ZTS PHP when another thread is using PDFium
     */
    public function artBox(): ?Rect {}

    /**
     * Writes the page's own art box as given; other Page objects at the same index report it too.
     * It does not affect width() and height(). The change is written by save() and toString().
     *
     * @throws \ValueError when a side is unset or not finite, or left is not less than right or bottom
     *         not less than top once rounded to floats
     * @throws PdfiumException when the page was deleted from its document, or its position is unknown after a failed edit
     * @throws BusyException on ZTS PHP when another thread is using PDFium
     */
    public function setArtBox(Rect $box): void {}

    /**
     * The whole page as UTF-8, with PDFium's line breaks kept as they are.
     *
     * @throws FormatException when PDFium cannot load the page's text
     * @throws MemoryException when the text would not fit under memory_limit
     * @throws PdfiumException when the page was deleted from its document, or its position is unknown after a failed edit
     * @throws BusyException on ZTS PHP when another thread is using PDFium
     */
    public function text(): string {}

    /**
     * Renders the page, on white unless $transparent is true. Each side is
     * max(1, round(points * $dpi / 72)) pixels.
     *
     * @param Rect|null $region render only this area, in points as TextPage gives them (rotation not
     *        applied); the image is that part of the whole-page render, to within a pixel at the edges,
     *        with background outside the page, and the image size limits apply to it, not the whole page
     * @param bool $transparent render on a transparent background instead: PNG keeps the alpha channel;
     *        JPEG has none, so it is drawn on white
     * @param bool $annotations draw annotations and form fields. Form fields, and annotations of most
     *        types, that have no appearance stream get one, and on a /NeedAppearances form the fields'
     *        streams can be replaced; save() and toString() then include them. false leaves the
     *        document unchanged
     * @param bool $grayscale render in gray: PNG and JPEG output have one gray channel, two in a
     *        transparent PNG
     *
     * @throws \ValueError when $dpi is outside 1..2400, the image would exceed 65,500 pixels a side or
     *         100,000,000 pixels, the whole page would exceed 4,194,304 pixels a side with $region, or
     *         $region has no width or height, does not overlap the page, or has a side unset or one its
     *         constructor refuses
     * @throws MemoryException when the bitmap and its encoding would not fit under memory_limit; the
     *         bitmap (4 bytes a pixel, 1 in grayscale, 2 in transparent grayscale) counts against
     *         memory_limit for as long as the Bitmap lives
     * @throws PdfiumException when the page was deleted from its document, or its position is unknown after a failed edit
     * @throws BusyException on ZTS PHP when another thread is using PDFium
     */
    public function render(
        int $dpi = 150,
        ?Rect $region = null,
        bool $transparent = false,
        bool $annotations = true,
        bool $grayscale = false,
    ): Bitmap {}

    /**
     * Reads the page's text once and keeps it, for chars(), search() and the other text methods.
     *
     * @throws FormatException when PDFium cannot load the page's text
     * @throws PdfiumException when the page was deleted from its document, or its position is unknown after a failed edit
     * @throws BusyException on ZTS PHP when another thread is using PDFium
     */
    public function textPage(): TextPage {}

    /**
     * The action run when the page is opened; null when there is none.
     *
     * @throws MemoryException when the action would not fit under memory_limit
     * @throws PdfiumException when the page was deleted from its document, or its position is unknown after a failed edit
     * @throws BusyException on ZTS PHP when another thread is using PDFium
     */
    public function openAction(): ?Action {}

    /**
     * The action run when the page is closed; null when there is none.
     *
     * @throws MemoryException when the action would not fit under memory_limit
     * @throws PdfiumException when the page was deleted from its document, or its position is unknown after a failed edit
     * @throws BusyException on ZTS PHP when another thread is using PDFium
     */
    public function closeAction(): ?Action {}

    /**
     * The page's link annotations, in the page's annotation order: back to front.
     *
     * @return Link[]
     * @throws MemoryException when the links would not fit under memory_limit
     * @throws PdfiumException when the page was deleted from its document, or its position is unknown after a failed edit
     * @throws BusyException on ZTS PHP when another thread is using PDFium
     */
    public function links(): array {}

    /**
     * The topmost link annotation at this point, in points as TextPage gives them; null when there is none.
     * Once linkAt() has looked at a page of a document, it keeps finding that page's link annotations
     * as they were then: a link removed since is still found, and one added is not. links() and
     * Annotation::link() see the change.
     *
     * @throws \ValueError when $x or $y is not finite
     * @throws MemoryException when the link would not fit under memory_limit
     * @throws PdfiumException when the page was deleted from its document, or its position is unknown after a failed edit
     * @throws BusyException on ZTS PHP when another thread is using PDFium
     */
    public function linkAt(float $x, float $y): ?Link {}

    /**
     * @throws BusyException on ZTS PHP when another thread is using PDFium
     */
    public function annotationCount(): int {}

    /**
     * Every annotation on the page, in the page's order (back to front), keyed by the index
     * annotation() takes. An entry of a damaged list that is not an annotation is left out, so keys
     * can skip numbers. Each call returns new objects.
     *
     * @return array<int, Annotation>
     * @throws MemoryException when the list would not fit under memory_limit
     * @throws BusyException on ZTS PHP when another thread is using PDFium
     */
    public function annotations(): array {}

    /**
     * @throws \ValueError when $index is outside 0..annotationCount()-1
     * @throws FormatException when the entry is not an annotation, in a damaged list
     * @throws BusyException on ZTS PHP when another thread is using PDFium
     */
    public function annotation(int $index): Annotation {}

    /**
     * Adds an annotation of a type such as "Highlight" at the end of the page's list, in place. It
     * has no colour, contents or appearance yet, and its flags are 0 (add FLAG_PRINT for viewers to
     * print it). PDFium creates Text, Link, FreeText, Square, Circle, Highlight, Underline, Squiggly,
     * StrikeOut, Stamp, Ink, Popup and FileAttachment.
     *
     * @throws \ValueError when $type is not a type name type() gives, or is "Unknown", or a side of
     *         $rect is unset or one its constructor refuses, as in one made without it
     * @throws UnsupportedException when PDFium cannot create annotations of that type
     * @throws PdfiumException when PDFium cannot add the annotation
     * @throws BusyException on ZTS PHP when another thread is using PDFium
     */
    public function addAnnotation(string $type, Rect $rect): Annotation {}

    /**
     * Removes an annotation from the page, in place. The annotations after it move down one index,
     * and every Annotation for the removed one throws from then on, unless a damaged page lists it
     * twice (see the behaviour notes). A Widget's form field stays in the document.
     *
     * @throws \ValueError when $index is outside 0..annotationCount()-1
     * @throws PdfiumException when PDFium cannot remove the annotation
     * @throws BusyException on ZTS PHP when another thread is using PDFium
     */
    public function removeAnnotation(int $index): void {}

    /**
     * One FormField for every widget on the page, back to front, so a radio group gives one per button.
     * Like render(), this can add appearance streams that save() and toString() then include.
     *
     * @return FormField[]
     * @throws MemoryException when the fields would not fit under memory_limit
     * @throws BusyException on ZTS PHP when another thread is using PDFium
     */
    public function formFields(): array {}

    /**
     * The form field whose widget is at this point, in points as TextPage gives them; null when there
     * is none. Like formFields(), it can add appearance streams that a later save() writes.
     *
     * @throws \ValueError when $x or $y is not finite
     * @throws BusyException on ZTS PHP when another thread is using PDFium
     */
    public function formFieldAt(float $x, float $y): ?FormField {}

    /**
     * Turns every annotation of the page, form fields and links included, into page content, in place
     * and for good; false when there is nothing to flatten. $forPrint keeps only annotations flagged
     * for printing. FormFields and Annotations made earlier then throw, and other Page objects for this
     * page lack the new content, so fetch them again.
     *
     * @throws PdfiumException when PDFium cannot flatten the page
     * @throws FormatException when PDFium cannot load the flattened page again; the Page then throws
     *         FormatException from every method except index(); fetch it again with Document::page()
     * @throws BusyException on ZTS PHP when another thread is using PDFium
     */
    public function flatten(bool $forPrint = false): bool {}
}

/**
 * An annotation on a page. It keeps its page and document alive. Two objects for the same
 * annotation see each other's changes, but are not ==; compare index(). Once the annotation is
 * removed from its page, or its page is flattened, every method throws. It follows its page when
 * pages are moved, added or deleted, and throws as the Page's methods do once the page is deleted
 * or its position unknown.
 *
 * @strict-properties
 * @not-serializable
 */
final class Annotation
{
    /**
     * @var int
     */
    public const FLAG_INVISIBLE = 1;
    /**
     * @var int
     */
    public const FLAG_HIDDEN = 2;
    /**
     * @var int
     */
    public const FLAG_PRINT = 4;
    /**
     * @var int
     */
    public const FLAG_NO_ZOOM = 8;
    /**
     * @var int
     */
    public const FLAG_NO_ROTATE = 16;
    /**
     * @var int
     */
    public const FLAG_NO_VIEW = 32;
    /**
     * @var int
     */
    public const FLAG_READ_ONLY = 64;
    /**
     * @var int
     */
    public const FLAG_LOCKED = 128;
    /**
     * @var int
     */
    public const FLAG_TOGGLE_NO_VIEW = 256;

    /**
     * @var int
     */
    public const APPEARANCE_NORMAL = 0;
    /**
     * @var int
     */
    public const APPEARANCE_ROLLOVER = 1;
    /**
     * @var int
     */
    public const APPEARANCE_DOWN = 2;

    private function __construct() {}

    /**
     * The annotation's current position on its page, starting at 0; for one a damaged page lists
     * twice, the first.
     *
     * @throws PdfiumException when the annotation was removed from its page
     * @throws BusyException on ZTS PHP when another thread is using PDFium
     */
    public function index(): int {}

    /**
     * The PDF subtype name, such as "Text", "Highlight" or "Widget"; "Unknown" for a subtype
     * PDFium does not know.
     *
     * @throws PdfiumException when the annotation was removed from its page
     * @throws BusyException on ZTS PHP when another thread is using PDFium
     */
    public function type(): string {}

    /**
     * The annotation's rectangle in points, with left below right and bottom below top. PDFium gives
     * 0, 0, 0, 0 for an annotation without one; null when PDFium cannot read it.
     *
     * @throws PdfiumException when the annotation was removed from its page
     * @throws BusyException on ZTS PHP when another thread is using PDFium
     */
    public function rect(): ?Rect {}

    /**
     * Moves or resizes the annotation, in place. An appearance stream stays, and render() draws it
     * in the new rectangle, except for a Highlight, Underline, Squiggly or StrikeOut whose appearance
     * PDFium generated: that one is drawn at its quad points, so change those too, and call
     * clearAppearance() for the next render() to draw them.
     *
     * @throws \ValueError when a side of $rect is unset or one its constructor refuses, as in one made
     *         without it
     * @throws PdfiumException when the annotation was removed from its page, or PDFium refuses the
     *         rectangle
     * @throws BusyException on ZTS PHP when another thread is using PDFium
     */
    public function setRect(Rect $rect): void {}

    /**
     * The annotation flags, a combination of the FLAG_* constants.
     *
     * @throws PdfiumException when the annotation was removed from its page
     * @throws BusyException on ZTS PHP when another thread is using PDFium
     */
    public function flags(): int {}

    /**
     * Replaces the annotation flags with $flags, a combination of the FLAG_* constants; other bits
     * are written as given.
     *
     * @throws \ValueError when $flags is outside 0..4294967295
     * @throws PdfiumException when the annotation was removed from its page, or PDFium refuses the
     *         flags
     * @throws BusyException on ZTS PHP when another thread is using PDFium
     */
    public function setFlags(int $flags): void {}

    /**
     * The colour (/C), with /CA as alpha; without /C, yellow for a Highlight and black for other types.
     * A component far above 1 may read as 0. Null while the annotation has a normal appearance stream.
     *
     * @throws PdfiumException when the annotation was removed from its page
     * @throws BusyException on ZTS PHP when another thread is using PDFium
     */
    public function color(): ?Color {}

    /**
     * The interior colour (/IC), read as color() reads /C; null while the annotation has a normal
     * appearance stream.
     *
     * @throws PdfiumException when the annotation was removed from its page
     * @throws BusyException on ZTS PHP when another thread is using PDFium
     */
    public function interiorColor(): ?Color {}

    /**
     * Sets the colour (/C) and the opacity (/CA). Throws while the annotation has a normal appearance
     * stream, which most types get from a render(). Call clearAppearance() first; render() then
     * generates a new one for most types, and the rest, such as a Stamp, draw nothing.
     *
     * @throws \ValueError when a component is outside 0..255
     * @throws PdfiumException when the annotation was removed from its page, the annotation has a
     *         normal appearance stream, or PDFium refuses the colour
     * @throws BusyException on ZTS PHP when another thread is using PDFium
     */
    public function setColor(int $red, int $green, int $blue, int $alpha = 255): void {}

    /**
     * Sets the interior colour (/IC) and the opacity (/CA), as setColor() does. Both colours share
     * /CA, so $alpha, 255 by default, replaces an opacity setColor() set.
     *
     * @throws \ValueError when a component is outside 0..255
     * @throws PdfiumException when the annotation was removed from its page, the annotation has a
     *         normal appearance stream, or PDFium refuses the colour
     * @throws BusyException on ZTS PHP when another thread is using PDFium
     */
    public function setInteriorColor(int $red, int $green, int $blue, int $alpha = 255): void {}

    /**
     * The border (/Border); null when the annotation has none or it has fewer than three numbers.
     *
     * @throws PdfiumException when the annotation was removed from its page
     * @throws BusyException on ZTS PHP when another thread is using PDFium
     */
    public function border(): ?Border {}

    /**
     * Sets the border (/Border), in points. Also removes the appearance streams of every mode; render()
     * generates a new one for most types, and the rest, such as a Stamp, then draw nothing.
     *
     * @throws \ValueError when a value is not finite or is negative
     * @throws PdfiumException when the annotation was removed from its page, or PDFium refuses the
     *         border
     * @throws BusyException on ZTS PHP when another thread is using PDFium
     */
    public function setBorder(float $horizontalRadius, float $verticalRadius, float $width): void {}

    /**
     * The quad points of a Highlight, Underline, Squiggly, StrikeOut or Link annotation: the areas it
     * covers. Empty when it has none.
     *
     * @return Quad[]
     * @throws PdfiumException when the annotation was removed from its page
     * @throws MemoryException when the list would not fit under memory_limit
     * @throws BusyException on ZTS PHP when another thread is using PDFium
     */
    public function quadPoints(): array {}

    /**
     * Appends an area to the quad points of a Highlight, Underline, Squiggly, StrikeOut or Link
     * annotation. While the annotation has an appearance stream, render() keeps drawing it; the
     * area shows after clearAppearance().
     *
     * @throws \ValueError when a corner of $quad is unset or not finite, as in one made without its
     *         constructor
     * @throws PdfiumException when the annotation was removed from its page, or PDFium refuses: the
     *         type has no quad points
     * @throws BusyException on ZTS PHP when another thread is using PDFium
     */
    public function addQuadPoints(Quad $quad): void {}

    /**
     * Replaces the area at $index in the quad points. PDFium has no call to remove one. While the
     * annotation has an appearance stream, render() keeps drawing it; the new area shows after
     * clearAppearance().
     *
     * @throws \ValueError when $index is outside 0..count(quadPoints())-1, or a corner of $quad is
     *         unset or not finite
     * @throws PdfiumException when the annotation was removed from its page, or PDFium refuses: the
     *         type has no quad points
     * @throws BusyException on ZTS PHP when another thread is using PDFium
     */
    public function setQuadPoints(int $index, Quad $quad): void {}

    /**
     * The vertices (/Vertices) of a Polygon or PolyLine annotation; empty for other types.
     *
     * @return Point[]
     * @throws PdfiumException when the annotation was removed from its page
     * @throws MemoryException when the list would not fit under memory_limit
     * @throws BusyException on ZTS PHP when another thread is using PDFium
     */
    public function vertices(): array {}

    /**
     * The start and end points (/L) of a Line annotation, in that order; null for other types and for
     * a Line without a valid /L.
     *
     * @return Point[]|null
     * @throws PdfiumException when the annotation was removed from its page
     * @throws BusyException on ZTS PHP when another thread is using PDFium
     */
    public function line(): ?array {}

    /**
     * The strokes (/InkList) of an Ink annotation, as one list of points per stroke; empty for other
     * types.
     *
     * @return Point[][]
     * @throws PdfiumException when the annotation was removed from its page
     * @throws MemoryException when the strokes would not fit under memory_limit
     * @throws BusyException on ZTS PHP when another thread is using PDFium
     */
    public function inkStrokes(): array {}

    /**
     * Adds a stroke to an Ink annotation and returns its index in inkStrokes(). While the annotation
     * has an appearance stream, render() keeps drawing it; the stroke shows after
     * clearAppearance().
     *
     * @param Point[] $points
     * @throws \ValueError when $points is empty, or a point has a coordinate unset or not finite
     * @throws \TypeError when an element of $points is not a Point
     * @throws PdfiumException when the annotation was removed from its page, or PDFium refuses: it is
     *         not an Ink annotation
     * @throws MemoryException when the points would not fit under memory_limit
     * @throws BusyException on ZTS PHP when another thread is using PDFium
     */
    public function addInkStroke(array $points): int {}

    /**
     * Removes every stroke of an Ink annotation. An appearance stream stays, and render() keeps
     * drawing the old strokes until clearAppearance().
     *
     * @throws PdfiumException when the annotation was removed from its page, or PDFium refuses: it is
     *         not an Ink annotation
     * @throws BusyException on ZTS PHP when another thread is using PDFium
     */
    public function removeInkStrokes(): void {}

    /**
     * Whether the annotation's dictionary has the key, such as "Contents".
     *
     * @throws \ValueError when $key is empty or contains a null byte
     * @throws PdfiumException when the annotation was removed from its page
     * @throws BusyException on ZTS PHP when another thread is using PDFium
     */
    public function hasKey(string $key): bool {}

    /**
     * The type of the key's value: "Boolean", "Number", "String", "Name", "Array", "Dictionary",
     * "Stream", "Null", "Reference", or "Unknown" when the key is missing.
     *
     * @throws \ValueError when $key is empty or contains a null byte
     * @throws PdfiumException when the annotation was removed from its page
     * @throws BusyException on ZTS PHP when another thread is using PDFium
     */
    public function valueType(string $key): string {}

    /**
     * The key's string or name value as UTF-8, such as "Contents", "T" (the author), "M" (the
     * modification date) or "NM"; null when the key is missing, empty or of another type.
     *
     * @throws \ValueError when $key is empty or contains a null byte
     * @throws PdfiumException when the annotation was removed from its page
     * @throws MemoryException when the value would not fit under memory_limit
     * @throws BusyException on ZTS PHP when another thread is using PDFium
     */
    public function stringValue(string $key): ?string {}

    /**
     * The key's number value; null when the key is missing or not a number.
     *
     * @throws \ValueError when $key is empty or contains a null byte
     * @throws PdfiumException when the annotation was removed from its page
     * @throws BusyException on ZTS PHP when another thread is using PDFium
     */
    public function numberValue(string $key): ?float {}

    /**
     * The annotation the key points to, such as "Popup" (a markup annotation's pop-up) or "IRT"
     * (the annotation a reply answers), as a new Annotation; it follows one step only. Null when
     * the key is missing or not a dictionary, when that annotation is not in this page's annotation
     * list, or when the dictionary has no /Type /Annot (optional in PDF).
     *
     * @throws \ValueError when $key is empty or contains a null byte
     * @throws PdfiumException when the annotation was removed from its page
     * @throws BusyException on ZTS PHP when another thread is using PDFium
     */
    public function linkedAnnotation(string $key): ?Annotation {}

    /**
     * Sets the key, such as "Contents" or "T" (the author), to a string, replacing its value. PDFium
     * cannot remove a key or write another type, so a name key such as "Subtype" then holds a string
     * PDFium ignores (type() gives "Unknown"). "FS" replaces a FileAttachment's file, as
     * setAttachment() does. Text starting with þÿ, ÿþ or ï»¿ (read as a byte order mark) or holding
     * U+001B is stored as given but does not read back as written.
     *
     * @throws \ValueError when $key is empty or contains a null byte, or $value is not valid UTF-8
     *         or contains a null byte
     * @throws PdfiumException when the annotation was removed from its page, or PDFium refuses the
     *         value
     * @throws MemoryException when the value would not fit under memory_limit
     * @throws BusyException on ZTS PHP when another thread is using PDFium
     */
    public function setStringValue(string $key, string $value): void {}

    /**
     * The appearance stream for one of the APPEARANCE_* modes, decoded as a PDF text string to UTF-8;
     * bytes above 0x7F do not come back as stored. Null when there is none or it is empty.
     *
     * @throws \ValueError when $mode is not one of the APPEARANCE_* constants
     * @throws PdfiumException when the annotation was removed from its page
     * @throws MemoryException when the stream would not fit under memory_limit
     * @throws BusyException on ZTS PHP when another thread is using PDFium
     */
    public function appearance(int $mode = Annotation::APPEARANCE_NORMAL): ?string {}

    /**
     * Sets the appearance stream, in page coordinates, for one of the APPEARANCE_* modes; render()
     * draws it instead of generating one. Keep it to ASCII: one character outside PDFDocEncoding makes
     * the whole stream stored as UTF-16, which draws nothing. On a /NeedAppearances form, render()
     * replaces a Widget's stream.
     *
     * @throws \ValueError when $stream is not valid UTF-8 or contains a null byte, or $mode is not
     *         one of the APPEARANCE_* constants
     * @throws PdfiumException when the annotation was removed from its page, or PDFium refuses the
     *         appearance: the annotation's rectangle has no width or height
     * @throws MemoryException when the stream would not fit under memory_limit
     * @throws BusyException on ZTS PHP when another thread is using PDFium
     */
    public function setAppearance(string $stream, int $mode = Annotation::APPEARANCE_NORMAL): void {}

    /**
     * Removes the appearance stream of one of the APPEARANCE_* modes; for APPEARANCE_NORMAL, of all
     * three. The next render() generates a new normal appearance for most types; the rest, such as a
     * Stamp, are left with none and draw nothing.
     *
     * @throws \ValueError when $mode is not one of the APPEARANCE_* constants
     * @throws PdfiumException when the annotation was removed from its page, or PDFium refuses
     * @throws BusyException on ZTS PHP when another thread is using PDFium
     */
    public function clearAppearance(int $mode = Annotation::APPEARANCE_NORMAL): void {}

    /**
     * The link of a Link annotation, the same Link that Page::links() gives for it; null for any
     * other type.
     *
     * @throws PdfiumException when the annotation was removed from its page
     * @throws MemoryException when the link would not fit under memory_limit
     * @throws BusyException on ZTS PHP when another thread is using PDFium
     */
    public function link(): ?Link {}

    /**
     * Makes a Link annotation open this URI, replacing its action. A /Dest it had stays, so another
     * viewer may still follow that. Keep the URI to ASCII and percent-encode other characters.
     *
     * @throws \ValueError when $uri is empty or contains a null byte
     * @throws PdfiumException when the annotation was removed from its page, or it is not a Link
     *         annotation
     * @throws BusyException on ZTS PHP when another thread is using PDFium
     */
    public function setUri(string $uri): void {}

    /**
     * The file of a FileAttachment annotation; null when it has none, and for any other type. The file
     * need not be in the document's attachment list; this Attachment is the annotation's own and has
     * no index.
     *
     * @throws PdfiumException when the annotation was removed from its page
     * @throws BusyException on ZTS PHP when another thread is using PDFium
     */
    public function attachment(): ?Attachment {}

    /**
     * Embeds a file in a FileAttachment annotation, in place, replacing its file; PDFium sets the
     * size, creation date and checksum. An Attachment fetched earlier from this annotation reads
     * the new file.
     *
     * @throws \ValueError when $name is empty; $name or $description is not valid UTF-8, contains a
     *         null byte or the escape character, or starts with what a PDF reader would take for a
     *         byte order mark; or $contents is longer than 2147483647 bytes, the most PDFium can store
     * @throws PdfiumException when the annotation was removed from its page, or it is not a
     *         FileAttachment annotation; or when PDFium cannot store the contents or description,
     *         in which case the annotation's previous file is gone too
     * @throws MemoryException when the name or description would not fit under memory_limit
     * @throws BusyException on ZTS PHP when another thread is using PDFium
     */
    public function setAttachment(string $name, string $contents, ?string $description = null): Attachment {}

    /**
     * Removes the annotation from its page, as Page::removeAnnotation() does with its index; every
     * object for it throws from then on. If a damaged page lists it twice, the first entry goes and
     * only this object throws.
     *
     * @throws PdfiumException when the annotation was already removed from its page, or PDFium
     *         cannot remove it
     * @throws BusyException on ZTS PHP when another thread is using PDFium
     */
    public function remove(): void {}
}

/**
 * A file embedded in the document. It keeps the document alive. Once a file is added to or deleted
 * from the document, it is stale: every method throws; fetch it again.
 *
 * One from Annotation::attachment() or setAttachment() is that annotation's own file, separate from
 * the document's list; it keeps the annotation alive and its index() throws. It reads the
 * annotation's current file on every call, also after setAttachment() or setStringValue("FS", ...),
 * and throws once its annotation is removed or has no file. Adding or deleting a document file does
 * not affect it.
 *
 * @strict-properties
 * @not-serializable
 */
final class Attachment
{
    private function __construct() {}

    /**
     * The attachment's position in its document, starting at 0. For one addAttachment() returned,
     * each call searches the list, so keep the result.
     *
     * @throws PdfiumException when the document's attachments changed since it was fetched, or PDFium
     *         cannot find it in a damaged list, or the file belongs to an annotation
     * @throws BusyException on ZTS PHP when another thread is using PDFium
     */
    public function index(): int {}

    /**
     * The file name the entry gives, as UTF-8; empty when it gives none. It can differ from the key
     * the list is sorted by, which PDFium does not show.
     *
     * @throws PdfiumException when the document's attachments changed since it was fetched; for an
     *         annotation's file, when its annotation was removed or PDFium cannot find the
     *         file
     * @throws MemoryException when the name would not fit under memory_limit
     * @throws BusyException on ZTS PHP when another thread is using PDFium
     */
    public function name(): string {}

    /**
     * The file's bytes, decoded; when PDFium cannot decode the file, the bytes as stored. Null when
     * there is no embedded file PDFium can read, for example when the entry only points to a file
     * outside the PDF.
     *
     * @throws PdfiumException when the document's attachments changed since it was fetched, or
     *         reading the file fails partway; for an annotation's file, also when its annotation
     *         was removed or PDFium cannot find the file
     * @throws MemoryException when the file would not fit under memory_limit
     * @throws BusyException on ZTS PHP when another thread is using PDFium
     */
    public function contents(): ?string {}

    /**
     * @throws PdfiumException when the document's attachments changed since it was fetched; for an
     *         annotation's file, when its annotation was removed or PDFium cannot find the
     *         file
     * @throws MemoryException when an entry would not fit under memory_limit
     * @throws BusyException on ZTS PHP when another thread is using PDFium
     */
    public function metadata(): AttachmentMetadata {}
}

/**
 * The kind of form a document has.
 */
enum FormType
{
    case None;
    case AcroForm;
    case XfaFull;
    case XfaForeground;
}

/**
 * The type of a form field. A type PDFium reports that is not listed here is Unknown.
 */
enum FormFieldType
{
    case Unknown;
    case PushButton;
    case Checkbox;
    case RadioButton;
    case ComboBox;
    case ListBox;
    case Text;
    case Signature;
}

/**
 * One widget of a form field, from Page::formFields() or Page::formFieldAt(). It keeps its page and
 * document alive. Every method throws PdfiumException once its widget is removed from the page or its
 * page is flattened; removing other annotations does not affect it. It follows its page when pages
 * are moved, added or deleted, and throws as the Page's methods do once the page is deleted or its
 * position unknown.
 *
 * @strict-properties
 * @not-serializable
 */
final class FormField
{
    private function __construct() {}

    /**
     * The field's fully qualified name, as UTF-8, such as "parent.notes"; empty when it has none.
     *
     * @throws PdfiumException when the field is gone: its widget was removed or its page flattened
     * @throws MemoryException when the name would not fit under memory_limit
     * @throws BusyException on ZTS PHP when another thread is using PDFium
     */
    public function name(): string {}

    /**
     * The field's tooltip (/TU), as UTF-8; null when it has none.
     *
     * @throws PdfiumException when the field is gone: its widget was removed or its page flattened
     * @throws MemoryException when the text would not fit under memory_limit
     * @throws BusyException on ZTS PHP when another thread is using PDFium
     */
    public function alternateName(): ?string {}

    /**
     * @throws PdfiumException when the field is gone: its widget was removed or its page flattened
     * @throws BusyException on ZTS PHP when another thread is using PDFium
     */
    public function type(): FormFieldType {}

    /**
     * The value exactly as PDFium reports it, as UTF-8; empty when there is none. For a combo box it is
     * the selected option's export value, for a list box only the first selected option's, and for a
     * checkbox or radio group the checked button's export value or "Off".
     *
     * @throws PdfiumException when the field is gone: its widget was removed or its page flattened
     * @throws MemoryException when the value would not fit under memory_limit
     * @throws BusyException on ZTS PHP when another thread is using PDFium
     */
    public function value(): string {}

    /**
     * What the field's value is when this button is checked, as UTF-8. Null for any other field type,
     * and when the button gives none.
     *
     * @throws PdfiumException when the field is gone: its widget was removed or its page flattened
     * @throws MemoryException when the value would not fit under memory_limit
     * @throws BusyException on ZTS PHP when another thread is using PDFium
     */
    public function exportValue(): ?string {}

    /**
     * Whether this checkbox or radio button is checked; false for any other field type.
     *
     * @throws PdfiumException when the field is gone: its widget was removed or its page flattened
     * @throws BusyException on ZTS PHP when another thread is using PDFium
     */
    public function isChecked(): bool {}

    /**
     * The widget's rectangle on its page, in points.
     *
     * @throws PdfiumException when the field is gone: its widget was removed or its page flattened
     * @throws BusyException on ZTS PHP when another thread is using PDFium
     */
    public function rect(): Rect {}

    /**
     * This widget's place among the field's widgets, starting at 0.
     *
     * @throws PdfiumException when the field is gone: its widget was removed or its page flattened;
     *         or when PDFium cannot read the field's group
     * @throws BusyException on ZTS PHP when another thread is using PDFium
     */
    public function controlIndex(): int {}

    /**
     * The number of widgets the field has.
     *
     * @throws PdfiumException when the field is gone: its widget was removed or its page flattened;
     *         or when PDFium cannot read the field's group
     * @throws BusyException on ZTS PHP when another thread is using PDFium
     */
    public function controlCount(): int {}

    /**
     * The options of a combo box or list box, in order, with which are selected; an empty array for
     * any other type.
     *
     * @return FormFieldOption[]
     * @throws PdfiumException when the field is gone: its widget was removed or its page flattened
     * @throws MemoryException when the options would not fit under memory_limit
     * @throws BusyException on ZTS PHP when another thread is using PDFium
     */
    public function options(): array {}

    /**
     * The field's flags, limited to those that apply to its type.
     *
     * @throws PdfiumException when the field is gone: its widget was removed or its page flattened
     * @throws BusyException on ZTS PHP when another thread is using PDFium
     */
    public function flags(): FormFieldFlags {}

    /**
     * Sets the text of a text field or an editable combo box, as if a user typed it; "" clears it. PDFium
     * cuts text at the field's /MaxLen, changes read-only fields, runs no scripts and stores characters
     * above U+FFFF wrongly, so check value() when it matters.
     *
     * @throws \ValueError when $value is not valid UTF-8 or contains a null byte
     * @throws PdfiumException when the field is gone: its widget was removed or its page flattened;
     *         when it is not a text field or editable combo box; or when PDFium refuses to edit it
     * @throws MemoryException when the text would not fit under memory_limit
     * @throws BusyException on ZTS PHP when another thread is using PDFium
     */
    public function setValue(string $value): void {}

    /**
     * Checks or unchecks a checkbox or radio button, as if a user clicked it; nothing happens when it is
     * already in that state. PDFium does not change a read-only button or uncheck a radio button, so
     * those throw.
     *
     * @throws PdfiumException when the field is gone: its widget was removed or its page flattened;
     *         when the field is not a checkbox or radio button; or when PDFium did not leave the
     *         button in the requested state
     * @throws BusyException on ZTS PHP when another thread is using PDFium
     */
    public function setChecked(bool $checked = true): void {}

    /**
     * Selects or deselects one option of a combo box or list box, as if a user picked it. It replaces the
     * selection, except in a multiSelect list box; PDFium does not deselect a combo box's option, so that
     * throws. PDFium changes read-only fields too.
     *
     * @throws \ValueError when $option is not an option index of the field
     * @throws PdfiumException when the field is gone: its widget was removed or its page flattened;
     *         when the field is not a combo box or list box; or when PDFium refuses to change the
     *         selection
     * @throws BusyException on ZTS PHP when another thread is using PDFium
     */
    public function select(int $option, bool $selected = true): void {}
}

/**
 * The page's text as PDFium read it once. It keeps its page and document alive, and its methods
 * throw as the Page's do once the page is deleted or its position unknown.
 *
 * @strict-properties
 * @not-serializable
 */
final class TextPage
{
    private function __construct() {}

    /**
     * The same as Page::text().
     *
     * @throws MemoryException when the text would not fit under memory_limit
     * @throws PdfiumException when the page was deleted from its document, or its position is unknown after a failed edit
     * @throws BusyException on ZTS PHP when another thread is using PDFium
     */
    public function text(): string {}

    /**
     * The number of characters: the size of chars().
     *
     * @throws PdfiumException when the page was deleted from its document, or its position is unknown after a failed edit
     * @throws BusyException on ZTS PHP when another thread is using PDFium
     */
    public function charCount(): int {}

    /**
     * The characters of text(), in the same order: all of them, or $length of them from the character
     * index $start, fewer when the text ends first; a null $length means every character from $start.
     * The character index is the one search(), webLinks() and charIndexAt() use; the returned list
     * starts at 0 for the character at $start. PDFium counts a character outside the Basic Multilingual
     * Plane as two; the second one's text is empty.
     *
     * @return Char[]
     * @throws \ValueError when $start is outside 0..charCount() or $length is negative
     * @throws MemoryException when the characters would not fit under memory_limit
     * @throws PdfiumException when the page was deleted from its document, or its position is unknown after a failed edit
     * @throws BusyException on ZTS PHP when another thread is using PDFium
     */
    public function chars(int $start = 0, ?int $length = null): array {}

    /**
     * The index of the character at this point, or null. The tolerances are the width and height of
     * an area centred on the point, so a character up to half a tolerance away also counts.
     *
     * @throws \ValueError when a coordinate or tolerance is not finite, or a tolerance is negative
     * @throws PdfiumException when the page was deleted from its document, or its position is unknown after a failed edit
     * @throws BusyException on ZTS PHP when another thread is using PDFium
     */
    public function charIndexAt(float $x, float $y, float $xTolerance = 0.0, float $yTolerance = 0.0): ?int {}

    /**
     * The text of the characters whose centre is inside the rectangle or on its edge, as UTF-8.
     *
     * @throws \ValueError when a side of $rect is unset or one its constructor refuses, as in one made without it
     * @throws MemoryException when the text would not fit under memory_limit
     * @throws PdfiumException when the page was deleted from its document, or its position is unknown after a failed edit
     * @throws BusyException on ZTS PHP when another thread is using PDFium
     */
    public function textIn(Rect $rect): string {}

    /**
     * textIn() for every rectangle, reading the page's characters once: far faster than one textIn()
     * call per rectangle.
     *
     * @param Rect[] $rects
     * @return string[] under the keys of $rects
     * @throws \TypeError when $rects holds anything but a Rect
     * @throws \ValueError when a side of a rectangle is unset or one its constructor refuses, as in one made
     *         without it
     * @throws MemoryException when the texts would not fit under memory_limit
     * @throws PdfiumException when the page was deleted from its document, or its position is unknown after a failed edit
     * @throws BusyException on ZTS PHP when another thread is using PDFium
     */
    public function textInEach(array $rects): array {}

    /**
     * Every match of $needle in text(), in order.
     *
     * @param bool $matchCase without it, case is ignored for every alphabet in the Basic Multilingual
     *        Plane, one character for one: "ß" does not match "SS"
     * @param bool $wholeWord only matches that, where they start or end with a letter or digit, have no
     *        other one right before or after them; the characters of scripts written without spaces
     *        between words, such as Chinese, Japanese and Thai, do not count as letters
     * @param bool $overlapping look for the next match from the second character of the last one,
     *        not from after its end
     * @return TextMatch[]
     * @throws \ValueError when $needle is empty, not valid UTF-8 or contains a NUL byte
     * @throws MemoryException when the matches would not fit under memory_limit
     * @throws PdfiumException when the page was deleted from its document, or its position is unknown after a failed edit
     * @throws BusyException on ZTS PHP when another thread is using PDFium
     */
    public function search(string $needle, bool $matchCase = false, bool $wholeWord = false, bool $overlapping = false): array {}

    /**
     * The http, https and www URLs and the email addresses written in the text, which PDFium finds
     * by their shape; link annotations are not included: Page::links() gives those.
     *
     * @return WebLink[]
     * @throws MemoryException when the links would not fit under memory_limit
     * @throws PdfiumException when the page was deleted from its document, or its position is unknown after a failed edit
     * @throws BusyException on ZTS PHP when another thread is using PDFium
     */
    public function webLinks(): array {}
}

/**
 * @strict-properties
 * @not-serializable
 */
final class PageIterator implements \Iterator
{
    private function __construct() {}

    /**
     * The page at the current position, loaded on the first call and again when an edit has deleted
     * it, moved it away from key() or left its position unknown. A Page returned earlier keeps
     * following its own page.
     *
     * @throws PdfiumException when the iterator is past the last page
     * @throws FormatException when PDFium cannot load the page
     * @throws BusyException on ZTS PHP when another thread is using PDFium
     */
    public function current(): Page {}

    public function key(): int {}

    public function next(): void {}

    public function rewind(): void {}

    /**
     * Whether the iterator is on a page.
     *
     * @throws BusyException on ZTS PHP when another thread is using PDFium
     */
    public function valid(): bool {}
}

/**
 * @strict-properties
 * @not-serializable
 */
final class Bitmap
{
    private function __construct() {}

    public function width(): int {}

    public function height(): int {}

    /**
     * 8-bit PNG: RGB, RGBA, gray, or gray and alpha, following render()'s $transparent and $grayscale.
     *
     * @throws MemoryException when the PNG would not fit under memory_limit
     * @throws PdfiumException when encoding fails
     */
    public function toPng(): string {}

    /**
     * 8-bit JPEG: RGB, or gray with render()'s $grayscale; a transparent render is drawn on white.
     *
     * @throws \ValueError when $quality is outside 1..100
     * @throws MemoryException when the JPEG would not fit under memory_limit
     * @throws PdfiumException when encoding fails
     */
    public function toJpeg(int $quality = 85): string {}

    /**
     * Writes through PHP streams, so open_basedir and stream wrappers apply.
     *
     * @throws \ValueError when $path is empty
     * @throws FileException when $path cannot be written, or could only be overwritten in place while a
     *         lazily opened document reads it; the PHP warning saying why is its previous exception
     * @throws MemoryException when the PNG would not fit under memory_limit
     * @throws PdfiumException when encoding fails
     */
    public function savePng(string $path): void {}

    /**
     * Writes through PHP streams, so open_basedir and stream wrappers apply.
     *
     * @throws \ValueError when $path is empty or $quality is outside 1..100
     * @throws FileException when $path cannot be written, or could only be overwritten in place while a
     *         lazily opened document reads it; the PHP warning saying why is its previous exception
     * @throws MemoryException when the JPEG would not fit under memory_limit
     * @throws PdfiumException when encoding fails
     */
    public function saveJpeg(string $path, int $quality = 85): void {}
}
