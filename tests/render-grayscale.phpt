--TEST--
Page::render(grayscale: true) gives a one-channel PNG and JPEG, gray with alpha in a transparent PNG, and keeps an image's detail in gray
--EXTENSIONS--
pdfium
zlib
--FILE--
<?php
require __DIR__ . '/pdf.inc';
require __DIR__ . '/png.inc';
$page = Pdfium\Document::fromString(page_pdf(100, 100, '1 0 0 rg 10 10 30 30 re f'))->page(0);
$g = png_decode($page->render(dpi: 72, grayscale: true)->toPng());
var_dump($g['colorType']);
$red = png_pixel($g, 25, 75)[0];
var_dump($red > 0 && $red < 255);
var_dump(png_pixel($g, 50, 50) === [255]);
$ga = png_decode($page->render(dpi: 72, transparent: true, grayscale: true)->toPng());
var_dump($ga['colorType'], png_pixel($ga, 50, 50) === [0, 0], png_pixel($ga, 25, 75) === [$red, 255]);
var_dump(getimagesizefromstring($page->render(dpi: 72, grayscale: true)->toJpeg())['channels']);
$blank = Pdfium\Document::fromString(page_pdf(100, 100, ''))->page(0);
var_dump($blank->render(dpi: 72, transparent: true, grayscale: true)->toJpeg(quality: 100)
    === $blank->render(dpi: 72, grayscale: true)->toJpeg(quality: 100));
$n = png_decode(Pdfium\Document::fromString(noise_pdf(50))->page(0)->render(dpi: 72, grayscale: true)->toPng());
var_dump($n['colorType'], count(array_unique(str_split($n['rows'][25]))) > 1);
?>
--EXPECT--
int(0)
bool(true)
bool(true)
int(4)
bool(true)
bool(true)
int(1)
bool(true)
int(0)
bool(true)
