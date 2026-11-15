--TEST--
TextPage::chars() returns every character of text() with its boxes, origin, font and angle rounded to four decimal places
--EXTENSIONS--
pdfium
--FILE--
<?php
require __DIR__ . '/pdf.inc';
$page = Pdfium\Document::fromString(lines_pdf('Hello World', 'Second'))->page(0)->textPage();
$chars = $page->chars();
var_dump(array_is_list($chars), implode('', array_map(fn($c) => $c->text, $chars)) === $page->text());
$h = $chars[0];
var_dump($h->text, $h->x, $h->y, $h->fontSize, $h->fontName, $h->fontWeight, $h->angle, $h->generated, $h->hyphen,
    $h->unicodeMapError);
var_dump($h->box->left >= 72 && $h->box->right > $h->box->left && $h->box->bottom >= 700 && $h->box->top > 700);
var_dump($h->looseBox->left <= $h->box->left && $h->looseBox->right >= $h->box->right
    && $h->looseBox->bottom < $h->box->bottom && $h->looseBox->top >= $h->box->top);
var_dump($chars[11]->generated, bin2hex($chars[11]->text), $chars[11]->fontName, bin2hex($chars[12]->text));
var_dump($chars[array_key_last($chars)]->text, $chars[array_key_last($chars)]->y);

$rotated = Pdfium\Document::fromString(page_pdf(612, 792, 'BT /F1 12 Tf 0 1 -1 0 300 300 Tm (R) Tj ET'))->page(0)->textPage()->chars();
var_dump($rotated[0]->text, $rotated[0]->angle);
var_dump(Pdfium\Document::fromString(page_pdf(612, 792, 'BT /F1 12 Tf 0.70710678 0.70710678 -0.70710678 0.70710678 300 300 Tm (R) Tj ET'))->page(0)->textPage()->chars()[0]->angle);
foreach (['0.000001', '-0.000001'] as $c) {
    var_dump(Pdfium\Document::fromString(page_pdf(612, 792, "BT /F1 12 Tf 1 0 $c 1 300 300 Tm (R) Tj ET"))->page(0)->textPage()->chars()[0]->angle);
}

$page = Pdfium\Document::fromFile(__DIR__ . '/fixtures/unicode.pdf')->page(0)->textPage();
$chars = $page->chars();
var_dump(array_map(fn($c) => $c->text, $chars), implode('', array_map(fn($c) => $c->text, $chars)) === $page->text());
var_dump(Pdfium\Document::fromFile(__DIR__ . '/fixtures/rotated.pdf')->page(0)->textPage()->chars());
?>
--EXPECT--
bool(true)
bool(true)
string(1) "H"
float(72)
float(700)
float(12)
string(9) "Helvetica"
NULL
float(0)
bool(false)
bool(false)
bool(false)
bool(true)
bool(true)
bool(true)
string(2) "0d"
NULL
string(2) "0a"
string(1) "d"
float(600)
string(1) "R"
float(90)
float(45)
float(0)
float(0)
array(4) {
  [0]=>
  string(4) "😀"
  [1]=>
  string(0) ""
  [2]=>
  string(3) "�"
  [3]=>
  string(1) "A"
}
bool(true)
array(0) {
}
