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
 * @strict-properties
 * @not-serializable
 */
final class Document implements \Countable, \IteratorAggregate
{
    private function __construct() {}

    /**
     * Reads the whole file through PHP streams, so stream wrappers and open_basedir apply. The
     * document keeps the bytes in PHP memory and does not touch the file again.
     *
     * @throws \ValueError when $path is empty
     * @throws FileException when the path cannot be read, is a local path of MAXPATHLEN or longer, or is outside open_basedir;
     *         the PHP warning saying why is its previous exception
     * @throws MemoryException when the file would not fit under memory_limit
     * @throws FormatException when the file is not a PDF or is corrupted
     * @throws PasswordException when the password is missing or wrong
     * @throws UnsupportedException when the document uses a security handler PDFium does not support
     * @throws BusyException on ZTS PHP when another thread is using PDFium
     */
    public static function fromFile(string $path, ?string $password = null): Document {}

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
     * @throws \ValueError when $index is outside 0..pageCount()-1
     * @throws FormatException when PDFium cannot load the page
     * @throws BusyException on ZTS PHP when another thread is using PDFium
     */
    public function page(int $index): Page {}

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
     * @throws PdfiumException when PDFium cannot import the pages
     * @throws BusyException on ZTS PHP when another thread is using PDFium
     */
    public function extractPages(array $indexes, bool $removeSecurity = false): Document {}

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
     * @throws PdfiumException when PDFium cannot import the pages
     * @throws BusyException on ZTS PHP when another thread is using PDFium
     */
    public static function merge(array $documents, bool $removeSecurity = false): Document {}

    /**
     * Writes the bytes toString() returns through PHP streams, so open_basedir and stream
     * wrappers apply. A new file at a plain local path left partly written by a failed write is
     * removed; an existing one is replaced by renaming a complete copy over it where possible.
     *
     * @throws \ValueError when $path is empty
     * @throws FileException when $path cannot be written; the PHP warning saying why is its previous exception
     * @throws MemoryException when the serialised document would not fit under memory_limit
     * @throws PdfiumException when PDFium cannot serialise the document
     * @throws BusyException on ZTS PHP when another thread is using PDFium
     */
    public function save(string $path, bool $removeSecurity = false): void {}

    /**
     * A full, non-incremental copy. Encryption is kept unless $removeSecurity is true.
     *
     * @throws MemoryException when the serialised document would not fit under memory_limit
     * @throws PdfiumException when PDFium cannot serialise the document
     * @throws BusyException on ZTS PHP when another thread is using PDFium
     */
    public function toString(bool $removeSecurity = false): string {}
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
 * @strict-properties
 * @not-serializable
 */
final class Page
{
    private function __construct() {}

    /**
     * The page's 0-based position in its document.
     */
    public function index(): int {}

    /**
     * Crop box width in points, after rotation, as the shortest decimal PDFium's float holds (595.28).
     *
     * @throws BusyException on ZTS PHP when another thread is using PDFium
     */
    public function width(): float {}

    /**
     * Crop box height in points, after rotation, as the shortest decimal PDFium's float holds (841.89).
     *
     * @throws BusyException on ZTS PHP when another thread is using PDFium
     */
    public function height(): float {}

    /**
     * Page rotation in degrees: 0, 90, 180 or 270.
     *
     * @throws BusyException on ZTS PHP when another thread is using PDFium
     */
    public function rotation(): int {}

    /**
     * The whole page as UTF-8, with PDFium's line breaks kept as they are.
     *
     * @throws FormatException when PDFium cannot load the page's text
     * @throws MemoryException when the text would not fit under memory_limit
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
     * @throws BusyException on ZTS PHP when another thread is using PDFium
     */
    public function textPage(): TextPage {}

    /**
     * The action run when the page is opened; null when there is none.
     *
     * @throws MemoryException when the action would not fit under memory_limit
     * @throws BusyException on ZTS PHP when another thread is using PDFium
     */
    public function openAction(): ?Action {}

    /**
     * The action run when the page is closed; null when there is none.
     *
     * @throws MemoryException when the action would not fit under memory_limit
     * @throws BusyException on ZTS PHP when another thread is using PDFium
     */
    public function closeAction(): ?Action {}

    /**
     * The page's link annotations, in the page's annotation order: back to front.
     *
     * @return Link[]
     * @throws MemoryException when the links would not fit under memory_limit
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
}

/**
 * An annotation on a page. It keeps its page and document alive. Two objects for the same
 * annotation see each other's changes, but are not ==; compare index(). Once the annotation is
 * removed from its page, every method throws.
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
     * PDFium ignores (type() gives "Unknown"). "FS" replaces a FileAttachment's file and makes
     * Attachments stale, as setAttachment() does. Text starting with þÿ, ÿþ or ï»¿ (read as a byte
     * order mark) or holding U+001B is stored as given but does not read back as written.
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
     * size, creation date and checksum. Every Attachment fetched earlier from this document's
     * annotations goes stale; a call refused because the annotation is not a FileAttachment changes
     * nothing.
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
 * the document's list; it keeps the annotation alive and its index() throws. It goes stale after
 * setAttachment() on any FileAttachment annotation of the document, setStringValue("FS", ...) on any
 * annotation of it, or removal of its annotation, but not when a document file is added or deleted.
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
     *         annotation's file, when it went stale or its annotation was removed, or PDFium cannot
     *         find the file
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
     *         reading the file fails partway; for an annotation's file, also when it went stale or
     *         its annotation was removed, or PDFium cannot find the file
     * @throws MemoryException when the file would not fit under memory_limit
     * @throws BusyException on ZTS PHP when another thread is using PDFium
     */
    public function contents(): ?string {}

    /**
     * @throws PdfiumException when the document's attachments changed since it was fetched; for an
     *         annotation's file, when it went stale or its annotation was removed, or PDFium cannot
     *         find the file
     * @throws MemoryException when an entry would not fit under memory_limit
     * @throws BusyException on ZTS PHP when another thread is using PDFium
     */
    public function metadata(): AttachmentMetadata {}
}

/**
 * The page's text as PDFium read it once. It keeps its page and document alive.
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
     * @throws BusyException on ZTS PHP when another thread is using PDFium
     */
    public function text(): string {}

    /**
     * The number of characters: the size of chars().
     *
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
     * @throws BusyException on ZTS PHP when another thread is using PDFium
     */
    public function chars(int $start = 0, ?int $length = null): array {}

    /**
     * The index of the character at this point, or null. The tolerances are the width and height of
     * an area centred on the point, so a character up to half a tolerance away also counts.
     *
     * @throws \ValueError when a coordinate or tolerance is not finite, or a tolerance is negative
     * @throws BusyException on ZTS PHP when another thread is using PDFium
     */
    public function charIndexAt(float $x, float $y, float $xTolerance = 0.0, float $yTolerance = 0.0): ?int {}

    /**
     * The text of the characters whose centre is inside the rectangle or on its edge, as UTF-8.
     *
     * @throws \ValueError when a side of $rect is unset or one its constructor refuses, as in one made without it
     * @throws MemoryException when the text would not fit under memory_limit
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
     * @throws BusyException on ZTS PHP when another thread is using PDFium
     */
    public function search(string $needle, bool $matchCase = false, bool $wholeWord = false, bool $overlapping = false): array {}

    /**
     * The http, https and www URLs and the email addresses written in the text, which PDFium finds
     * by their shape; link annotations are not included: Page::links() gives those.
     *
     * @return WebLink[]
     * @throws MemoryException when the links would not fit under memory_limit
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
     * The page at the current position, loaded on the first call.
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
     * @throws FileException when $path cannot be written; the PHP warning saying why is its previous exception
     * @throws MemoryException when the PNG would not fit under memory_limit
     * @throws PdfiumException when encoding fails
     */
    public function savePng(string $path): void {}

    /**
     * Writes through PHP streams, so open_basedir and stream wrappers apply.
     *
     * @throws \ValueError when $path is empty or $quality is outside 1..100
     * @throws FileException when $path cannot be written; the PHP warning saying why is its previous exception
     * @throws MemoryException when the JPEG would not fit under memory_limit
     * @throws PdfiumException when encoding fails
     */
    public function saveJpeg(string $path, int $quality = 85): void {}
}
