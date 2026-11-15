--TEST--
TextPage::charCount() counts the characters; TextPage::chars() with a start and length returns the same characters as a slice of them all, also when it starts between the two halves of a character outside the Basic Multilingual Plane
--EXTENSIONS--
pdfium
--FILE--
<?php
require __DIR__ . '/pdf.inc';
$page = Pdfium\Document::fromString(lines_pdf('Hello World', 'Second'))->page(0)->textPage();
$all = $page->chars();
var_dump($page->charCount(), count($all));
foreach ([[0], [6], [6, 5], [6, 0], [10, 100], [19], [0, null], [19, 1], ['length' => 2]] as $args) {
    $part = $page->chars(...$args);
    var_dump(array_is_list($part) && $part == array_slice($all, $args[0] ?? 0, $args[1] ?? $args['length'] ?? null));
}
echo implode('', array_map(fn($c) => $c->text, $page->chars(6, 5))), ' ', count($page->chars(19)), "\n";
$index = $page->charIndexAt($all[4]->x + 1, $all[4]->y + 1);
echo $index, ' ', $page->chars($index, 1)[0]->text, "\n";
$m = $page->search('second')[0];
echo implode('', array_map(fn($c) => $c->text, $page->chars($m->index, $m->length))), "\n";
foreach ([[-1], [20], [0, -1], [PHP_INT_MAX], [0, PHP_INT_MAX], [PHP_INT_MIN, PHP_INT_MIN]] as $args) {
    try { echo count($page->chars(...$args)), "\n"; } catch (ValueError $e) { echo $e->getMessage(), "\n"; }
}

$page = Pdfium\Document::fromFile(__DIR__ . '/fixtures/unicode.pdf')->page(0)->textPage();
$all = $page->chars();
for ($i = 0; $i <= $page->charCount(); $i++) {
    var_dump($page->chars($i) == array_slice($all, $i), $page->chars($i, 1) == array_slice($all, $i, 1));
}
$empty = Pdfium\Document::fromFile(__DIR__ . '/fixtures/rotated.pdf')->page(0)->textPage();
var_dump($empty->charCount(), $empty->chars(0, 5));
?>
--EXPECT--
int(19)
int(19)
bool(true)
bool(true)
bool(true)
bool(true)
bool(true)
bool(true)
bool(true)
bool(true)
bool(true)
World 0
4 o
Second
Pdfium\TextPage::chars(): Argument #1 ($start) must be between 0 and the number of characters (19)
Pdfium\TextPage::chars(): Argument #1 ($start) must be between 0 and the number of characters (19)
Pdfium\TextPage::chars(): Argument #2 ($length) must not be negative
Pdfium\TextPage::chars(): Argument #1 ($start) must be between 0 and the number of characters (19)
19
Pdfium\TextPage::chars(): Argument #2 ($length) must not be negative
bool(true)
bool(true)
bool(true)
bool(true)
bool(true)
bool(true)
bool(true)
bool(true)
bool(true)
bool(true)
int(0)
array(0) {
}
