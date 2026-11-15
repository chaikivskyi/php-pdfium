--TEST--
TextPage::charIndexAt() finds the character at a point, within optional tolerances, and points beyond float range do not break it
--EXTENSIONS--
pdfium
--FILE--
<?php
require __DIR__ . '/pdf.inc';
$page = Pdfium\Document::fromString(lines_pdf('Hello World'))->page(0)->textPage();
$e = $page->chars()[1]->box;
var_dump($page->charIndexAt(($e->left + $e->right) / 2, ($e->bottom + $e->top) / 2));
var_dump($page->charIndexAt(10, 10));
var_dump($page->charIndexAt(73, 690), $page->charIndexAt(73, 690, yTolerance: 20));
var_dump($page->charIndexAt(1e300, -1e300), $page->charIndexAt(73, 690, 1e300, 1e300));
foreach ([[NAN, 0, 0, 0], [0, INF, 0, 0], [0, 0, -1, 0], [0, 0, 0, NAN]] as $args) {
    try { $page->charIndexAt(...$args); echo "found\n"; } catch (ValueError $e) { echo $e->getMessage(), "\n"; }
}
?>
--EXPECT--
int(1)
NULL
NULL
int(0)
NULL
int(0)
Pdfium\TextPage::charIndexAt(): Argument #1 ($x) must be finite
Pdfium\TextPage::charIndexAt(): Argument #2 ($y) must be finite
Pdfium\TextPage::charIndexAt(): Argument #3 ($xTolerance) must be finite and not negative
Pdfium\TextPage::charIndexAt(): Argument #4 ($yTolerance) must be finite and not negative
