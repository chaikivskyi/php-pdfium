--TEST--
Annotation appearance life cycle: render() generates an appearance, which blocks setColor() until clearAppearance(); setAppearance() writes one as PDF text; setBorder() drops it; saving keeps what exists; setRect() alone does not move a generated text-markup drawing; byte order marks; quad points changed after a render keep the old drawing until clearAppearance()
--EXTENSIONS--
pdfium
zlib
--FILE--
<?php
require __DIR__ . '/pdf.inc';
require __DIR__ . '/png.inc';
function pixel(Pdfium\Page $page, float $x = 200, float $y = 675): void
{
    echo implode(',', png_pixel(png_decode($page->render(dpi: 72)->toPng()), (int) $x, (int) (792 - $y))), "\n";
}
function drawn(Pdfium\Page $page, float $x, float $y): void
{
    echo png_pixel(png_decode($page->render(dpi: 72)->toPng()), (int) $x, (int) (792 - $y)) === [255, 255, 255] ? "blank\n" : "drawn\n";
}
$rect = new Pdfium\Rect(100, 650, 300, 700);
$quad = new Pdfium\Quad(100, 700, 300, 700, 100, 650, 300, 650);
$lower = new Pdfium\Rect(100, 500, 300, 550);
$lowerQuad = new Pdfium\Quad(100, 550, 300, 550, 100, 500, 300, 500);

echo "-- highlight\n";
$doc = Pdfium\Document::fromString(nav_pdf());
$page = $doc->page(0);
$h = $page->addAnnotation('Highlight', $rect);
$h->addQuadPoints($quad);
$h->setColor(255, 0, 0);
var_dump($h->appearance());
pixel($page);
var_dump(strlen($h->appearance()) > 0);
var_dump($h->color());
attempt(fn() => $h->setColor(0, 0, 255));
attempt(fn() => $h->setInteriorColor(0, 0, 255));
pixel($page);
$h->clearAppearance();
var_dump($h->appearance());
$h->setColor(0, 0, 255);
pixel($page);
$h->setRect($lower);
$h->setQuadPoints(0, $lowerQuad);
pixel($page);
pixel($page, 200, 525);

echo "-- stamp\n";
$s = $page->addAnnotation('Stamp', $rect);
pixel($page);
var_dump($s->appearance());
$s->setAppearance('1 0 0 rg 0 0 200 50 re f');
var_dump($s->appearance());
pixel($page);
attempt(fn() => $s->setColor(0, 255, 0));
$s->setAppearance('0 1 0 rg 100 650 200 50 re f');
pixel($page);

echo "-- border\n";
$s->setAppearance('0 0 1 rg 100 650 200 50 re f', Pdfium\Annotation::APPEARANCE_ROLLOVER);
$s->setBorder(0, 0, 1);
var_dump($s->appearance(), $s->appearance(Pdfium\Annotation::APPEARANCE_ROLLOVER), $s->hasKey('AP'));
pixel($page);

echo "-- ink\n";
$ink = $page->addAnnotation('Ink', new Pdfium\Rect(300, 100, 400, 200));
$ink->addInkStroke([new Pdfium\Point(300, 100), new Pdfium\Point(400, 200)]);
drawn($page, 350, 150);
$ink->addInkStroke([new Pdfium\Point(300, 200), new Pdfium\Point(400, 100)]);
drawn($page, 325, 175);
$ink->clearAppearance();
drawn($page, 325, 175);

echo "-- saving\n";
$d = Pdfium\Document::fromString(nav_pdf());
$p = $d->page(0);
$a = $p->addAnnotation('Highlight', $rect);
$a->addQuadPoints($quad);
$a->setColor(255, 0, 0);
var_dump(Pdfium\Document::fromString($d->toString())->page(0)->annotation(0)->appearance());
pixel($p);
$saved = Pdfium\Document::fromString($d->toString())->page(0)->annotation(0);
var_dump($saved->appearance() !== null, $saved->appearance() === $a->appearance(), $saved->color());

echo "-- modes\n";
$sq = $p->addAnnotation('Square', $lower);
$sq->setAppearance('0 0 1 rg 0 0 10 10 re f', Pdfium\Annotation::APPEARANCE_ROLLOVER);
$sq->setAppearance('0 1 0 rg 0 0 10 10 re f', Pdfium\Annotation::APPEARANCE_DOWN);
var_dump($sq->appearance(), $sq->appearance(Pdfium\Annotation::APPEARANCE_ROLLOVER), $sq->appearance(Pdfium\Annotation::APPEARANCE_DOWN));
$sq->setColor(0, 0, 255);
$sq->clearAppearance(Pdfium\Annotation::APPEARANCE_DOWN);
var_dump($sq->appearance(Pdfium\Annotation::APPEARANCE_ROLLOVER), $sq->appearance(Pdfium\Annotation::APPEARANCE_DOWN));
$sq->setAppearance('1 0 0 rg 0 0 10 10 re f');
$sq->clearAppearance();
var_dump($sq->appearance(), $sq->appearance(Pdfium\Annotation::APPEARANCE_ROLLOVER), $sq->hasKey('AP'));
$sq->clearAppearance();

echo "-- empty\n";
$sq->setAppearance('');
var_dump($sq->appearance(), $sq->hasKey('AP'));
attempt(fn() => $sq->setColor(0, 255, 0));
$sq->clearAppearance();
$sq->setColor(0, 255, 0);
echo implode(',', (array) $sq->color()), "\n";

echo "-- encoding\n";
$sq->setAppearance("% é \x7f\n0 0 1 rg 0 0 10 10 re f");
echo bin2hex($sq->appearance()), "\n";
$fixture = Pdfium\Document::fromString(nav_pdf(
    extra: [
        '<< /Type /Annot /Subtype /Square /Rect [0 0 10 10] /AP << /N 7 0 R >> >>',
        form_xobject("% \xe9 \xc3\xa9 \x80\n0 g 0 0 10 10 re f", 10, 10),
    ],
    pageExtra: ['/Annots [6 0 R]', '', ''],
))->page(0)->annotation(0);
echo bin2hex($fixture->appearance()), "\n";
$s2 = $p->addAnnotation('Stamp', new Pdfium\Rect(100, 300, 300, 350));
$s2->setAppearance("% é\n0 g 100 300 200 50 re f");
pixel($p, 200, 325);
$s2->setAppearance("% 日\n0 g 100 300 200 50 re f");
echo $s2->appearance(), "\n";
pixel($p, 200, 325);
attempt(fn() => $sq->setAppearance("\xe9"));
attempt(fn() => $sq->setAppearance("a\0b"));

echo "-- byte order marks\n";
foreach (["\xef\xbb\xbf% \xc3\xa9\n0 g", "\xff\xfe%\0 \0\xe9\0\n\x000\0 \0g\0", "\xfe\xff\0%\0 \0\xe9\0\n\x000\0 \0g"] as $data) {
    $bom = Pdfium\Document::fromString(nav_pdf(
        extra: ['<< /Type /Annot /Subtype /Square /Rect [0 0 10 10] /AP << /N 7 0 R >> >>', form_xobject($data, 10, 10)],
        pageExtra: ['/Annots [6 0 R]', '', ''],
    ))->page(0)->annotation(0);
    echo bin2hex($bom->appearance()), "\n";
}

echo "-- setRect alone\n";
foreach (['Highlight', 'Underline', 'Squiggly', 'StrikeOut', 'Square', 'Highlight with setAppearance'] as $label) {
    $type = explode(' ', $label)[0];
    $page = Pdfium\Document::fromString(nav_pdf())->page(0);
    $m = $page->addAnnotation($type, $rect);
    if ($type !== 'Square') {
        $m->addQuadPoints($quad);
    }
    $m->setColor(255, 0, 0);
    if ($label === 'Highlight with setAppearance') {
        $m->setAppearance('1 0 0 rg 100 650 200 50 re f');
    }
    $before = png_md5($page);
    $m->setRect($lower);
    $afterRect = png_md5($page);
    echo $label, ': setRect ', $afterRect === $before ? 'keeps the drawing' : 'moves the drawing';
    if ($type !== 'Square' && $afterRect === $before) {
        $m->setQuadPoints(0, $lowerQuad);
        echo ', setQuadPoints ', png_md5($page) === $before ? 'keeps it' : 'moves it';
    }
    echo "\n";
    if ($label === 'Highlight') {
        pixel($page);
        pixel($page, 200, 525);
    }
}

echo "-- quad points after a render\n";
function area(float $left, float $bottom, float $right, float $top): Pdfium\Quad
{
    return new Pdfium\Quad($left, $top, $right, $top, $left, $bottom, $right, $bottom);
}
function rendered_highlight(array $quads, bool $own = false): array
{
    $doc = Pdfium\Document::fromString(nav_pdf());
    $page = $doc->page(0);
    $h = $page->addAnnotation('Highlight', new Pdfium\Rect(100, 650, 300, 700));
    foreach ($quads as $q) {
        $h->addQuadPoints($q);
    }
    $h->setColor(255, 0, 0);
    if ($own) {
        $h->setAppearance('1 0 0 rg 100 650 200 50 re f');
    }
    return [$doc, $page, $h, png_md5($page)];
}
[$d, $p, $h, $before] = rendered_highlight([area(100, 650, 300, 700)]);
$h->setQuadPoints(0, area(50, 600, 350, 750));
echo 'enlarged: ', png_md5($p) === $before ? 'unchanged' : 'changed', "\n";
pixel($p, 80, 625);
pixel(Pdfium\Document::fromString($d->toString())->page(0), 80, 625);
$h->clearAppearance();
pixel($p, 80, 625);

[$d, $p, $h, $before] = rendered_highlight([area(100, 650, 300, 700)]);
$h->addQuadPoints(area(100, 500, 300, 550));
echo 'added: ', png_md5($p) === $before ? 'unchanged' : 'changed', "\n";
pixel(Pdfium\Document::fromString($d->toString())->page(0), 200, 525);
$h->clearAppearance();
pixel($p, 200, 525);

[$d, $p, $h, $before] = rendered_highlight([area(100, 650, 300, 700), area(100, 600, 300, 640)]);
$h->setQuadPoints(1, area(100, 500, 300, 550));
echo 'second of two moved down: ', png_md5($p) === $before ? 'unchanged' : 'changed', "\n";

[$d, $p, $h, $before] = rendered_highlight([area(100, 650, 300, 700)]);
$h->setQuadPoints(0, area(100, 500, 340, 580));
echo 'only one moved down and widened: ', png_md5($p) === $before ? 'unchanged' : 'changed', "\n";
pixel($p, 200, 675);
pixel($p, 200, 560);
pixel($p, 320, 540);

[$d, $p, $h, $before] = rendered_highlight([area(100, 650, 300, 700)], true);
$h->setQuadPoints(0, area(100, 500, 300, 550));
echo 'from setAppearance, moved down: ', png_md5($p) === $before ? 'unchanged' : 'changed', "\n";
?>
--EXPECT--
-- highlight
NULL
255,0,0
bool(true)
NULL
Pdfium\PdfiumException: Cannot set the colour: the annotation has an appearance stream; call clearAppearance() first
Pdfium\PdfiumException: Cannot set the colour: the annotation has an appearance stream; call clearAppearance() first
255,0,0
NULL
0,0,255
255,255,255
0,0,255
-- stamp
255,255,255
NULL
string(24) "1 0 0 rg 0 0 200 50 re f"
255,255,255
Pdfium\PdfiumException: Cannot set the colour: the annotation has an appearance stream; call clearAppearance() first
0,255,0
-- border
NULL
NULL
bool(false)
255,255,255
-- ink
drawn
blank
drawn
-- saving
NULL
255,0,0
bool(true)
bool(true)
NULL
-- modes
NULL
string(23) "0 0 1 rg 0 0 10 10 re f"
string(23) "0 1 0 rg 0 0 10 10 re f"
string(23) "0 0 1 rg 0 0 10 10 re f"
NULL
NULL
NULL
bool(false)
-- empty
NULL
bool(true)
Pdfium\PdfiumException: Cannot set the colour: the annotation has an appearance stream; call clearAppearance() first
0,255,0,255
-- encoding
2520c3a9207f0a3020302031207267203020302031302031302072652066
2520c3a920c383c2a920e280a20a302067203020302031302031302072652066
0,0,0
% 日
0 g 100 300 200 50 re f
255,255,255
ValueError: Pdfium\Annotation::setAppearance(): Argument #1 ($stream) must be valid UTF-8
ValueError: Pdfium\Annotation::setAppearance(): Argument #1 ($stream) must not contain any null bytes
-- byte order marks
2520c3a90a302067
2520c3a90a302067
2520c3a90a302067
-- setRect alone
Highlight: setRect keeps the drawing, setQuadPoints moves it
255,255,255
255,0,0
Underline: setRect keeps the drawing, setQuadPoints moves it
Squiggly: setRect keeps the drawing, setQuadPoints moves it
StrikeOut: setRect keeps the drawing, setQuadPoints moves it
Square: setRect moves the drawing
Highlight with setAppearance: setRect moves the drawing
-- quad points after a render
enlarged: unchanged
255,255,255
255,255,255
255,0,0
added: unchanged
255,255,255
255,0,0
second of two moved down: unchanged
only one moved down and widened: changed
255,255,255
255,0,0
255,0,0
from setAppearance, moved down: unchanged
