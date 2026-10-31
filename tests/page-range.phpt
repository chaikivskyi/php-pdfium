--TEST--
Document::page() rejects indexes outside the page range, also when there are no pages
--EXTENSIONS--
pdfium
--FILE--
<?php
$doc = Pdfium\Document::open(__DIR__ . '/fixtures/text-3pages.pdf');
foreach ([-1, 3] as $i) {
    try { $doc->page($i); } catch (ValueError $e) { echo $e->getMessage(), "\n"; }
}
$empty = Pdfium\Document::open(__DIR__ . '/fixtures/empty.pdf');
try { $empty->page(0); } catch (ValueError $e) { echo $e->getMessage(), "\n"; }
?>
--EXPECT--
Pdfium\Document::page(): Argument #1 ($index) must be between 0 and 2
Pdfium\Document::page(): Argument #1 ($index) must be between 0 and 2
Pdfium\Document::page(): Argument #1 ($index) must be a page index, but the document has no pages
