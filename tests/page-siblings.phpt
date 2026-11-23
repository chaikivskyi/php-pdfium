--TEST--
Two Page objects for one page report the same size and rotation after a setter on either
--EXTENSIONS--
pdfium
--FILE--
<?php
require __DIR__ . '/pdf.inc';
$doc = Pdfium\Document::fromString(letters_pdf(2));
$one = $doc->page(0); $two = $doc->page(0); $other = $doc->page(1);
$it = $doc->pages(); $it->rewind(); $three = $it->current();
$one->setRotation(90);
foreach ([$one, $two, $three, $other] as $p) { echo $p->rotation(), ' ', $p->width(), 'x', $p->height(), "\n"; }
$two->setCropBox(new Pdfium\Rect(0, 0, 100, 50));
foreach ([$one, $two, $three, $other] as $p) { echo $p->width(), 'x', $p->height(), "\n"; }
?>
--EXPECT--
90 792x612
90 792x612
90 792x612
0 612x792
50x100
50x100
50x100
612x792
