--TEST--
Page::addAnnotation() appends an annotation of each type PDFium creates, with the rectangle and no appearance, and saves it; other types, unknown names and an unset Rect are refused and add nothing
--EXTENSIONS--
pdfium
--FILE--
<?php
require __DIR__ . '/pdf.inc';
$doc = Pdfium\Document::fromString(nav_pdf());
$page = $doc->page(0);
$rect = new Pdfium\Rect(100, 650, 300, 700);
foreach (['Text', 'Link', 'FreeText', 'Square', 'Circle', 'Highlight', 'Underline', 'Squiggly', 'StrikeOut', 'Stamp', 'Ink', 'Popup', 'FileAttachment'] as $i => $t) {
    $a = $page->addAnnotation($t, $rect);
    var_dump(get_class($a), $a->type() === $t, $a->index() === $i, $a->rect() == $rect, $a->appearance());
}
var_dump($page->annotation(0)->flags());
var_dump($page->annotationCount());
foreach (['Line', 'Polygon', 'PolyLine', 'Caret', 'Redact', 'Widget', 'Sound'] as $t) {
    try { $page->addAnnotation($t, $rect); } catch (Pdfium\UnsupportedException $e) { echo $e->getMessage(), "\n"; }
}
foreach (['highlight', '', 'Unknown', "Text\0", 'Bogus'] as $t) {
    try { $page->addAnnotation($t, $rect); } catch (ValueError $e) { echo $e->getMessage(), "\n"; }
}
$unset = (new ReflectionClass(Pdfium\Rect::class))->newInstanceWithoutConstructor();
try { $page->addAnnotation('Text', $unset); } catch (ValueError $e) { echo $e->getMessage(), "\n"; }
var_dump($page->annotationCount());
$again = Pdfium\Document::fromString($doc->toString())->page(0);
echo implode(' ', array_map(fn($a) => $a->type(), $again->annotations())), "\n";
var_dump($again->annotation(12)->rect() == $rect);
?>
--EXPECT--
string(17) "Pdfium\Annotation"
bool(true)
bool(true)
bool(true)
NULL
string(17) "Pdfium\Annotation"
bool(true)
bool(true)
bool(true)
NULL
string(17) "Pdfium\Annotation"
bool(true)
bool(true)
bool(true)
NULL
string(17) "Pdfium\Annotation"
bool(true)
bool(true)
bool(true)
NULL
string(17) "Pdfium\Annotation"
bool(true)
bool(true)
bool(true)
NULL
string(17) "Pdfium\Annotation"
bool(true)
bool(true)
bool(true)
NULL
string(17) "Pdfium\Annotation"
bool(true)
bool(true)
bool(true)
NULL
string(17) "Pdfium\Annotation"
bool(true)
bool(true)
bool(true)
NULL
string(17) "Pdfium\Annotation"
bool(true)
bool(true)
bool(true)
NULL
string(17) "Pdfium\Annotation"
bool(true)
bool(true)
bool(true)
NULL
string(17) "Pdfium\Annotation"
bool(true)
bool(true)
bool(true)
NULL
string(17) "Pdfium\Annotation"
bool(true)
bool(true)
bool(true)
NULL
string(17) "Pdfium\Annotation"
bool(true)
bool(true)
bool(true)
NULL
int(0)
int(13)
PDFium cannot create Line annotations; it creates Text, Link, FreeText, Square, Circle, Highlight, Underline, Squiggly, StrikeOut, Stamp, Ink, Popup and FileAttachment
PDFium cannot create Polygon annotations; it creates Text, Link, FreeText, Square, Circle, Highlight, Underline, Squiggly, StrikeOut, Stamp, Ink, Popup and FileAttachment
PDFium cannot create PolyLine annotations; it creates Text, Link, FreeText, Square, Circle, Highlight, Underline, Squiggly, StrikeOut, Stamp, Ink, Popup and FileAttachment
PDFium cannot create Caret annotations; it creates Text, Link, FreeText, Square, Circle, Highlight, Underline, Squiggly, StrikeOut, Stamp, Ink, Popup and FileAttachment
PDFium cannot create Redact annotations; it creates Text, Link, FreeText, Square, Circle, Highlight, Underline, Squiggly, StrikeOut, Stamp, Ink, Popup and FileAttachment
PDFium cannot create Widget annotations; it creates Text, Link, FreeText, Square, Circle, Highlight, Underline, Squiggly, StrikeOut, Stamp, Ink, Popup and FileAttachment
PDFium cannot create Sound annotations; it creates Text, Link, FreeText, Square, Circle, Highlight, Underline, Squiggly, StrikeOut, Stamp, Ink, Popup and FileAttachment
Pdfium\Page::addAnnotation(): Argument #1 ($type) must be an annotation type name, such as "Highlight"
Pdfium\Page::addAnnotation(): Argument #1 ($type) must be an annotation type name, such as "Highlight"
Pdfium\Page::addAnnotation(): Argument #1 ($type) must be an annotation type name, such as "Highlight"
Pdfium\Page::addAnnotation(): Argument #1 ($type) must be an annotation type name, such as "Highlight"
Pdfium\Page::addAnnotation(): Argument #1 ($type) must be an annotation type name, such as "Highlight"
Pdfium\Page::addAnnotation(): Argument #2 ($rect) must have every side set
int(13)
Text Link FreeText Square Circle Highlight Underline Squiggly StrikeOut Stamp Ink Popup FileAttachment
bool(true)
