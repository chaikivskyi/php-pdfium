--TEST--
Page::textPage() reads the text once and keeps its page alive
--EXTENSIONS--
pdfium
--FILE--
<?php
require __DIR__ . '/pdf.inc';
$page = Pdfium\Document::fromString(lines_pdf('Hello World', 'see https://example.com/x'))->page(0);
$tp = $page->textPage();
var_dump(get_class($tp), $tp->text() === $page->text(), $tp->chars() == $page->textPage()->chars());

$kept = Pdfium\Document::fromString(lines_pdf('kept alive'))->page(0)->textPage();
gc_collect_cycles();
var_dump($kept->text());
?>
--EXPECT--
string(15) "Pdfium\TextPage"
bool(true)
bool(true)
string(10) "kept alive"
