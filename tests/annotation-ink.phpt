--TEST--
Annotation::addInkStroke() adds a stroke to an Ink annotation and returns its index, removeInkStrokes() drops them all; bad points and other types throw
--EXTENSIONS--
pdfium
--FILE--
<?php
require __DIR__ . '/pdf.inc';
function strokes(Pdfium\Annotation $a): string
{
    return json_encode(array_map(fn($s) => array_map(fn($p) => [$p->x, $p->y], $s), $a->inkStrokes()));
}
$doc = Pdfium\Document::fromString(nav_pdf());
$page = $doc->page(0);
$ink = $page->addAnnotation('Ink', new Pdfium\Rect(100, 650, 300, 700));
var_dump($ink->addInkStroke([new Pdfium\Point(110, 690), new Pdfium\Point(200, 660), new Pdfium\Point(290, 690)]));
var_dump($ink->addInkStroke(['a' => new Pdfium\Point(120.5, 655), 'b' => new Pdfium\Point(280, 655)]));
echo strokes($ink), "\n";
echo strokes(Pdfium\Document::fromString($doc->toString())->page(0)->annotation(0)), "\n";
$ink->removeInkStrokes();
var_dump($ink->inkStrokes());
var_dump($ink->addInkStroke([new Pdfium\Point(1, 2)]));
echo strokes($ink), "\n";

$unset = (new ReflectionClass(Pdfium\Point::class))->newInstanceWithoutConstructor();
$nan = unserialize(str_replace('d:1;', 'd:NAN;', serialize(new Pdfium\Point(1, 2))));
$text = $page->addAnnotation('Text', new Pdfium\Rect(10, 10, 30, 30));
echo_outcome(fn() => $ink->addInkStroke([]));
echo_outcome(fn() => $ink->addInkStroke([1]));
echo_outcome(fn() => $ink->addInkStroke([new Pdfium\Point(1, 2), new Pdfium\Rect(0, 0, 1, 1)]));
echo_outcome(fn() => $ink->addInkStroke([new Pdfium\Point(1, 2), $unset]));
echo_outcome(fn() => $ink->addInkStroke([$nan]));
echo_outcome(fn() => $ink->addInkStroke([$unset, new Pdfium\Rect(0, 0, 1, 1)]));
echo_outcome(fn() => $ink->addInkStroke([new Pdfium\Point(1, 2), $nan, $unset]));
echo_outcome(fn() => $text->addInkStroke([new Pdfium\Point(1, 2)]));
echo_outcome(fn() => $text->removeInkStrokes());
echo strokes($ink), "\n";
?>
--EXPECT--
int(0)
int(1)
[[[110,690],[200,660],[290,690]],[[120.5,655],[280,655]]]
[[[110,690],[200,660],[290,690]],[[120.5,655],[280,655]]]
array(0) {
}
int(0)
[[[1,2]]]
ValueError: Pdfium\Annotation::addInkStroke(): Argument #1 ($points) must not be empty
TypeError: Pdfium\Annotation::addInkStroke(): Argument #1 ($points) must be a list of Pdfium\Point
TypeError: Pdfium\Annotation::addInkStroke(): Argument #1 ($points) must be a list of Pdfium\Point
ValueError: Pdfium\Annotation::addInkStroke(): Argument #1 ($points) must have every coordinate set
ValueError: Pdfium\Annotation::addInkStroke(): Argument #1 ($points) must have finite coordinates
TypeError: Pdfium\Annotation::addInkStroke(): Argument #1 ($points) must be a list of Pdfium\Point
ValueError: Pdfium\Annotation::addInkStroke(): Argument #1 ($points) must have finite coordinates
Pdfium\PdfiumException: Cannot add an ink stroke to a Text annotation
Pdfium\PdfiumException: Cannot remove the ink strokes of a Text annotation
[[[1,2]]]
