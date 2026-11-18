--TEST--
Annotation::remove() and Page::removeAnnotation() take an annotation off its page: every method of each handle for it throws, the annotations after it move down, other handles stay usable
--EXTENSIONS--
pdfium
--FILE--
<?php
require __DIR__ . '/pdf.inc';
$doc = Pdfium\Document::fromString(annots_pdf(
    '<< /Type /Annot /Subtype /Text /Rect [0 0 10 10] /Contents (A) >>',
    '<< /Type /Annot /Subtype /Text /Rect [0 0 10 10] /Contents (B) >>',
    '<< /Type /Annot /Subtype /Text /Rect [0 0 10 10] /Contents (C) >>',
));
$page = $doc->page(0);
[$a, $b, $c] = $page->annotations();
$b2 = $page->annotation(1);
var_dump($b->remove());
var_dump($page->annotationCount(), $a->index(), $c->index(), $c->stringValue('Contents'));
foreach (['index', 'type', 'rect', 'flags', 'color', 'interiorColor', 'border', 'quadPoints', 'vertices', 'line', 'inkStrokes', 'appearance', 'remove'] as $m) {
    foreach ([$b, $b2] as $h) {
        try { $h->$m(); } catch (Pdfium\PdfiumException $e) { echo $m, ': ', get_class($e), ': ', $e->getMessage(), "\n"; }
    }
}
foreach (['hasKey', 'valueType', 'stringValue', 'numberValue'] as $m) {
    foreach ([$b, $b2] as $h) {
        try { $h->$m('Contents'); } catch (Pdfium\PdfiumException $e) { echo $m, ': ', $e->getMessage(), "\n"; }
    }
}
var_dump($page->removeAnnotation(0));
try { $a->type(); } catch (Pdfium\PdfiumException $e) { echo $e->getMessage(), "\n"; }
var_dump($c->index());
foreach ([-1, 1] as $i) {
    try { $page->removeAnnotation($i); } catch (ValueError $e) { echo $e->getMessage(), "\n"; }
}
$saved = Pdfium\Document::fromString($doc->toString())->page(0);
var_dump(count($saved->annotations()), $saved->annotation(0)->stringValue('Contents'));

$other = Pdfium\Document::fromString(annots_pdf(
    '<< /Type /Annot /Subtype /Text /Rect [0 0 10 10] /Contents (X) >>',
    '<< /Type /Annot /Subtype /Text /Rect [0 0 10 10] /Contents (Y) >>',
))->page(0);
$y = $other->annotation(1);
$other->removeAnnotation(0);
var_dump($c->index(), $c->stringValue('Contents'), $y->index());

$n = $page->addAnnotation('Square', new Pdfium\Rect(0, 0, 10, 10));
var_dump($n->index(), $n->type());
$c->remove();
var_dump($n->index(), $n->type(), $page->annotationCount());
try { $c->stringValue('Contents'); } catch (Pdfium\PdfiumException $e) { echo $e->getMessage(), "\n"; }
$n->remove();
try { $page->removeAnnotation(0); } catch (ValueError $e) { echo $e->getMessage(), "\n"; }
try { $n->remove(); } catch (Pdfium\PdfiumException $e) { echo $e->getMessage(), "\n"; }
?>
--EXPECT--
NULL
int(2)
int(0)
int(1)
string(1) "C"
index: Pdfium\PdfiumException: The annotation was removed from its page
index: Pdfium\PdfiumException: The annotation was removed from its page
type: Pdfium\PdfiumException: The annotation was removed from its page
type: Pdfium\PdfiumException: The annotation was removed from its page
rect: Pdfium\PdfiumException: The annotation was removed from its page
rect: Pdfium\PdfiumException: The annotation was removed from its page
flags: Pdfium\PdfiumException: The annotation was removed from its page
flags: Pdfium\PdfiumException: The annotation was removed from its page
color: Pdfium\PdfiumException: The annotation was removed from its page
color: Pdfium\PdfiumException: The annotation was removed from its page
interiorColor: Pdfium\PdfiumException: The annotation was removed from its page
interiorColor: Pdfium\PdfiumException: The annotation was removed from its page
border: Pdfium\PdfiumException: The annotation was removed from its page
border: Pdfium\PdfiumException: The annotation was removed from its page
quadPoints: Pdfium\PdfiumException: The annotation was removed from its page
quadPoints: Pdfium\PdfiumException: The annotation was removed from its page
vertices: Pdfium\PdfiumException: The annotation was removed from its page
vertices: Pdfium\PdfiumException: The annotation was removed from its page
line: Pdfium\PdfiumException: The annotation was removed from its page
line: Pdfium\PdfiumException: The annotation was removed from its page
inkStrokes: Pdfium\PdfiumException: The annotation was removed from its page
inkStrokes: Pdfium\PdfiumException: The annotation was removed from its page
appearance: Pdfium\PdfiumException: The annotation was removed from its page
appearance: Pdfium\PdfiumException: The annotation was removed from its page
remove: Pdfium\PdfiumException: The annotation was removed from its page
remove: Pdfium\PdfiumException: The annotation was removed from its page
hasKey: The annotation was removed from its page
hasKey: The annotation was removed from its page
valueType: The annotation was removed from its page
valueType: The annotation was removed from its page
stringValue: The annotation was removed from its page
stringValue: The annotation was removed from its page
numberValue: The annotation was removed from its page
numberValue: The annotation was removed from its page
NULL
The annotation was removed from its page
int(0)
Pdfium\Page::removeAnnotation(): Argument #1 ($index) must be between 0 and 0
Pdfium\Page::removeAnnotation(): Argument #1 ($index) must be between 0 and 0
int(1)
string(1) "C"
int(0)
string(1) "C"
int(0)
int(1)
string(6) "Square"
int(0)
string(6) "Square"
int(1)
The annotation was removed from its page
Pdfium\Page::removeAnnotation(): Argument #1 ($index) must be an annotation index, but the page has no annotations
The annotation was removed from its page
