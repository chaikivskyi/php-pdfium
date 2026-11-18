--TEST--
Document::insertPages() with the document itself as the source
--EXTENSIONS--
pdfium
--FILE--
<?php
require __DIR__ . '/pdf.inc';
$doc = Pdfium\Document::fromString(letters_pdf(3));
[$a, $b, $c] = [$doc->page(0), $doc->page(1), $doc->page(2)];
$doc->insertPages($doc, [2, 0], 1);
echo order($doc), ' ', $a->index(), $b->index(), $c->index(), "\n";
$doc->insertPages($doc);
echo order($doc), "\n";
foreach ([[[2], 1], [[2, 0], 0]] as [$ix, $at]) {
    $doc = Pdfium\Document::fromString(letters_pdf(3));
    [$a, $b, $c] = [$doc->page(0), $doc->page(1), $doc->page(2)];
    $doc->insertPages($doc, $ix, $at);
    echo order($doc), ' ', $a->index(), $b->index(), $c->index(), "\n";
}
echo order(Pdfium\Document::fromString($doc->toString())), "\n";
?>
--EXPECT--
ACABC 034
ACABCACABC
ACBC 023
CAABC 234
CAABC
