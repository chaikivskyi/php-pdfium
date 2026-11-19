--TEST--
Document::addPage() inserts a blank page; held pages follow and the sizes and index are validated; on a page tree whose /Count is larger than its pages, the new page loads at the index it reports
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
var_dump($re->pageCount(), $re->page(2)->width(), $re->page(2)->mediaBox() == new Pdfium\Rect(0, 0, 200.5, 300));
foreach ([[0, 10, null], [10, -1, null], [INF, 10, null], [10, NAN, null], [1e-50, 10, null], [10, 1e-50, null], [10, 10, -1], [10, 10, 7]] as $args) {
    try { $doc->addPage(...$args); } catch (ValueError $e) { echo $e->getMessage(), "\n"; }
}
var_dump($doc->pageCount());
$big = $doc->addPage(1e300, 1e300);
var_dump($big->width(), $big->height(), $big->width() === $doc->addPage(PHP_FLOAT_MAX, 1)->width());
foreach ([[50, null], [2, null], [50, 10], [50, 1]] as [$count, $at]) {
    $liar = Pdfium\Document::fromString(liar_pdf($count));
    $b = $liar->page(1);
    $new = $liar->addPage(10, 20, $at);
    echo "liar $count at ", $at ?? 'end', ': ', $liar->pageCount(), ' pages, index ', $new->index(), ', loads ',
        $liar->page($new->index())->width(), 'x', $liar->page($new->index())->height(), ', B at ', $b->index(), ' ',
        trim($liar->page($b->index())->text()), "\n";
}
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
bool(true)
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
liar 50 at end: 51 pages, index 50, loads 10x20, B at 1 B
liar 2 at end: 3 pages, index 2, loads 10x20, B at 1 B
liar 50 at 10: 51 pages, index 10, loads 10x20, B at 1 B
liar 50 at 1: 51 pages, index 1, loads 10x20, B at 2 B
