--TEST--
Document::pageLabel() returns each page's label, null without labels or for an empty one
--EXTENSIONS--
pdfium
--FILE--
<?php
require __DIR__ . '/pdf.inc';
$doc = Pdfium\Document::fromString(nav_pdf('/PageLabels << /Nums [0 << /S /r >> 1 << /S /D /St 5 /P (A-) >> 2 << >>] >>'));
var_dump($doc->pageLabel(0), $doc->pageLabel(1), $doc->pageLabel(2));
$late = Pdfium\Document::fromString(nav_pdf('/PageLabels << /Nums [1 << /S /D >>] >>'));
var_dump($late->pageLabel(0), $late->pageLabel(1));
var_dump(Pdfium\Document::fromString(nav_pdf())->pageLabel(0));
try { $doc->pageLabel(3); } catch (ValueError $e) { echo $e->getMessage(), "\n"; }
try { $doc->pageLabel(-1); } catch (ValueError $e) { echo $e->getMessage(), "\n"; }
?>
--EXPECT--
string(1) "i"
string(3) "A-5"
NULL
string(1) "1"
string(1) "1"
NULL
Pdfium\Document::pageLabel(): Argument #1 ($index) must be between 0 and 2
Pdfium\Document::pageLabel(): Argument #1 ($index) must be between 0 and 2
