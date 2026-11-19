--TEST--
Point and Quad can be created with finite coordinates and serialized; Border comes only from an annotation and can be serialized
--EXTENSIONS--
pdfium
--FILE--
<?php
require __DIR__ . '/pdf.inc';
var_dump(new Pdfium\Point(1.5, 2));
$q = new Pdfium\Quad(1, 2, 3, 4, 5, 6, 7, 8.5);
echo quad($q), "\n";
foreach ([
    fn() => new Pdfium\Point(NAN, 0),
    fn() => new Pdfium\Point(0, -INF),
    fn() => new Pdfium\Quad(0, 0, 0, 0, 0, 0, INF, 0),
    fn() => new Pdfium\Quad(NAN, 0, 0, 0, 0, 0, 0, 0),
] as $make) {
    try { $make(); } catch (ValueError $e) { echo $e->getMessage(), "\n"; }
}
try { $q->__construct(0, 0, 0, 0, 0, 0, 0, 0); } catch (Error $e) { echo $e->getMessage(), "\n"; }
$p = new Pdfium\Point(1, 2);
try { $p->__construct(3, 4); } catch (Error $e) { echo $e->getMessage(), "\n"; }
var_dump($p->x, $p->y);

var_dump(unserialize(serialize($p)) == $p, unserialize(serialize($q)) == $q);
$border = Pdfium\Document::fromString(annots_pdf(
    '<< /Type /Annot /Subtype /Square /Rect [0 0 10 10] /Border [0 0 1.5] >>',
))->page(0)->annotation(0)->border();
$copy = unserialize(serialize($border));
var_dump($copy == $border, $copy->width);
?>
--EXPECTF--
object(Pdfium\Point)#%d (2) {
  ["x"]=>
  float(1.5)
  ["y"]=>
  float(2)
}
1 2 3 4 5 6 7 8.5
Pdfium\Point::__construct(): Argument #1 ($x) must be finite
Pdfium\Point::__construct(): Argument #2 ($y) must be finite
Pdfium\Quad::__construct(): Argument #7 ($x4) must be finite
Pdfium\Quad::__construct(): Argument #1 ($x1) must be finite
Cannot modify readonly property Pdfium\Quad::$x1
Cannot modify readonly property Pdfium\Point::$x
float(1)
float(2)
bool(true)
bool(true)
bool(true)
float(1.5)
