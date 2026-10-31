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
final class Document implements \Countable
{
    private function __construct() {}

    /**
     * @throws FileException when the path cannot be read or is outside open_basedir
     * @throws FormatException when the file is not a PDF or is corrupted
     * @throws PasswordException when the password is missing or wrong
     */
    public static function open(string $path, ?string $password = null): Document {}

    /**
     * The document keeps $bytes alive for its whole life.
     *
     * @throws \ValueError when $bytes is empty
     * @throws FormatException when the bytes are not a PDF or are corrupted
     * @throws PasswordException when the password is missing or wrong
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
}

/**
 * @strict-properties
 * @not-serializable
 */
final class Page
{
    private function __construct() {}
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
}
