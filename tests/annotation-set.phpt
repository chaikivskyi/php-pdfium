--TEST--
Annotation::setRect(), setFlags(), setColor(), setInteriorColor(), setBorder(), addQuadPoints(), setQuadPoints() and setStringValue() change the annotation in place and are saved; bad arguments and refusals throw
--EXTENSIONS--
pdfium
--FILE--
<?php
require __DIR__ . '/pdf.inc';
function show(Pdfium\Annotation $h): void
{
    $r = $h->rect();
    $b = $h->border();
    echo "rect $r->left,$r->bottom,$r->right,$r->top flags ", $h->flags(), ' color ', color($h->color()), "\n";
    echo 'border ', $b ? "$b->horizontalRadius,$b->verticalRadius,$b->width" : 'null', "\n";
    foreach ($h->quadPoints() as $q) {
        echo 'quad ', quad($q), "\n";
    }
    echo 'Contents ', $h->stringValue('Contents'), ' (', $h->valueType('Contents'), ') T ', $h->stringValue('T'), "\n";
}

$doc = Pdfium\Document::fromString(nav_pdf());
$page = $doc->page(0);
$h = $page->addAnnotation('Highlight', new Pdfium\Rect(100, 650, 300, 700));
$h->setRect(new Pdfium\Rect(50, 600, 250, 640));
$h->setFlags(Pdfium\Annotation::FLAG_PRINT | Pdfium\Annotation::FLAG_LOCKED);
$h->setColor(255, 128, 0, 128);
$h->setBorder(1.5, 2.5, 3);
$h->addQuadPoints(new Pdfium\Quad(50, 640, 250, 640, 50, 600, 250, 600));
$h->addQuadPoints(new Pdfium\Quad(50, 590, 250, 590, 50, 560, 250, 560));
$h->setQuadPoints(1, new Pdfium\Quad(60, 590, 240, 590, 60, 560, 240, 560));
$h->setStringValue('Contents', 'naïve — 日本語');
$h->setStringValue('T', 'Me');
show($h);
$s = $page->addAnnotation('Square', new Pdfium\Rect(100, 400, 200, 500));
$s->setInteriorColor(0, 0, 255);
echo 'square ', color($s->color()), ' ', color($s->interiorColor()), "\n";
$c = $page->addAnnotation('Circle', new Pdfium\Rect(300, 400, 400, 500));
$c->setColor(255, 0, 0, 64);
echo 'circle ', color($c->color()), ' ', color($c->interiorColor()), "\n";
$c->setInteriorColor(0, 0, 255);
echo 'circle ', color($c->color()), ' ', color($c->interiorColor()), "\n";
$h->setFlags(4294967295);
echo $h->flags(), "\n";
$h->setFlags(Pdfium\Annotation::FLAG_PRINT | Pdfium\Annotation::FLAG_LOCKED);

$again = Pdfium\Document::fromString($doc->toString())->page(0)->annotations();
show($again[0]);
echo 'square ', color($again[1]->color()), ' ', color($again[1]->interiorColor()), "\n";

$quad = new Pdfium\Quad(1, 2, 3, 4, 5, 6, 7, 8);
$text = $page->addAnnotation('Text', new Pdfium\Rect(10, 10, 30, 30));
$unsetQuad = (new ReflectionClass(Pdfium\Quad::class))->newInstanceWithoutConstructor();
$serialized = serialize($quad);
$nanQuad = unserialize(substr_replace($serialized, 'd:NAN;', strpos($serialized, 'd:1;'), 4));
$unsetRect = (new ReflectionClass(Pdfium\Rect::class))->newInstanceWithoutConstructor();
echo_outcome(fn() => $h->setColor(256, 0, 0));
echo_outcome(fn() => $h->setColor(0, -1, 0));
echo_outcome(fn() => $h->setColor(0, 0, 0, -1));
echo_outcome(fn() => $h->setInteriorColor(0, 0, 256));
echo_outcome(fn() => $h->setFlags(-1));
echo_outcome(fn() => $h->setFlags(2 ** 32));
echo_outcome(fn() => $h->setBorder(-1, 0, 0));
echo_outcome(fn() => $h->setBorder(0, NAN, 0));
echo_outcome(fn() => $h->setBorder(0, 0, INF));
echo_outcome(fn() => $h->setQuadPoints(2, $quad));
echo_outcome(fn() => $h->setQuadPoints(-1, $quad));
echo_outcome(fn() => $text->setQuadPoints(0, $quad));
echo_outcome(fn() => $text->addQuadPoints($quad));
echo_outcome(fn() => $page->addAnnotation('Underline', new Pdfium\Rect(0, 0, 10, 10))->setQuadPoints(0, $quad));
echo_outcome(fn() => $h->addQuadPoints($unsetQuad));
echo_outcome(fn() => $h->setQuadPoints(0, $unsetQuad));
echo_outcome(fn() => $h->addQuadPoints($nanQuad));
echo_outcome(fn() => $h->setStringValue('', 'x'));
echo_outcome(fn() => $h->setStringValue("T\0", 'x'));
echo_outcome(fn() => $h->setStringValue('Contents', "\xff"));
echo_outcome(fn() => $h->setStringValue('Contents', "a\0b"));
echo_outcome(fn() => $h->setRect($unsetRect));
echo_outcome(fn() => $h->setAppearance('x', 7));
echo_outcome(fn() => $h->setAppearance('x', -1));
echo_outcome(fn() => $h->clearAppearance(3));
echo_outcome(fn() => $page->addAnnotation('Stamp', new Pdfium\Rect(10, 10, 10, 30))->setAppearance('0 g'));
show($h);
$sub = $page->addAnnotation('Square', new Pdfium\Rect(100, 400, 200, 500));
$sub->setStringValue('Subtype', 'Square');
var_dump($sub->type(), $sub->valueType('Subtype'));
foreach (["þÿAB", "ÿþAB", "ï»¿x", "a\x1bb"] as $value) {
    $text->setStringValue('Contents', $value);
    var_dump($text->stringValue('Contents'));
}
?>
--EXPECT--
rect 50,600,250,640 flags 132 color 255,128,0,128
border 1.5,2.5,3
quad 50 640 250 640 50 600 250 600
quad 60 590 240 590 60 560 240 560
Contents naïve — 日本語 (String) T Me
square 0,0,0,255 0,0,255,255
circle 255,0,0,64 0,0,0,64
circle 255,0,0,255 0,0,255,255
4294967295
rect 50,600,250,640 flags 132 color 255,128,0,128
border 1.5,2.5,3
quad 50 640 250 640 50 600 250 600
quad 60 590 240 590 60 560 240 560
Contents naïve — 日本語 (String) T Me
square 0,0,0,255 0,0,255,255
ValueError: Pdfium\Annotation::setColor(): Argument #1 ($red) must be between 0 and 255
ValueError: Pdfium\Annotation::setColor(): Argument #2 ($green) must be between 0 and 255
ValueError: Pdfium\Annotation::setColor(): Argument #4 ($alpha) must be between 0 and 255
ValueError: Pdfium\Annotation::setInteriorColor(): Argument #3 ($blue) must be between 0 and 255
ValueError: Pdfium\Annotation::setFlags(): Argument #1 ($flags) must be between 0 and 4294967295
ValueError: Pdfium\Annotation::setFlags(): Argument #1 ($flags) must be between 0 and 4294967295
ValueError: Pdfium\Annotation::setBorder(): Argument #1 ($horizontalRadius) must be finite and not negative
ValueError: Pdfium\Annotation::setBorder(): Argument #2 ($verticalRadius) must be finite and not negative
ValueError: Pdfium\Annotation::setBorder(): Argument #3 ($width) must be finite and not negative
ValueError: Pdfium\Annotation::setQuadPoints(): Argument #1 ($index) must be between 0 and 1
ValueError: Pdfium\Annotation::setQuadPoints(): Argument #1 ($index) must be between 0 and 1
Pdfium\PdfiumException: Cannot set the quad points of a Text annotation
Pdfium\PdfiumException: Cannot add quad points to a Text annotation
ValueError: Pdfium\Annotation::setQuadPoints(): Argument #1 ($index) must be an index of the quad points, but the annotation has none
ValueError: Pdfium\Annotation::addQuadPoints(): Argument #1 ($quad) must have every coordinate set
ValueError: Pdfium\Annotation::setQuadPoints(): Argument #2 ($quad) must have every coordinate set
ValueError: Pdfium\Annotation::addQuadPoints(): Argument #1 ($quad) must have finite coordinates
ValueError: Pdfium\Annotation::setStringValue(): Argument #1 ($key) must not be empty
ValueError: Pdfium\Annotation::setStringValue(): Argument #1 ($key) must not contain a null byte
ValueError: Pdfium\Annotation::setStringValue(): Argument #2 ($value) must be valid UTF-8
ValueError: Pdfium\Annotation::setStringValue(): Argument #2 ($value) must not contain any null bytes
ValueError: Pdfium\Annotation::setRect(): Argument #1 ($rect) must have every side set
ValueError: Pdfium\Annotation::setAppearance(): Argument #2 ($mode) must be one of the Annotation::APPEARANCE_* constants
ValueError: Pdfium\Annotation::setAppearance(): Argument #2 ($mode) must be one of the Annotation::APPEARANCE_* constants
ValueError: Pdfium\Annotation::clearAppearance(): Argument #1 ($mode) must be one of the Annotation::APPEARANCE_* constants
Pdfium\PdfiumException: Cannot set the appearance of a Stamp annotation
rect 50,600,250,640 flags 132 color 255,128,0,128
border 1.5,2.5,3
quad 50 640 250 640 50 600 250 600
quad 60 590 240 590 60 560 240 560
Contents naïve — 日本語 (String) T Me
string(7) "Unknown"
string(6) "String"
string(3) "䅂"
string(3) "䉁"
string(1) "x"
string(1) "a"
