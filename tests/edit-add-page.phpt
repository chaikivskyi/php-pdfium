--TEST--
Document::addPage() inserts a blank page; held pages follow and the sizes and index are validated
--EXTENSIONS--
pdfium
--FILE--
<?php
require __DIR__ . '/pdf.inc';
$doc = Pdfium\Document::fromString(letters_pdf(3));
[$a, $c] = [$doc->page(0), $doc->page(2)];
$new = $doc->addPage(200.5, 300, 1);
var_dump($new->index(), $new->width(), $new->height(), $new->rotation(), $new->text());
echo $a->index(), $c->index(), ' ', $doc->pageCount(), "\n";
var_dump($doc->addPage(10, 10)->index(), $doc->addPage(10, 10, 0)->index(), $new->index());
$re = Pdfium\Document::fromString($doc->toString());
var_dump($re->pageCount(), $re->page(2)->width());
foreach ([[0, 10, null], [10, -1, null], [INF, 10, null], [10, NAN, null], [1e-50, 10, null], [10, 1e-50, null], [10, 10, -1], [10, 10, 7]] as $args) {
    try { $doc->addPage(...$args); } catch (ValueError $e) { echo $e->getMessage(), "\n"; }
}
var_dump($doc->pageCount());
$big = $doc->addPage(1e300, 1e300);
var_dump($big->width(), $big->height(), $big->width() === $doc->addPage(PHP_FLOAT_MAX, 1)->width());
?>
--EXPECT--
int(1)
float(200.5)
float(300)
int(0)
string(0) ""
03 4
int(4)
int(0)
int(2)
int(6)
float(200.5)
Pdfium\Document::addPage(): Argument #1 ($width) must be finite and greater than 0
Pdfium\Document::addPage(): Argument #2 ($height) must be finite and greater than 0
Pdfium\Document::addPage(): Argument #1 ($width) must be finite and greater than 0
Pdfium\Document::addPage(): Argument #2 ($height) must be finite and greater than 0
Pdfium\Document::addPage(): Argument #1 ($width) must be finite and greater than 0
Pdfium\Document::addPage(): Argument #2 ($height) must be finite and greater than 0
Pdfium\Document::addPage(): Argument #3 ($index) must be between 0 and 6
Pdfium\Document::addPage(): Argument #3 ($index) must be between 0 and 6
int(6)
float(3.4028235E+38)
float(3.4028235E+38)
bool(true)
