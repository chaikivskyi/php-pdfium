--TEST--
pdfium.font_paths makes PDFium use fonts from the listed directories, skipping one that does not exist and empty entries; standard fonts still draw; ini_set() cannot change it
--EXTENSIONS--
pdfium
zlib
--INI--
pdfium.font_paths=::/no/such/dir::{PWD}/fixtures/fonts::
--FILE--
<?php
require __DIR__ . '/pdf.inc';
require __DIR__ . '/png.inc';
var_dump(str_starts_with(ini_get('pdfium.font_paths'), '::/no/such/dir::'), str_ends_with(ini_get('pdfium.font_paths'), '/fixtures/fonts::'));
$img = png_render(font_pdf('PdfiumTestSquare'));
foreach ([[104, 104], [195, 104], [104, 195], [195, 195], [150, 150]] as [$x, $y]) {
    var_dump(png_pixel($img, $x, $y)[0] < 50);
}
var_dump(png_pixel($img, 90, 150)[0] > 200, png_pixel($img, 210, 150)[0] > 200);
var_dump(ini_set('pdfium.font_paths', '/elsewhere'));
var_dump(str_ends_with(ini_get('pdfium.font_paths'), '/fixtures/fonts::'));
$helvetica = png_render(font_pdf('Helvetica'));
var_dump(ink_in($helvetica, 100, 199), png_pixel($helvetica, 104, 104)[0] > 200);
ob_start();
phpinfo(INFO_MODULES);
var_dump(str_contains(ob_get_clean(), 'pdfium.font_paths'));
?>
--EXPECT--
bool(true)
bool(true)
bool(true)
bool(true)
bool(true)
bool(true)
bool(true)
bool(true)
bool(true)
bool(false)
bool(true)
bool(true)
bool(true)
bool(true)
