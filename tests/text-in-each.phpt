--TEST--
TextPage::textInEach() returns textIn() of every Rect under the same key, also for a Rect held by reference, and refuses anything but a Rect with every side set and sides its constructor would accept
--EXTENSIONS--
pdfium
--FILE--
<?php
require __DIR__ . '/pdf.inc';
$page = Pdfium\Document::fromString(lines_pdf('Hello World', 'Second'))->page(0)->textPage();
$rects = [
    'first' => new Pdfium\Rect(0, 690, 612, 720),
    5 => new Pdfium\Rect(0, 590, 612, 620),
    'none' => new Pdfium\Rect(0, 0, 10, 10),
    'o' => $page->chars(4, 1)[0]->box,
];
$each = $page->textInEach($rects);
var_dump($each, $each === array_map(fn($r) => $page->textIn($r), $rects), $page->textInEach([]));
$held = new Pdfium\Rect(0, 690, 612, 720);
$list = [&$held];
var_dump($page->textInEach($list));
foreach ([[1], [$held, null], [(new ReflectionClass(Pdfium\Rect::class))->newInstanceWithoutConstructor()],
    [$held, unserialize('O:11:"Pdfium\\Rect":4:{s:4:"left";d:NAN;s:6:"bottom";d:0;s:5:"right";d:1;s:3:"top";d:1;}')]] as $bad) {
    try { $page->textInEach($bad); echo "read\n"; } catch (Error $e) { echo get_class($e), ': ', $e->getMessage(), "\n"; }
}
var_dump(Pdfium\Document::fromFile(__DIR__ . '/fixtures/rotated.pdf')->page(0)->textPage()->textInEach(['a' => $held]));
?>
--EXPECT--
array(4) {
  ["first"]=>
  string(11) "Hello World"
  [5]=>
  string(6) "Second"
  ["none"]=>
  string(0) ""
  ["o"]=>
  string(1) "o"
}
bool(true)
array(0) {
}
array(1) {
  [0]=>
  string(11) "Hello World"
}
TypeError: Pdfium\TextPage::textInEach(): Argument #1 ($rects) must contain only Pdfium\Rect objects
TypeError: Pdfium\TextPage::textInEach(): Argument #1 ($rects) must contain only Pdfium\Rect objects
ValueError: Pdfium\TextPage::textInEach(): Argument #1 ($rects) must contain only rectangles with every side set
ValueError: Pdfium\TextPage::textInEach(): Argument #1 ($rects) must contain only rectangles with finite sides, with left not greater than right and bottom not greater than top
array(1) {
  ["a"]=>
  string(0) ""
}
