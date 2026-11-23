--TEST--
Two Page objects for the same page share its annotations: one sees what the other adds, changes and removes, an Annotation from one throws after a removal through the other, and both render each change
--EXTENSIONS--
pdfium
--FILE--
<?php
require __DIR__ . '/pdf.inc';
require __DIR__ . '/png.inc';
$doc = Pdfium\Document::fromString(annots_pdf('<< /Type /Annot /Subtype /Text /Rect [10 10 30 30] /Contents (first) >>'));
$p1 = $doc->page(0);
$p2 = $doc->page(0);
$start = png_md5($p1, dpi: 36);
var_dump(png_md5($p2, dpi: 36) === $start);

$square = $p1->addAnnotation('Square', new Pdfium\Rect(100, 600, 300, 700));
var_dump($p2->annotationCount());
foreach ($p2->annotations() as $i => $a) {
    echo $i, ' ', $a->type(), "\n";
}
$square->setColor(255, 0, 0);
$square->setInteriorColor(255, 0, 0);
$red = png_md5($p2, dpi: 36);
var_dump($red !== $start, png_md5($p1, dpi: 36) === $red);

$via2 = $p2->annotation(1);
$via2->clearAppearance();
$via2->setColor(0, 0, 255);
$via2->setInteriorColor(0, 0, 255);
$blue = png_md5($p1, dpi: 36);
var_dump($blue !== $red, png_md5($p2, dpi: 36) === $blue);

$p2->removeAnnotation(1);
try { $square->type(); } catch (Pdfium\PdfiumException $e) { echo $e->getMessage(), "\n"; }
var_dump($p1->annotationCount(), png_md5($p1, dpi: 36) === $start, png_md5($p2, dpi: 36) === $start);

$text1 = $p1->annotation(0);
$text2 = $p2->annotation(0);
$text2->setStringValue('Contents', 'changed');
var_dump($text1->stringValue('Contents'));
$ink = $p2->addAnnotation('Ink', new Pdfium\Rect(100, 100, 200, 200));
var_dump($p1->annotation(1)->type());
$text1->remove();
try { $text2->index(); } catch (Pdfium\PdfiumException $e) { echo $e->getMessage(), "\n"; }
var_dump($ink->index(), $p1->annotation(0)->type());
unset($p2);
var_dump($ink->index(), $p1->annotationCount());
$saved = Pdfium\Document::fromString($doc->toString())->page(0);
var_dump($saved->annotationCount(), $saved->annotation(0)->type());
?>
--EXPECT--
bool(true)
int(2)
0 Text
1 Square
bool(true)
bool(true)
bool(true)
bool(true)
The annotation was removed from its page
int(1)
bool(true)
bool(true)
string(7) "changed"
string(3) "Ink"
The annotation was removed from its page
int(0)
string(3) "Ink"
int(0)
int(1)
int(1)
string(3) "Ink"
