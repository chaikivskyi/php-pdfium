--TEST--
Annotation::linkedAnnotation() follows a key such as Popup or IRT to an annotation of the same page; null when there is none, it is not on this page or it has no /Type /Annot
--EXTENSIONS--
pdfium
--FILE--
<?php
require __DIR__ . '/pdf.inc';
$doc = Pdfium\Document::fromString(nav_pdf(
    extra: [
        '<< /Type /Annot /Subtype /Text /Rect [0 0 10 10] /Contents (first) /Popup 7 0 R >>',
        '<< /Type /Annot /Subtype /Popup /Rect [20 0 120 50] /Parent 6 0 R >>',
        '<< /Type /Annot /Subtype /Text /Rect [0 20 10 30] /Contents (reply) /IRT 6 0 R /RT /R >>',
        '<< /Type /Annot /Subtype /Text /Rect [0 40 10 50] /IRT 10 0 R /Str (text) >>',
        '<< /Type /Annot /Subtype /Text /Rect [0 0 10 10] /Contents (on page 2) >>',
        '<< /Type /Annot /Subtype /Text /Rect [0 60 10 70] /IRT 12 0 R >>',
        '<< /Type /Annot /Subtype /Text /Rect [0 0 10 10] /Contents (nowhere) >>',
        '<< /Type /Annot /Subtype /Text /Rect [0 80 10 90] /Contents (self) /IRT 13 0 R >>',
    ],
    pageExtra: ['/Annots [6 0 R 7 0 R 8 0 R 9 0 R 11 0 R 13 0 R]', '/Annots [10 0 R]', ''],
));
$page = $doc->page(0);
[$first, $popup, $reply, $offPage, $unlisted, $self] = $page->annotations();

$p = $first->linkedAnnotation('Popup');
var_dump($p->type(), $p->index(), $p->rect() == $popup->rect());
var_dump($p->linkedAnnotation('Parent')->stringValue('Contents'));
$irt = $reply->linkedAnnotation('IRT');
var_dump($irt->stringValue('Contents'), $irt->index());

var_dump($first->linkedAnnotation('IRT'), $offPage->linkedAnnotation('Str'), $reply->linkedAnnotation('RT'));
var_dump($offPage->linkedAnnotation('IRT'), $unlisted->linkedAnnotation('IRT'));
var_dump($doc->page(1)->annotation(0)->stringValue('Contents'));
$s = $self->linkedAnnotation('IRT');
var_dump($s->index(), $s->index() === $self->index(), $s->stringValue('Contents'));

$irt->setStringValue('Contents', 'changed');
var_dump($first->stringValue('Contents'), $page->annotation(0)->stringValue('Contents'));
$first->setStringValue('Contents', 'again');
var_dump($irt->stringValue('Contents'));

$page->removeAnnotation(1);
try { $p->type(); } catch (Pdfium\PdfiumException $e) { echo $e->getMessage(), "\n"; }
var_dump($first->linkedAnnotation('Popup'), $reply->linkedAnnotation('IRT')->index());

foreach (['', "a\0b"] as $k) {
    try { $first->linkedAnnotation($k); } catch (ValueError $e) { echo $e->getMessage(), "\n"; }
}
$untyped = Pdfium\Document::fromString(annots_pdf(
    '<< /Subtype /Text /Rect [0 0 10 10] /Popup 7 0 R >>',
    '<< /Subtype /Popup /Rect [20 0 120 50] /Parent 8 0 R >>',
    '<< /Type /Annot /Subtype /Text /Rect [0 20 10 30] /Popup 7 0 R >>',
))->page(0)->annotations();
var_dump($untyped[0]->linkedAnnotation('Popup'), $untyped[1]->linkedAnnotation('Parent')->index(), $untyped[2]->linkedAnnotation('Popup'));
?>
--EXPECT--
string(5) "Popup"
int(1)
bool(true)
string(5) "first"
string(5) "first"
int(0)
NULL
NULL
NULL
NULL
NULL
string(9) "on page 2"
int(5)
bool(true)
string(4) "self"
string(7) "changed"
string(7) "changed"
string(5) "again"
The annotation was removed from its page
NULL
int(0)
Pdfium\Annotation::linkedAnnotation(): Argument #1 ($key) must not be empty
Pdfium\Annotation::linkedAnnotation(): Argument #1 ($key) must not contain a null byte
NULL
int(2)
NULL
