--TEST--
Document::deletePage() removes a page and held pages follow it; an index out of range is refused; deleting the only page leaves a document with no pages that toString() writes
--EXTENSIONS--
pdfium
--FILE--
<?php
require __DIR__ . '/pdf.inc';
$doc = Pdfium\Document::fromString(letters_pdf());
[$a, $b, $c, $d] = [$doc->page(0), $doc->page(1), $doc->page(2), $doc->page(3)];
$doc->deletePage(1);
echo order($doc), ' ', $doc->pageCount(), "\n";
echo $a->index(), $c->index(), $d->index(), ' ', trim($c->text()), "\n";
echo order(Pdfium\Document::fromString($doc->toString())), "\n";
foreach ([3, -1] as $i) {
    try { $doc->deletePage($i); } catch (ValueError $e) { echo $e->getMessage(), "\n"; }
}
$doc->deletePage(2); $doc->deletePage(0); $doc->deletePage(0);
var_dump($doc->pageCount(), Pdfium\Document::fromString($doc->toString())->pageCount());
try { $doc->deletePage(0); } catch (ValueError $e) { echo $e->getMessage(), "\n"; }
?>
--EXPECT--
ACD 3
012 C
ACD
Pdfium\Document::deletePage(): Argument #1 ($index) must be between 0 and 2
Pdfium\Document::deletePage(): Argument #1 ($index) must be between 0 and 2
int(0)
int(0)
Pdfium\Document::deletePage(): Argument #1 ($index) must be a page index, but the document has no pages
