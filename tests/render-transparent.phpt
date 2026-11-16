--TEST--
Page::render(transparent: true) gives an RGBA PNG with straight alpha and a JPEG on white
--EXTENSIONS--
pdfium
zlib
--FILE--
<?php
require __DIR__ . '/pdf.inc';
require __DIR__ . '/png.inc';
$page = Pdfium\Document::fromString(page_pdf(100, 100, '1 0 0 rg 10 10 30 30 re f /G gs 0 0 1 rg 60 60 30 30 re f', '/ExtGState << /G << /ca 0.5 >> >>'))->page(0);
$t = png_decode($page->render(dpi: 72, transparent: true)->toPng());
var_dump($t['colorType']);
var_dump(png_pixel($t, 50, 50));
var_dump(png_pixel($t, 25, 75));
$half = png_pixel($t, 75, 25);
var_dump($half[2] === 255 && abs($half[3] - 128) <= 1);
var_dump(png_decode($page->render(dpi: 72)->toPng())['colorType']);
var_dump(getimagesizefromstring($page->render(dpi: 72, transparent: true)->toJpeg(quality: 100))['channels']);
$blank = Pdfium\Document::fromString(page_pdf(100, 100, ''))->page(0);
var_dump($blank->render(dpi: 72, transparent: true)->toJpeg(quality: 100) === $blank->render(dpi: 72)->toJpeg(quality: 100));
?>
--EXPECT--
int(6)
array(4) {
  [0]=>
  int(0)
  [1]=>
  int(0)
  [2]=>
  int(0)
  [3]=>
  int(0)
}
array(4) {
  [0]=>
  int(255)
  [1]=>
  int(0)
  [2]=>
  int(0)
  [3]=>
  int(255)
}
bool(true)
int(2)
int(3)
bool(true)
