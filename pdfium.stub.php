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
     * The new file can take any index, and every Attachment fetched before is now stale.
     *
     * @throws \ValueError when $name is empty, or $name or $description is not valid UTF-8 or contains a null
     *         byte, or $contents is longer than 2147483646 bytes
     * @throws PdfiumException when PDFium refuses: the name may already be used, or the attachment list is damaged;
     *         or when PDFium cannot store the contents or description (the message says if the entry stays)
     * @throws MemoryException when the name or description would not fit under memory_limit
     * @throws BusyException on ZTS PHP when another thread is using PDFium
     */
    public function addAttachment(string $name, string $contents, ?string $description = null): Attachment {}

    /**
     * Removes an embedded file from this document, in place. Its bytes stay in the saved PDF, unused.
     * Every Attachment fetched before is now stale; to delete several, delete from the highest index down.
     *
     * @throws \ValueError when $index is outside 0..attachmentCount()-1
     * @throws PdfiumException when PDFium cannot delete the entry
     * @throws BusyException on ZTS PHP when another thread is using PDFium
     */
    public function deleteAttachment(int $index): void {}

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
     * @param bool $incremental true keeps the original file from its %PDF header on and appends
     *        the changes, with copies of the objects PDFium has read. PDFium writes a full copy
     *        instead when $removeSecurity is true, or when the document came from extractPages()
     *        or merge() and has no original bytes.
     * @param int|null $version the PDF version for the header, from 10 for 1.0 to 17 for 1.7;
     *        PDFium ignores other values. null lets PDFium choose. An incremental save keeps the
     *        original header unless it falls back to a full copy.
     * @throws \ValueError when $path is empty or $version does not fit a 32-bit integer
     * @throws FileException when $path cannot be written; the PHP warning saying why is its previous exception
     * @throws MemoryException when the serialised document would not fit under memory_limit
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
 * A link annotation on a page, as Page::links() and linkAt() return it. Coordinates are in points,
 * as TextPage gives them.
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
     * @param bool $annotations draw annotations and form fields. The first such render, of any page, can
     *        add appearance streams to the document's form fields, which save() and toString() then
     *        include; false leaves the document unchanged
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
     *
     * @throws \ValueError when $x or $y is not finite
     * @throws MemoryException when the link would not fit under memory_limit
     * @throws BusyException on ZTS PHP when another thread is using PDFium
     */
    public function linkAt(float $x, float $y): ?Link {}
}

/**
 * A file embedded in the document. It keeps the document alive. Once a file is added to or deleted
 * from the document, it is stale: every method throws; fetch it again.
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
     *         cannot find it in a damaged list
     * @throws BusyException on ZTS PHP when another thread is using PDFium
     */
    public function index(): int {}

    /**
     * The file name the entry gives, as UTF-8; empty when it gives none. It can differ from the key
     * the list is sorted by, which PDFium does not show.
     *
     * @throws PdfiumException when the document's attachments changed since it was fetched
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
     *         reading the file fails partway
     * @throws MemoryException when the file would not fit under memory_limit
     * @throws BusyException on ZTS PHP when another thread is using PDFium
     */
    public function contents(): ?string {}

    /**
     * @throws PdfiumException when the document's attachments changed since it was fetched
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
