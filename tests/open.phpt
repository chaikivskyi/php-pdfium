--TEST--
open from path and bytes, page count, Countable, password
--EXTENSIONS--
pdfium
--FILE--
<?php
$f = __DIR__ . '/fixtures/';
$doc = Pdfium\Document::fromFile($f . 'text-3pages.pdf');
var_dump($doc->pageCount(), count($doc));
var_dump(Pdfium\Document::fromString(file_get_contents($f . 'text-3pages.pdf'))->pageCount());
var_dump(Pdfium\Document::fromFile($f . 'encrypted-user.pdf', password: 'user')->pageCount());
var_dump(Pdfium\Document::fromFile($f . 'empty.pdf')->pageCount());
?>
--EXPECT--
int(3)
int(3)
int(3)
int(3)
int(0)
