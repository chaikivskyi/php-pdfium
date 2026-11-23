--TEST--
A pdfium.font_paths value with empty entries and missing directories is read at start-up and released at shutdown, and documents still render
--EXTENSIONS--
pdfium
--INI--
pdfium.font_paths=:/no/such/dir::{PWD}/fixtures/fonts:/also/missing:
--FILE--
<?php
require __DIR__ . '/pdf.inc';
var_dump(str_starts_with(ini_get('pdfium.font_paths'), ':/no/such/dir::'));
foreach (['PdfiumTestSquare', 'Helvetica', 'NoSuchFont'] as $font) {
    $bitmap = Pdfium\Document::fromString(font_pdf($font))->page(0)->render(dpi: 72);
    var_dump($bitmap->width(), strlen($bitmap->toJpeg()) > 0);
}
?>
--EXPECT--
bool(true)
int(300)
bool(true)
int(300)
bool(true)
int(300)
bool(true)
