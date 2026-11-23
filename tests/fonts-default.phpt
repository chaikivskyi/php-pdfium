--TEST--
Without pdfium.font_paths a font PDFium cannot find is replaced by a substitute, without an error
--EXTENSIONS--
pdfium
zlib
--FILE--
<?php
require __DIR__ . '/pdf.inc';
require __DIR__ . '/png.inc';
var_dump(ini_get('pdfium.font_paths'));
$img = png_render(font_pdf('PdfiumTestSquare'));
var_dump(png_pixel($img, 104, 104)[0] > 200, png_pixel($img, 195, 104)[0] > 200);
var_dump(ink_in($img, 100, 199));
?>
--EXPECT--
string(0) ""
bool(true)
bool(true)
bool(true)
