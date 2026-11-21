--TEST--
A standard font is drawn with a font from pdfium.font_paths when a listed directory holds PDFium's stand-in for it (Arial for Helvetica), and with the built-in font otherwise
--EXTENSIONS--
pdfium
zlib
--INI--
pdfium.font_paths={PWD}/fixtures/fonts-standard
--FILE--
<?php
require __DIR__ . '/pdf.inc';
require __DIR__ . '/png.inc';
$type1 = str_replace('/Subtype /TrueType', '/Subtype /Type1', font_pdf('Helvetica'));
foreach ([font_pdf('Helvetica'), $type1] as $pdf) {
    $img = png_render($pdf);
    var_dump(png_pixel($img, 104, 104)[0] < 50, png_pixel($img, 150, 150)[0] < 50);
}
$courier = png_render(font_pdf('Courier'));
var_dump(ink_in($courier, 100, 199), png_pixel($courier, 104, 104)[0] > 200);
?>
--EXPECT--
bool(true)
bool(true)
bool(true)
bool(true)
bool(true)
bool(true)
