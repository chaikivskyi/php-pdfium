--TEST--
Page::render(annotations: false) leaves out annotations and form fields and does not set up the form
--EXTENSIONS--
pdfium
zlib
--FILE--
<?php
require __DIR__ . '/pdf.inc';
require __DIR__ . '/png.inc';
$f = __DIR__ . '/fixtures/';
$page = Pdfium\Document::fromString(annot_pdf())->page(0);
$on  = png_decode($page->render(dpi: 72)->toPng());
$off = png_decode($page->render(dpi: 72, annotations: false)->toPng());
var_dump(png_pixel($on, 25, 25) === [0, 0, 0]);
var_dump(png_pixel($off, 25, 25) === [255, 255, 255]);

$saved = function ($d) {
    $s = $d->toString();
    $i = strrpos($s, '/ID');
    return substr($s, 0, $i) . substr($s, strpos($s, ']', $i) + 1);
};
$doc = Pdfium\Document::fromFile($f . 'form.pdf');
$before = $saved($doc);
$plain = $doc->page(0)->render(dpi: 72, annotations: false)->toPng();
var_dump($saved($doc) === $before);
$empty = Pdfium\Document::fromFile($f . 'form-empty.pdf')->page(0)->render(dpi: 72, annotations: false)->toPng();
var_dump($plain === $empty);
var_dump($doc->page(0)->render(dpi: 72)->toPng() !== $plain);
var_dump($saved($doc) !== $before);
?>
--EXPECT--
bool(true)
bool(true)
bool(true)
bool(true)
bool(true)
bool(true)
