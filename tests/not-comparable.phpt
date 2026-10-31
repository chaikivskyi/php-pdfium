--TEST--
Documents, pages, iterators and bitmaps compare equal only to themselves
--EXTENSIONS--
pdfium
--FILE--
<?php
$f = __DIR__ . '/fixtures/';
$a = Pdfium\Document::open($f . 'tiny.pdf');
$b = Pdfium\Document::open($f . 'text-3pages.pdf');
$pa = $a->page(0);
$ia = $pa->render(dpi: 10);
$pairs = [
    [$a, $b], [$a, Pdfium\Document::open($f . 'tiny.pdf')], [$pa, $b->page(2)], [$a->pages(), $b->pages()],
    [$ia, $b->page(0)->render(dpi: 10)],
];
foreach ($pairs as [$x, $y]) {
    var_dump($x == $y);
}
var_dump(in_array($b, [$a]), $a == $a, $pa == $pa, $ia == $ia, in_array($a, [$b, $a], true));
?>
--EXPECT--
bool(false)
bool(false)
bool(false)
bool(false)
bool(false)
bool(false)
bool(true)
bool(true)
bool(true)
bool(true)
