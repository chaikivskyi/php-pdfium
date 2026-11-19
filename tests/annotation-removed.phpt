--TEST--
Annotation::remove() and Page::removeAnnotation() take an annotation off its page: every method of each handle for it throws, the annotations after it move down, other handles stay usable; a saved file keeps the removed annotation's objects only where something else still points to them
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
foreach (['index', 'type', 'rect', 'flags', 'color', 'interiorColor', 'border', 'quadPoints', 'vertices', 'line', 'inkStrokes', 'appearance', 'link', 'remove'] as $m) {
    foreach ([$b, $b2] as $h) {
        try { $h->$m(); } catch (Pdfium\PdfiumException $e) { echo $m, ': ', get_class($e), ': ', $e->getMessage(), "\n"; }
    }
}
foreach (['hasKey', 'valueType', 'stringValue', 'numberValue', 'linkedAnnotation'] as $m) {
    foreach ([$b, $b2] as $h) {
        try { $h->$m('Contents'); } catch (Pdfium\PdfiumException $e) { echo $m, ': ', $e->getMessage(), "\n"; }
    }
}
$setters = [
    'setRect' => [new Pdfium\Rect(0, 0, 5, 5)],
    'setFlags' => [4],
    'setColor' => [1, 2, 3],
    'setInteriorColor' => [1, 2, 3],
    'setBorder' => [0, 0, 1],
    'addQuadPoints' => [new Pdfium\Quad(1, 2, 3, 4, 5, 6, 7, 8)],
    'setQuadPoints' => [0, new Pdfium\Quad(1, 2, 3, 4, 5, 6, 7, 8)],
    'addInkStroke' => [[new Pdfium\Point(1, 2)]],
    'removeInkStrokes' => [],
    'setStringValue' => ['Contents', 'Z'],
    'setAppearance' => ['0 g'],
    'clearAppearance' => [],
    'setUri' => ['https://example.com/'],
];
foreach ($setters as $m => $args) {
    foreach ([$b, $b2] as $h) {
        try { $h->$m(...$args); } catch (Pdfium\PdfiumException $e) { echo $m, ': ', $e->getMessage(), "\n"; }
    }
}
var_dump($c->stringValue('Contents'), $page->annotationCount());
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
$saved = Pdfium\Document::fromString(nav_pdf(
    extra: [
        '<< /Type /Annot /Subtype /FileAttachment /Rect [0 0 10 10] /Contents (REMOVED-CONTENTS) /FS 9 0 R >>',
        '<< /Type /Annot /Subtype /Text /Rect [0 20 10 30] /Contents (REFERENCED-CONTENTS) >>',
        '<< /Type /Annot /Subtype /Popup /Rect [20 20 60 60] /Parent 7 0 R >>',
        file_spec('removed.txt', 10),
        stream_obj('removed file', '/Mark /REMOVED-FILE '),
    ],
    pageExtra: ['/Annots [6 0 R 7 0 R 8 0 R]', '', ''],
));
$page = $saved->page(0);
$page->removeAnnotation(1);
$page->removeAnnotation(0);
$new = $page->addAnnotation('Text', new Pdfium\Rect(0, 40, 10, 50));
$new->setStringValue('Contents', 'NEW-CONTENTS');
$new->remove();
$bytes = $saved->toString();
foreach (['REMOVED-CONTENTS', 'removed.txt', 'REMOVED-FILE', 'REFERENCED-CONTENTS', 'NEW-CONTENTS'] as $marker) {
    echo $marker, ': ', str_contains($bytes, $marker) ? 'kept' : 'gone', "\n";
}
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
link: Pdfium\PdfiumException: The annotation was removed from its page
link: Pdfium\PdfiumException: The annotation was removed from its page
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
linkedAnnotation: The annotation was removed from its page
linkedAnnotation: The annotation was removed from its page
setRect: The annotation was removed from its page
setRect: The annotation was removed from its page
setFlags: The annotation was removed from its page
setFlags: The annotation was removed from its page
setColor: The annotation was removed from its page
setColor: The annotation was removed from its page
setInteriorColor: The annotation was removed from its page
setInteriorColor: The annotation was removed from its page
setBorder: The annotation was removed from its page
setBorder: The annotation was removed from its page
addQuadPoints: The annotation was removed from its page
addQuadPoints: The annotation was removed from its page
setQuadPoints: The annotation was removed from its page
setQuadPoints: The annotation was removed from its page
addInkStroke: The annotation was removed from its page
addInkStroke: The annotation was removed from its page
removeInkStrokes: The annotation was removed from its page
removeInkStrokes: The annotation was removed from its page
setStringValue: The annotation was removed from its page
setStringValue: The annotation was removed from its page
setAppearance: The annotation was removed from its page
setAppearance: The annotation was removed from its page
clearAppearance: The annotation was removed from its page
clearAppearance: The annotation was removed from its page
setUri: The annotation was removed from its page
setUri: The annotation was removed from its page
string(1) "C"
int(2)
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
REMOVED-CONTENTS: gone
removed.txt: gone
REMOVED-FILE: gone
REFERENCED-CONTENTS: kept
NEW-CONTENTS: gone
