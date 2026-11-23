--TEST--
Page::setRotation() sets an absolute rotation, follows in width and height, and rejects other values; a page without /Type has its rotation and takes another
--EXTENSIONS--
pdfium
--FILE--
<?php
require __DIR__ . '/pdf.inc';
$doc = Pdfium\Document::fromString(letters_pdf(2));
$p = $doc->page(0);
foreach ([90, 180, 270, 0] as $deg) { $p->setRotation($deg); echo $p->rotation(), ' ', $p->width(), 'x', $p->height(), "\n"; }
$p->setRotation(90);
$re = Pdfium\Document::fromString($doc->toString());
echo $re->page(0)->rotation(), $re->page(1)->rotation(), "\n";
foreach ([45, -90, 360, 1] as $deg) { try { $p->setRotation($deg); } catch (ValueError $e) { echo $e->getMessage(), "\n"; } }
echo $p->rotation(), "\n";
$b = Pdfium\Document::fromString(boxes_pdf());
$b->page(0)->setRotation(0);
echo $b->page(0)->rotation(), ' ', $b->page(1)->rotation(), "\n";
$u = Pdfium\Document::fromString(untyped_pdf());
$a = $u->page(0);
echo 'without /Type: ', $a->rotation(), ' ', $a->width(), 'x', $a->height(), ', ', $u->page(1)->rotation(), "\n";
$a->setRotation(270);
echo $a->rotation(), ' ', $a->width(), 'x', $a->height(), ', saved: ', Pdfium\Document::fromString($u->toString())->page(0)->rotation(), "\n";
?>
--EXPECT--
90 792x612
180 612x792
270 792x612
0 612x792
900
Pdfium\Page::setRotation(): Argument #1 ($degrees) must be 0, 90, 180 or 270
Pdfium\Page::setRotation(): Argument #1 ($degrees) must be 0, 90, 180 or 270
Pdfium\Page::setRotation(): Argument #1 ($degrees) must be 0, 90, 180 or 270
Pdfium\Page::setRotation(): Argument #1 ($degrees) must be 0, 90, 180 or 270
90
0 90
without /Type: 90 400x300, 180
270 400x300, saved: 270
