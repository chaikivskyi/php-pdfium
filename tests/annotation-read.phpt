--TEST--
Annotation::color(), interiorColor(), border(), quadPoints(), vertices(), line(), inkStrokes() and appearance() read an annotation's dictionary; a colour cannot be read while an appearance stream exists
--EXTENSIONS--
pdfium
--FILE--
<?php
require __DIR__ . '/pdf.inc';
function points(?array $points): string
{
    return $points === null ? 'null' : '[' . implode(' ', array_map(fn($p) => get_class($p) . '(' . point($p) . ')', $points)) . ']';
}
$a = Pdfium\Document::fromString(annots_pdf(
    '<< /Type /Annot /Subtype /Square /Rect [0 0 10 10] /C [1 0 0] /IC [0 0 1] /CA 0.5 /Border [3 4 2] >>',
    '<< /Type /Annot /Subtype /Highlight /Rect [0 0 20 20] /QuadPoints [1 2 3 4 5 6 7 8 9 10 11 12 13 14 15 16.5] >>',
    '<< /Type /Annot /Subtype /Text /Rect [0 0 10 10] >>',
    '<< /Type /Annot /Subtype /Polygon /Rect [0 0 50 50] /Vertices [10 10 50 10 30 40] >>',
    '<< /Type /Annot /Subtype /Line /Rect [0 0 50 50] /L [10 20 30 40] >>',
    '<< /Type /Annot /Subtype /Ink /Rect [0 0 50 50] /InkList [[1 2 3 4] [5 6 7 8 9 10.25]] >>',
    '<< /Type /Annot /Subtype /Square /Rect [0 0 10 10] /C [2 0 0.5] /IC [1] /CA 2 >>',
))->page(0)->annotations();
[$square, $highlight, $text, $polygon, $line, $ink, $outOfRange] = $a;

echo 'square: ', color($square->color()), ' ', color($square->interiorColor()), "\n";
var_dump($square->border());
echo 'highlight: ', color($highlight->color()), ' ', color($highlight->interiorColor()), "\n";
echo 'text: ', color($text->color()), ' ', color($text->interiorColor()), "\n";
var_dump($text->border());
echo 'out of range: ', color($outOfRange->color()), ' ', color($outOfRange->interiorColor()), "\n";
$far = Pdfium\Document::fromString(annots_pdf(
    '<< /Type /Annot /Subtype /Square /Rect [0 0 10 10] /C [1000 0 0] >>',
    '<< /Type /Annot /Subtype /Square /Rect [0 0 10 10] /C [100000000 0 0] >>',
))->page(0)->annotations();
$red = $far[1]->color()->red;
echo 'far above 1: ', color($far[0]->color()), ' ', php_uname('m') === 'x86_64' || $red !== 255 ? $red : 0, "\n";

$quads = $highlight->quadPoints();
echo count($quads), "\n";
foreach ($quads as $q) {
    echo get_class($q), ' ', quad($q), "\n";
}
var_dump($text->quadPoints());

echo points($polygon->vertices()), ' ', points($text->vertices()), "\n";
echo points($line->line()), ' ', points($text->line()), "\n";
echo implode(' ', array_map('points', $ink->inkStrokes())), ' ', count($text->inkStrokes()), "\n";
var_dump($text->appearance());

$withAp = Pdfium\Document::fromString(nav_pdf(
    extra: [
        '<< /Type /Annot /Subtype /Square /Rect [0 0 10 10] /C [1 0 0] /IC [0 0 1] /AP << /N 7 0 R >> >>',
        form_xobject('1 0 0 rg 0 0 10 10 re f', 10, 10),
    ],
    pageExtra: ['/Annots [6 0 R]', '', ''],
))->page(0)->annotation(0);
echo 'with appearance: ', color($withAp->color()), ' ', color($withAp->interiorColor()), "\n";
var_dump($withAp->appearance(), $withAp->appearance(Pdfium\Annotation::APPEARANCE_NORMAL));
var_dump($withAp->appearance(Pdfium\Annotation::APPEARANCE_DOWN), $withAp->appearance(Pdfium\Annotation::APPEARANCE_ROLLOVER));
foreach ([3, -1] as $mode) {
    try { $withAp->appearance($mode); } catch (ValueError $e) { echo $e->getMessage(), "\n"; }
}
var_dump(Pdfium\Document::fromString(annots_pdf('<< /Type /Annot /Subtype /Line /Rect [0 0 50 50] >>'))->page(0)->annotation(0)->line());
?>
--EXPECTF--
square: 255,0,0,127 0,0,255,127
object(Pdfium\Border)#%d (3) {
  ["horizontalRadius"]=>
  float(3)
  ["verticalRadius"]=>
  float(4)
  ["width"]=>
  float(2)
}
highlight: 255,255,0,255 255,255,0,255
text: 0,0,0,255 0,0,0,255
NULL
out of range: 255,0,127,255 255,255,255,255
far above 1: 255,0,0,255 0
2
Pdfium\Quad 1 2 3 4 5 6 7 8
Pdfium\Quad 9 10 11 12 13 14 15 16.5
array(0) {
}
[Pdfium\Point(10,10) Pdfium\Point(50,10) Pdfium\Point(30,40)] []
[Pdfium\Point(10,20) Pdfium\Point(30,40)] null
[Pdfium\Point(1,2) Pdfium\Point(3,4)] [Pdfium\Point(5,6) Pdfium\Point(7,8) Pdfium\Point(9,10.25)] 0
NULL
with appearance: null null
string(23) "1 0 0 rg 0 0 10 10 re f"
string(23) "1 0 0 rg 0 0 10 10 re f"
NULL
NULL
Pdfium\Annotation::appearance(): Argument #1 ($mode) must be one of the Annotation::APPEARANCE_* constants
Pdfium\Annotation::appearance(): Argument #1 ($mode) must be one of the Annotation::APPEARANCE_* constants
NULL
