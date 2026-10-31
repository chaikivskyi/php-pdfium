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
final class Document
{
    private function __construct() {}
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
