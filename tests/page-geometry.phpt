--TEST--
Page width, height and rotation, from the crop box and as written in the PDF
--EXTENSIONS--
pdfium
--FILE--
<?php
$f = __DIR__ . '/fixtures/';
$p = Pdfium\Document::fromFile($f . 'text-3pages.pdf')->page(0);
var_dump($p->width(), $p->height(), $p->rotation());
$p = Pdfium\Document::fromFile($f . 'rotated.pdf')->page(0);
var_dump($p->width(), $p->height(), $p->rotation());
$p = Pdfium\Document::fromFile($f . 'cropped.pdf')->page(0);
var_dump($p->width(), $p->height(), $p->rotation());
$p = Pdfium\Document::fromFile($f . 'a4.pdf')->page(0);
var_dump($p->width() === 595.28, $p->height() === 841.89);
?>
--EXPECT--
float(612)
float(792)
int(0)
float(400)
float(300)
int(90)
float(300)
float(400)
int(0)
bool(true)
bool(true)
