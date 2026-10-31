--TEST--
open from path and bytes, page count, Countable, password
--EXTENSIONS--
pdfium
--FILE--
<?php
$f = __DIR__ . '/fixtures/';
$doc = Pdfium\Document::open($f . 'text-3pages.pdf');
var_dump($doc->pageCount(), count($doc));
var_dump(Pdfium\Document::fromString(file_get_contents($f . 'text-3pages.pdf'))->pageCount());
var_dump(Pdfium\Document::open($f . 'encrypted-user.pdf', password: 'user')->pageCount());
var_dump(Pdfium\Document::open($f . 'empty.pdf')->pageCount());
?>
--EXPECT--
int(3)
int(3)
int(3)
int(3)
int(0)
