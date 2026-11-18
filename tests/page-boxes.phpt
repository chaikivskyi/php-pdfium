--TEST--
Page boxes: getters give the page's own entry with ordered corners, setters write it, bad rectangles are rejected, and a Rect made without its constructor must have every side set; a crop box outside the media box leaves a 0x0 page that renders as one background pixel; a page without /Type takes every box
--EXTENSIONS--
pdfium
--FILE--
<?php
require __DIR__ . '/pdf.inc';
$doc = Pdfium\Document::fromString(boxes_pdf());
$show = fn (?Pdfium\Rect $r) => $r ? "$r->left $r->bottom $r->right $r->top" : 'null';
$all = fn ($p) => implode(' | ', array_map($show, [$p->mediaBox(), $p->cropBox(), $p->bleedBox(), $p->trimBox(), $p->artBox()]));
echo $all($doc->page(0)), "\n";
echo $all($doc->page(1)), "\n";
echo $show($doc->page(2)->cropBox()), "\n";
$p = $doc->page(0);
$p->setRotation(0);
$p->setMediaBox(new Pdfium\Rect(0, 0, 200, 100));
$p->setCropBox(new Pdfium\Rect(10.5, 20, 110.5, 70));
$p->setBleedBox(new Pdfium\Rect(1, 1, 2, 2)); $p->setTrimBox(new Pdfium\Rect(3, 3, 4, 4)); $p->setArtBox(new Pdfium\Rect(5, 5, 6, 6));
echo $all($p), ' ', $p->width(), 'x', $p->height(), "\n";
echo $all(Pdfium\Document::fromString($doc->toString())->page(0)), "\n";
$bad = [new Pdfium\Rect(0, 0, 0, 10), new Pdfium\Rect(0, 5, 10, 5), unserialize('O:11:"Pdfium\\Rect":4:{s:4:"left";d:0;s:6:"bottom";d:0;s:5:"right";d:INF;s:3:"top";d:10;}'),
    unserialize('O:11:"Pdfium\\Rect":4:{s:4:"left";d:0;s:6:"bottom";d:NAN;s:5:"right";d:10;s:3:"top";d:10;}'),
    (new ReflectionClass(Pdfium\Rect::class))->newInstanceWithoutConstructor(),
    unserialize('O:11:"Pdfium\\Rect":4:{s:4:"left";d:5;s:6:"bottom";d:0;s:5:"right";d:1;s:3:"top";d:10;}'),
    new Pdfium\Rect(100, 100, 100.0000000001, 200), new Pdfium\Rect(0, 1e-50, 10, 2e-50), new Pdfium\Rect(1e300, 0, 2e300, 5)];
$before = $all($p);
foreach ($bad as $r) {
    foreach (['setMediaBox', 'setTrimBox'] as $m) {
        try { $p->$m($r); } catch (ValueError $e) { echo $e->getMessage(), "\n"; }
    }
}
var_dump($all($p) === $before);
$p->setArtBox(new Pdfium\Rect(-1e300, 0, 1e300, 5));
echo $show($p->artBox()), "\n";
$p->setCropBox(new Pdfium\Rect(300, 300, 400, 400));
$back = Pdfium\Document::fromString($doc->toString())->page(0);
echo 'crop box outside the media box: ', $p->width(), 'x', $p->height(), ', saved: ', $back->width(), 'x', $back->height(), "\n";
$blank = fn (bool $transparent) => Pdfium\Document::create()->addPage(72, 72)->render(dpi: 1, transparent: $transparent)->toPng();
$image = $p->render(dpi: 300);
echo '  render: ', $image->width(), 'x', $image->height(), ', background only: ',
    json_encode($image->toPng() === $blank(false) && $p->render(transparent: true)->toPng() === $blank(true)), "\n";
$u = Pdfium\Document::fromString(untyped_pdf())->page(1);
echo 'without /Type: ', $all($u), "\n";
$u->setMediaBox(new Pdfium\Rect(0, 0, 200, 100));
$u->setCropBox(new Pdfium\Rect(10, 10, 110, 60));
$u->setBleedBox(new Pdfium\Rect(1, 1, 2, 2)); $u->setTrimBox(new Pdfium\Rect(3, 3, 4, 4)); $u->setArtBox(new Pdfium\Rect(5, 5, 6, 6));
echo '  set: ', $all($u), "\n";
?>
--EXPECT--
null | null | null | null | null
0 0 300 400 | 20 30 280 370 | 1 2 3 4 | 5 6 7 8 | 9 10 11 12
10 10 110 60
0 0 200 100 | 10.5 20 110.5 70 | 1 1 2 2 | 3 3 4 4 | 5 5 6 6 100x50
0 0 200 100 | 10.5 20 110.5 70 | 1 1 2 2 | 3 3 4 4 | 5 5 6 6
Pdfium\Page::setMediaBox(): Argument #1 ($box) must have finite sides, with left less than right and bottom less than top
Pdfium\Page::setTrimBox(): Argument #1 ($box) must have finite sides, with left less than right and bottom less than top
Pdfium\Page::setMediaBox(): Argument #1 ($box) must have finite sides, with left less than right and bottom less than top
Pdfium\Page::setTrimBox(): Argument #1 ($box) must have finite sides, with left less than right and bottom less than top
Pdfium\Page::setMediaBox(): Argument #1 ($box) must have finite sides, with left less than right and bottom less than top
Pdfium\Page::setTrimBox(): Argument #1 ($box) must have finite sides, with left less than right and bottom less than top
Pdfium\Page::setMediaBox(): Argument #1 ($box) must have finite sides, with left less than right and bottom less than top
Pdfium\Page::setTrimBox(): Argument #1 ($box) must have finite sides, with left less than right and bottom less than top
Pdfium\Page::setMediaBox(): Argument #1 ($box) must have every side set
Pdfium\Page::setTrimBox(): Argument #1 ($box) must have every side set
Pdfium\Page::setMediaBox(): Argument #1 ($box) must have finite sides, with left less than right and bottom less than top
Pdfium\Page::setTrimBox(): Argument #1 ($box) must have finite sides, with left less than right and bottom less than top
Pdfium\Page::setMediaBox(): Argument #1 ($box) must have finite sides, with left less than right and bottom less than top
Pdfium\Page::setTrimBox(): Argument #1 ($box) must have finite sides, with left less than right and bottom less than top
Pdfium\Page::setMediaBox(): Argument #1 ($box) must have finite sides, with left less than right and bottom less than top
Pdfium\Page::setTrimBox(): Argument #1 ($box) must have finite sides, with left less than right and bottom less than top
Pdfium\Page::setMediaBox(): Argument #1 ($box) must have finite sides, with left less than right and bottom less than top
Pdfium\Page::setTrimBox(): Argument #1 ($box) must have finite sides, with left less than right and bottom less than top
bool(true)
-3.4028235E+38 0 3.4028235E+38 5
crop box outside the media box: 0x0, saved: 0x0
  render: 1x1, background only: true
without /Type: null | null | null | null | null
  set: 0 0 200 100 | 10 10 110 60 | 1 1 2 2 | 3 3 4 4 | 5 5 6 6
