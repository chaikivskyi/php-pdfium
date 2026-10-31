--TEST--
extractPages() and merge() keep order and duplicates, outlive their sources and carry no metadata
--EXTENSIONS--
pdfium
--FILE--
<?php
$f = __DIR__ . '/fixtures/';
$doc = Pdfium\Document::open($f . 'text-3pages.pdf');
$sub = $doc->extractPages([2, 0, 2]);
unset($doc);
gc_collect_cycles();
foreach ($sub->pages() as $p) echo trim($p->text()), "\n";
$a = Pdfium\Document::open($f . 'text-3pages.pdf');
$m = Pdfium\Document::merge([$a, Pdfium\Document::open($f . 'rotated.pdf'), $a]);
var_dump($m->pageCount(), $m->page(3)->rotation(), trim($m->page(4)->text()), isset($m->metadata()['title']));
foreach ([fn() => $a->extractPages([]), fn() => $a->extractPages([3]), fn() => $a->extractPages([-1]),
          fn() => Pdfium\Document::merge([]), fn() => Pdfium\Document::merge([$a, 'x'])] as $fn) {
    try { $fn(); echo "ok\n"; } catch (Throwable $e) { echo get_class($e), "\n"; }
}
?>
--EXPECT--
Page 3 gamma
Page 1 alpha
Page 3 gamma
int(7)
int(90)
string(12) "Page 1 alpha"
bool(false)
ValueError
ValueError
ValueError
ValueError
TypeError
