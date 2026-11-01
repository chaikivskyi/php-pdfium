--TEST--
A Page keeps its document and source bytes alive
--EXTENSIONS--
pdfium
--FILE--
<?php
$page = Pdfium\Document::fromFile(__DIR__ . '/fixtures/text-3pages.pdf')->page(1);
gc_collect_cycles();
var_dump($page->index(), $page->width());
$bytes = file_get_contents(__DIR__ . '/fixtures/text-3pages.pdf');
$doc = Pdfium\Document::fromString($bytes);
$len = strlen($bytes);
unset($bytes);
$junk = str_repeat("\xFF", $len);
var_dump($doc->page(2)->height());
?>
--EXPECT--
int(1)
float(612)
float(792)
