--TEST--
Which of the 13 creatable annotation types get an appearance from render(): first, after setBorder() removes it, and after clearAppearance(); the appearance life cycle without pixel checks; a hidden annotation and the types PDFium cannot add get none
--EXTENSIONS--
pdfium
--FILE--
<?php
require __DIR__ . '/pdf.inc';
$rect = new Pdfium\Rect(100, 650, 300, 700);
$quad = new Pdfium\Quad(100, 700, 300, 700, 100, 650, 300, 650);
foreach (['Text', 'Link', 'FreeText', 'Square', 'Circle', 'Highlight', 'Underline', 'Squiggly', 'StrikeOut', 'Stamp', 'Ink', 'Popup', 'FileAttachment'] as $type) {
    $page = Pdfium\Document::fromString(nav_pdf())->page(0);
    $a = $page->addAnnotation($type, $rect);
    if (in_array($type, ['Link', 'Highlight', 'Underline', 'Squiggly', 'StrikeOut'], true)) {
        $a->addQuadPoints($quad);
    }
    if ($type === 'Ink') {
        $a->addInkStroke([new Pdfium\Point(110, 690), new Pdfium\Point(290, 660)]);
    }
    $a->setColor(255, 0, 0);
    $a->setStringValue('Contents', 'Note');
    $before = $a->appearance();
    $page->render(dpi: 72);
    $generated = $a->appearance() !== null;
    $a->setBorder(0, 0, 2);
    $afterBorder = $a->appearance() !== null;
    $page->render(dpi: 72);
    $rendered = $a->appearance() !== null;
    $a->clearAppearance();
    $cleared = $a->appearance() !== null;
    $page->render(dpi: 72);
    printf("%s: before %s, render %s, setBorder %s, render %s, clearAppearance %s, render %s\n", $type,
        $before === null ? 'none' : 'some', $generated ? 'generated' : 'none', $afterBorder ? 'kept' : 'none',
        $rendered ? 'generated' : 'none', $cleared ? 'kept' : 'none', $a->appearance() !== null ? 'generated' : 'none');
}

echo "-- life cycle\n";
$doc = Pdfium\Document::fromString(nav_pdf());
$page = $doc->page(0);
$h = $page->addAnnotation('Highlight', $rect);
$h->addQuadPoints($quad);
$h->setColor(255, 0, 0);
$page->render(dpi: 72);
var_dump($h->appearance() !== null, $h->color());
attempt(fn() => $h->setColor(0, 0, 255));
attempt(fn() => $h->setInteriorColor(0, 0, 255));
$h->clearAppearance();
attempt(fn() => $h->setColor(0, 0, 255));
$page->render(dpi: 72);
var_dump($h->appearance() !== null);
$saved = Pdfium\Document::fromString($doc->toString())->page(0)->annotation(0);
var_dump($saved->appearance() !== null, $saved->appearance() === $h->appearance());

$s = $page->addAnnotation('Stamp', $rect);
$s->setAppearance('1 0 0 rg 100 650 200 50 re f');
var_dump($s->appearance());
attempt(fn() => $s->setColor(0, 255, 0));
$s->setAppearance('0 0 1 rg 100 650 200 50 re f', Pdfium\Annotation::APPEARANCE_ROLLOVER);
$s->setAppearance('0 1 0 rg 100 650 200 50 re f', Pdfium\Annotation::APPEARANCE_DOWN);
var_dump($s->appearance(Pdfium\Annotation::APPEARANCE_ROLLOVER), $s->appearance(Pdfium\Annotation::APPEARANCE_DOWN));
$s->clearAppearance(Pdfium\Annotation::APPEARANCE_DOWN);
var_dump($s->appearance(), $s->appearance(Pdfium\Annotation::APPEARANCE_ROLLOVER), $s->appearance(Pdfium\Annotation::APPEARANCE_DOWN));
$s->setBorder(0, 0, 1);
var_dump($s->appearance(), $s->appearance(Pdfium\Annotation::APPEARANCE_ROLLOVER), $s->hasKey('AP'));
$s->setAppearance('1 0 0 rg 100 650 200 50 re f');
$s->setAppearance('0 0 1 rg 100 650 200 50 re f', Pdfium\Annotation::APPEARANCE_ROLLOVER);
$s->clearAppearance();
var_dump($s->appearance(), $s->appearance(Pdfium\Annotation::APPEARANCE_ROLLOVER), $s->hasKey('AP'));

$sq = $page->addAnnotation('Square', $rect);
$sq->setAppearance('');
var_dump($sq->appearance(), $sq->hasKey('AP'));
attempt(fn() => $sq->setColor(0, 255, 0));
$sq->clearAppearance();
attempt(fn() => $sq->setColor(0, 255, 0));

$ink = $page->addAnnotation('Ink', $rect);
$ink->addInkStroke([new Pdfium\Point(110, 690), new Pdfium\Point(290, 660)]);
$page->render(dpi: 72);
$before = $ink->appearance();
var_dump($ink->addInkStroke([new Pdfium\Point(110, 660), new Pdfium\Point(290, 690)]), $ink->appearance() === $before);
$ink->removeInkStrokes();
var_dump($ink->inkStrokes(), $ink->appearance() === $before);

echo "-- popup with a parent\n";
$page = Pdfium\Document::fromString(annots_pdf(
    '<< /Type /Annot /Subtype /Text /Rect [10 10 30 30] /Popup 7 0 R /Contents (Note) >>',
    '<< /Type /Annot /Subtype /Popup /Rect [40 10 200 100] /Parent 6 0 R /Open true >>',
))->page(0);
$page->render(dpi: 72);
foreach ($page->annotations() as $a) {
    echo $a->type(), ': ', $a->appearance() === null ? 'none' : 'generated', "\n";
}

echo "-- flags\n";
foreach ([Pdfium\Annotation::FLAG_HIDDEN, Pdfium\Annotation::FLAG_INVISIBLE, Pdfium\Annotation::FLAG_NO_VIEW] as $flag) {
    $page = Pdfium\Document::fromString(nav_pdf())->page(0);
    $a = $page->addAnnotation('Square', $rect);
    $a->setFlags($flag);
    $page->render(dpi: 72);
    echo $flag, ': ', $a->appearance() === null ? 'none' : 'generated', "\n";
}

echo "-- types it cannot add\n";
foreach (['Line', 'Polygon'] as $type) {
    $page = Pdfium\Document::fromString(nav_pdf(
        extra: [
            "<< /Type /Annot /Subtype /$type /Rect [0 0 50 50] /L [0 0 50 50] /Vertices [0 0 50 0 25 50] /AP << /N 7 0 R >> >>",
            form_xobject('0 g 0 0 50 50 re f', 50, 50),
        ],
        pageExtra: ['/Annots [6 0 R]', '', ''],
    ))->page(0);
    $a = $page->annotation(0);
    $before = $a->appearance() === null ? 'none' : 'some';
    $a->clearAppearance();
    $page->render(dpi: 72);
    echo $type, ': before ', $before, ', clearAppearance and render ', $a->appearance() === null ? 'none' : 'generated', "\n";
}

echo "-- setBorder on an appearance another tool wrote\n";
$s = Pdfium\Document::fromString(nav_pdf(
    extra: ['<< /Type /Annot /Subtype /Stamp /Rect [0 0 50 50] /AP << /N 7 0 R /R 7 0 R >> >>', form_xobject('0 g 0 0 50 50 re f', 50, 50)],
    pageExtra: ['/Annots [6 0 R]', '', ''],
))->page(0)->annotation(0);
var_dump($s->appearance(), $s->appearance(Pdfium\Annotation::APPEARANCE_ROLLOVER));
$s->setBorder(0, 0, 1);
var_dump($s->appearance(), $s->appearance(Pdfium\Annotation::APPEARANCE_ROLLOVER), $s->hasKey('AP'));
?>
--EXPECT--
Text: before none, render generated, setBorder none, render generated, clearAppearance none, render generated
Link: before none, render none, setBorder none, render none, clearAppearance none, render none
FreeText: before none, render generated, setBorder none, render generated, clearAppearance none, render generated
Square: before none, render generated, setBorder none, render generated, clearAppearance none, render generated
Circle: before none, render generated, setBorder none, render generated, clearAppearance none, render generated
Highlight: before none, render generated, setBorder none, render generated, clearAppearance none, render generated
Underline: before none, render generated, setBorder none, render generated, clearAppearance none, render generated
Squiggly: before none, render generated, setBorder none, render generated, clearAppearance none, render generated
StrikeOut: before none, render generated, setBorder none, render generated, clearAppearance none, render generated
Stamp: before none, render none, setBorder none, render none, clearAppearance none, render none
Ink: before none, render generated, setBorder none, render generated, clearAppearance none, render generated
Popup: before none, render none, setBorder none, render none, clearAppearance none, render none
FileAttachment: before none, render none, setBorder none, render none, clearAppearance none, render none
-- life cycle
bool(true)
NULL
Pdfium\PdfiumException: Cannot set the colour: the annotation has an appearance stream; call clearAppearance() first
Pdfium\PdfiumException: Cannot set the colour: the annotation has an appearance stream; call clearAppearance() first
no exception
bool(true)
bool(true)
bool(true)
string(28) "1 0 0 rg 100 650 200 50 re f"
Pdfium\PdfiumException: Cannot set the colour: the annotation has an appearance stream; call clearAppearance() first
string(28) "0 0 1 rg 100 650 200 50 re f"
string(28) "0 1 0 rg 100 650 200 50 re f"
string(28) "1 0 0 rg 100 650 200 50 re f"
string(28) "0 0 1 rg 100 650 200 50 re f"
NULL
NULL
NULL
bool(false)
NULL
NULL
bool(false)
NULL
bool(true)
Pdfium\PdfiumException: Cannot set the colour: the annotation has an appearance stream; call clearAppearance() first
no exception
int(1)
bool(true)
array(0) {
}
bool(true)
-- popup with a parent
Text: generated
Popup: none
-- flags
2: none
1: generated
32: generated
-- types it cannot add
Line: before some, clearAppearance and render none
Polygon: before some, clearAppearance and render none
-- setBorder on an appearance another tool wrote
string(18) "0 g 0 0 50 50 re f"
string(18) "0 g 0 0 50 50 re f"
NULL
NULL
bool(false)
