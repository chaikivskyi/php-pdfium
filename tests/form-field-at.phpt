--TEST--
Page::formFieldAt() finds the field under a point
--EXTENSIONS--
pdfium
--FILE--
<?php
require __DIR__ . '/form.inc';
$doc = Pdfium\Document::fromString(standard_form());
$page = $doc->page(0);
$f = $page->formFieldAt(200, 710);
var_dump($f->name());
$r = $f->rect();
var_dump($r->left <= 200 && 200 <= $r->right && $r->bottom <= 710 && 710 <= $r->top);
var_dump($page->formFieldAt(30, 30));
var_dump($page->formFieldAt(500, 100));
var_dump($doc->page(1)->formFieldAt(200, 710));
foreach ([[NAN, 0], [0, INF]] as [$x, $y]) {
    try { $page->formFieldAt($x, $y); } catch (ValueError $e) { echo $e->getMessage(), "\n"; }
}
?>
--EXPECT--
string(4) "name"
bool(true)
NULL
NULL
NULL
Pdfium\Page::formFieldAt(): Argument #1 ($x) must be finite
Pdfium\Page::formFieldAt(): Argument #2 ($y) must be finite
