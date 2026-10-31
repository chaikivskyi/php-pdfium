--TEST--
extractPages() and merge() results do not keep their source documents alive
--EXTENSIONS--
pdfium
--FILE--
<?php
$bytes = file_get_contents(__DIR__ . '/fixtures/text-3pages.pdf');
$src = Pdfium\Document::fromString($bytes);
$size = strlen($bytes);
unset($bytes);
$sub = $src->extractPages([2]);
$merged = Pdfium\Document::merge([$src, $sub]);
$before = memory_get_usage();
unset($src);
var_dump($before - memory_get_usage() >= $size);
$acc = $sub;
for ($i = 0; $i < 50; $i++) {
    $acc = Pdfium\Document::merge([$acc->extractPages([0]), $sub]);
}
var_dump($acc->pageCount(), $merged->pageCount(), $merged->page(3)->text() === $sub->page(0)->text());
?>
--EXPECT--
bool(true)
int(2)
int(4)
bool(true)
