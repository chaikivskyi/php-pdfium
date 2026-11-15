--TEST--
TextPage::textIn() returns the text of the characters whose centre is inside a Rect or on its edge, with a joined hyphen as "-", no line break inside a line and no space from outside it at the end; Rect checks its sides in its constructor; a Rect made without it must have every side set and sides the constructor would accept, and a side held by reference counts as set
--EXTENSIONS--
pdfium
--FILE--
<?php
require __DIR__ . '/pdf.inc';
$page = Pdfium\Document::fromString(lines_pdf('Hello World', 'Second'))->page(0)->textPage();
var_dump($page->textIn(new Pdfium\Rect(0, 690, 612, 720)));
echo json_encode($page->textIn(new Pdfium\Rect(0, 590, 612, 720))), "\n";
var_dump($page->textIn(new Pdfium\Rect(left: 0, bottom: 0, right: 10, top: 10)));
var_dump($page->textIn($page->chars()[6]->box));
$c = $page->chars();
echo json_encode([$page->textIn(new Pdfium\Rect($c[0]->box->left, 690, $c[4]->box->right, 720)), $page->textIn(new Pdfium\Rect($c[0]->box->left, 590, $c[3]->box->right, 720))]), "\n";
echo json_encode($page->textIn(new Pdfium\Rect(-1e300, -1e300, 1e300, 1e300))), "\n";
$page = Pdfium\Document::fromString(lines_pdf('some hyphen-', 'ated', 'more exam-', 'ple'))->page(0)->textPage();
[$h1, $h2] = array_values(array_filter($page->chars(), fn($c) => $c->hyphen));
foreach ([
    new Pdfium\Rect($h1->box->left + 0.5, $h1->box->bottom, 612, $h1->box->top),
    new Pdfium\Rect(0, 500, ($h1->box->left + $h1->box->right) / 2, 792),
    new Pdfium\Rect(0, $h2->box->bottom, $h2->box->left + 0.1, 792),
    new Pdfium\Rect($h2->box->left, 0, $h1->box->right, 792),
] as $rect) {
    echo json_encode($page->textIn($rect)), "\n";
}
$page = Pdfium\Document::fromString(page_pdf(612, 792, 'BT /F1 12 Tf 72 700 Td (ab) Tj 14 30 Td (X) Tj 8 -30 Td (cd) Tj ET'))->page(0)->textPage();
echo json_encode([$page->text(), $page->textIn(new Pdfium\Rect(0, 690, 612, 715))]), "\n";
var_dump(new Pdfium\Rect(1, 2, 3.5, 4));
foreach ([[1, 0, 0, 0], [0, 1, 0, 0], [NAN, 0, 0, 0], [0, 0, INF, 0], [0, -INF, 0, 0]] as $args) {
    try { new Pdfium\Rect(...$args); echo "created\n"; } catch (ValueError $e) { echo $e->getMessage(), "\n"; }
}
$bad = [
    (new ReflectionClass(Pdfium\Rect::class))->newInstanceWithoutConstructor(),
    unserialize('O:11:"Pdfium\\Rect":4:{s:4:"left";d:NAN;s:6:"bottom";d:0;s:5:"right";d:1;s:3:"top";d:1;}'),
    unserialize('O:11:"Pdfium\\Rect":4:{s:4:"left";d:5;s:6:"bottom";d:0;s:5:"right";d:1;s:3:"top";d:1;}'),
    unserialize('O:11:"Pdfium\\Rect":4:{s:4:"left";d:0;s:6:"bottom";d:0;s:5:"right";d:INF;s:3:"top";d:800;}'),
    unserialize('O:11:"Pdfium\\Rect":4:{s:4:"left";d:0;s:6:"bottom";d:9;s:5:"right";d:1;s:3:"top";d:1;}'),
];
foreach ($bad as $rect) {
    try { var_dump($page->textIn($rect)); } catch (ValueError $e) { echo $e->getMessage(), "\n"; }
}
$shared = unserialize('a:2:{i:0;d:0;i:1;O:11:"Pdfium\\Rect":4:{s:4:"left";R:2;s:6:"bottom";d:690;s:5:"right";d:612;s:3:"top";d:715;}}')[1];
var_dump($page->textIn($shared));
$r = new Pdfium\Rect(0, 0, 1, 1);
try { $r->__construct(0, 0, 2, 2); } catch (Error $e) { echo get_class($e), "\n"; }
var_dump($r->right, unserialize(serialize($r)) == $r);
?>
--EXPECTF--
string(11) "Hello World"
"Hello World\r\nSecond"
string(0) ""
string(1) "W"
["Hello","Hell\r\nSec"]
"Hello World\r\nSecond"
"-"
"some hyphen-ated\r\nmore exam-"
"some hyph\r\nated\r\nmore exam"
"en-\r\n-"
["ab\r\nX\r\ncd","abcd"]
object(Pdfium\Rect)#%d (4) {
  ["left"]=>
  float(1)
  ["bottom"]=>
  float(2)
  ["right"]=>
  float(3.5)
  ["top"]=>
  float(4)
}
Pdfium\Rect::__construct(): Argument #1 ($left) must not be greater than argument #3 ($right)
Pdfium\Rect::__construct(): Argument #2 ($bottom) must not be greater than argument #4 ($top)
Pdfium\Rect::__construct(): Argument #1 ($left) must be finite
Pdfium\Rect::__construct(): Argument #3 ($right) must be finite
Pdfium\Rect::__construct(): Argument #2 ($bottom) must be finite
Pdfium\TextPage::textIn(): Argument #1 ($rect) must have every side set
Pdfium\TextPage::textIn(): Argument #1 ($rect) must have finite sides, with left not greater than right and bottom not greater than top
Pdfium\TextPage::textIn(): Argument #1 ($rect) must have finite sides, with left not greater than right and bottom not greater than top
Pdfium\TextPage::textIn(): Argument #1 ($rect) must have finite sides, with left not greater than right and bottom not greater than top
Pdfium\TextPage::textIn(): Argument #1 ($rect) must have finite sides, with left not greater than right and bottom not greater than top
string(4) "abcd"
Error
float(1)
bool(true)
