--TEST--
Releasing a Page or TextPage of a deleted page before or after its document is safe
--EXTENSIONS--
pdfium
--FILE--
<?php
require __DIR__ . '/pdf.inc';
$doc = Pdfium\Document::fromString(letters_pdf());
$t = $doc->page(1)->textPage();
$doc->deletePage(1);
unset($doc);
unset($t);
echo "ok\n";

$doc = Pdfium\Document::fromString(letters_pdf());
$t = $doc->page(1)->textPage();
$doc->deletePage(1);
unset($t);
unset($doc);
echo "ok\n";

$doc = Pdfium\Document::fromString(letters_pdf());
$p1 = $doc->page(2);
$p2 = $doc->page(2);
$doc->deletePage(2);
unset($p1);
unset($p2);
unset($doc);
$doc = Pdfium\Document::fromString(letters_pdf());
$p1 = $doc->page(2);
$p2 = $doc->page(2);
$doc->deletePage(2);
unset($doc);
unset($p2);
unset($p1);
echo "ok\n";

$doc = Pdfium\Document::fromFile(__DIR__ . '/fixtures/form.pdf');
$p = $doc->page(0);
$p->render();
$doc->deletePage(0);
unset($p);
unset($doc);
echo "ok\n";
?>
--EXPECT--
ok
ok
ok
ok
