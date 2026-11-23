--TEST--
Document::deletePage() inside foreach continues by index, and deleting backwards removes exactly the intended pages
--EXTENSIONS--
pdfium
--FILE--
<?php
require __DIR__ . '/pdf.inc';
$doc = Pdfium\Document::fromString(letters_pdf(6));
$held = [];
foreach ($doc as $i => $page) { $held[] = $page; if ($i === 1) { $doc->deletePage(0); } }
echo order($doc), ' ', count($held), "\n";
foreach ($held as $p) { try { echo $p->index(); } catch (Pdfium\PdfiumException) { echo 'x'; } }
echo "\n";
$doc = Pdfium\Document::fromString(letters_pdf(6));
for ($i = $doc->pageCount() - 1; $i >= 0; $i--) { if ($i % 2) { $doc->deletePage($i); } }
echo order($doc), "\n";
?>
--EXPECT--
BCDEF 5
x0234
ACE
