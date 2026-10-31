<?php

/**
 * @generate-class-entries
 * @generate-legacy-arginfo 80200
 */

namespace Pdfium;

class Exception extends \RuntimeException {}

class FileException extends Exception {}

class FormatException extends Exception {}

class PasswordException extends Exception {}

class UnsupportedException extends Exception {}

class MemoryException extends Exception {}

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
     */
    public static function open(string $path, ?string $password = null): Document {}

    /**
     * The document keeps $bytes alive for its whole life.
     *
     * @throws \ValueError when $bytes is empty
     * @throws FormatException when the bytes are not a PDF or are corrupted
     * @throws PasswordException when the password is missing or wrong
     * @throws UnsupportedException when the document uses a security handler PDFium does not support
     */
    public static function fromString(string $bytes, ?string $password = null): Document {}

    public function pageCount(): int {}

    /** @implementation-alias Pdfium\Document::pageCount */
    public function count(): int {}

    /**
     * Only non-empty entries are present; dates stay in raw PDF form ("D:...").
     *
     * @return array<string, string>
     */
    public function metadata(): array {}

    /**
     * Reports the document's permission flags; they are never enforced.
     *
     * @return array<string, bool>
     */
    public function permissions(): array {}

    /**
     * @throws \ValueError when $index is outside 0..pageCount()-1
     * @throws FormatException when PDFium cannot load the page
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
     * A new document with the pages at these int indexes, in this order, duplicates kept. It is
     * unencrypted, has no bookmarks, viewer preferences or metadata beyond the creator, producer and
     * creation date PDFium sets, and its form fields can no longer be filled in. It does not keep this
     * document alive.
     *
     * @throws \ValueError when $indexes is empty or an index is outside 0..pageCount()-1
     * @throws \TypeError when an element is not an int
     * @throws Exception when PDFium cannot import the pages
     */
    public function extractPages(array $indexes): Document {}

    /**
     * A new document with every page of each Document in turn, duplicates kept. It is unencrypted,
     * has no bookmarks, viewer preferences or metadata beyond the creator, producer and creation date
     * PDFium sets, and its form fields can no longer be filled in. It does not keep the given documents
     * alive.
     *
     * @throws \ValueError when $documents is empty
     * @throws \TypeError when an element is not a Document
     * @throws Exception when PDFium cannot import the pages
     */
    public static function merge(array $documents): Document {}

    /**
     * Writes the bytes toString() returns through PHP streams, so open_basedir and stream
     * wrappers apply. A new file at a plain local path left partly written by a failed write is
     * removed; an existing one is replaced by renaming a complete copy over it where possible.
     *
     * @throws \ValueError when $path is empty
     * @throws FileException when $path cannot be written; the PHP warning saying why is its previous exception
     * @throws MemoryException when the serialised document would not fit under memory_limit
     * @throws Exception when PDFium cannot serialise the document
     */
    public function save(string $path, bool $removeSecurity = false): void {}

    /**
     * A full, non-incremental copy. Encryption is kept unless $removeSecurity is true.
     *
     * @throws MemoryException when the serialised document would not fit under memory_limit
     * @throws Exception when PDFium cannot serialise the document
     */
    public function toString(bool $removeSecurity = false): string {}
}

/**
 * @strict-properties
 * @not-serializable
 */
final class Page
{
    private function __construct() {}

    /** The page's 0-based position in its document. */
    public function index(): int {}

    /** Crop box width in points, after rotation, as the shortest decimal PDFium's float holds (595.28). */
    public function width(): float {}

    /** Crop box height in points, after rotation, as the shortest decimal PDFium's float holds (841.89). */
    public function height(): float {}

    /** Page rotation in degrees: 0, 90, 180 or 270. */
    public function rotation(): int {}

    /**
     * The whole page as UTF-8, with PDFium's line breaks kept as they are.
     *
     * @throws FormatException when PDFium cannot load the page's text
     * @throws MemoryException when the text would not fit under memory_limit
     */
    public function text(): string {}

    /**
     * Renders the page with annotations and form fields drawn, on white.
     * Each side is max(1, round(points * $dpi / 72)) pixels. The first render of a form page can add
     * appearance streams for its fields to the document, which save() and toString() then include.
     *
     * @throws \ValueError when $dpi is outside 1..2400, a side would exceed 65,500 pixels, or the image
     *         would exceed 100,000,000 pixels
     * @throws MemoryException when the bitmap and its encoding would not fit under memory_limit; the
     *         bitmap counts against memory_limit for as long as the Bitmap lives
     */
    public function render(int $dpi = 150): Bitmap {}
}

/**
 * @strict-properties
 * @not-serializable
 */
final class PageIterator implements \Iterator
{
    private function __construct() {}

    public function current(): Page {}

    public function key(): int {}

    public function next(): void {}

    public function rewind(): void {}

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
     * 8-bit RGB PNG.
     *
     * @throws MemoryException when the PNG would not fit under memory_limit
     * @throws Exception when encoding fails
     */
    public function toPng(): string {}

    /**
     * @throws \ValueError when $quality is outside 1..100
     * @throws MemoryException when the JPEG would not fit under memory_limit
     * @throws Exception when encoding fails
     */
    public function toJpeg(int $quality = 85): string {}

    /**
     * Writes through PHP streams, so open_basedir and stream wrappers apply.
     *
     * @throws \ValueError when $path is empty
     * @throws FileException when $path cannot be written; the PHP warning saying why is its previous exception
     * @throws MemoryException when the PNG would not fit under memory_limit
     * @throws Exception when encoding fails
     */
    public function savePng(string $path): void {}

    /**
     * Writes through PHP streams, so open_basedir and stream wrappers apply.
     *
     * @throws \ValueError when $path is empty or $quality is outside 1..100
     * @throws FileException when $path cannot be written; the PHP warning saying why is its previous exception
     * @throws MemoryException when the JPEG would not fit under memory_limit
     * @throws Exception when encoding fails
     */
    public function saveJpeg(string $path, int $quality = 85): void {}
}
