--TEST--
Page width, height and rotation
--EXTENSIONS--
pdfium
--FILE--
<?php
$f = __DIR__ . '/fixtures/';
$p = Pdfium\Document::open($f . 'text-3pages.pdf')->page(0);
var_dump($p->width(), $p->height(), $p->rotation());
$p = Pdfium\Document::open($f . 'rotated.pdf')->page(0);
var_dump($p->width(), $p->height(), $p->rotation());
?>
--EXPECT--
float(612)
float(792)
int(0)
float(400)
float(300)
int(90)
