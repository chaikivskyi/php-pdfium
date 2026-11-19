--TEST--
Widget annotations can be edited and removed while the form environment is loaded: after an edit or removal every Page draws them as a new Page does, renders without a change are unchanged, a removed field stays in the saved file, and removing, rendering, saving and freeing in any order is safe, also for a Widget written as a direct dictionary; on a /NeedAppearances form a render replaces every appearance of the page's fields, setAppearance() included
--EXTENSIONS--
pdfium
--FILE--
<?php
require __DIR__ . '/pdf.inc';
require __DIR__ . '/png.inc';

echo "-- form.pdf\n";
$doc = Pdfium\Document::fromFile(__DIR__ . '/fixtures/form.pdf');
$page = $doc->page(0);
$other = $doc->page(0);
$first = png_md5($page);
$bare = png_md5($page, false);
var_dump(png_md5($page) === $first, png_md5($other) === $first,
    png_md5(Pdfium\Document::fromFile(__DIR__ . '/fixtures/form.pdf')->page(0)) === $first);
foreach ($page->annotations() as $i => $a) {
    echo $i, ' ', $a->type(), ' ', $a->stringValue('T'), "\n";
}
$w = $page->annotation(0);
$w->setRect(new Pdfium\Rect(100, 500, 400, 550));
$moved = png_md5($page);
var_dump($moved !== $first, $moved === png_md5($other), $moved === png_md5($doc->page(0)));
$page->addAnnotation('Text', new Pdfium\Rect(10, 10, 30, 30))->remove();
var_dump(png_md5($page) === $moved, png_md5($other) === $moved);
$w->remove();
var_dump($page->annotationCount(), png_md5($page) === $bare, png_md5($other) === $bare, png_md5($doc->page(0)) === $bare);
try { $w->type(); } catch (Pdfium\PdfiumException $e) { echo $e->getMessage(), "\n"; }
$saved = $doc->toString();
var_dump(str_contains($saved, 'field1'));
$reopened = Pdfium\Document::fromString($saved)->page(0);
var_dump($reopened->annotationCount(), png_md5($reopened) === $bare);
unset($w, $page, $other, $doc);

function form_pdf(): string
{
    $ap = '/AP << /N << /On 13 0 R /Off 14 0 R >> >>';
    return build_pdf([
        '<< /Type /Catalog /Pages 2 0 R /AcroForm 5 0 R >>',
        '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
        '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Annots [4 0 R 7 0 R 8 0 R 9 0 R 10 0 R 11 0 R] >>',
        '<< /Type /Annot /Subtype /Widget /FT /Tx /T (name) /V (Alice) /Rect [100 700 300 730] /F 4 /DA (/Helv 12 Tf 0 g) /P 3 0 R >>',
        '<< /Fields [4 0 R 6 0 R 10 0 R 11 0 R] /NeedAppearances true /DA (/Helv 12 Tf 0 g) /DR << /Font << /Helv 12 0 R >> >> >>',
        '<< /FT /Btn /Ff 49152 /T (radio) /V /On /Kids [7 0 R 8 0 R 9 0 R] >>',
        "<< /Type /Annot /Subtype /Widget /Parent 6 0 R /Rect [100 600 120 620] /F 4 /AS /Off /P 3 0 R $ap >>",
        "<< /Type /Annot /Subtype /Widget /Parent 6 0 R /Rect [130 600 150 620] /F 4 /AS /On /P 3 0 R $ap >>",
        "<< /Type /Annot /Subtype /Widget /Parent 6 0 R /Rect [160 600 180 620] /F 4 /AS /Off /P 3 0 R $ap >>",
        "<< /Type /Annot /Subtype /Widget /FT /Btn /T (check) /V /On /AS /On /Rect [100 500 120 520] /F 4 /P 3 0 R $ap >>",
        '<< /Type /Annot /Subtype /Widget /FT /Ch /Ff 131072 /T (pick) /Opt [(one) (two)] /V (two) /Rect [100 400 300 430] /F 4 /DA (/Helv 12 Tf 0 g) /P 3 0 R >>',
        '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
        form_xobject('0 g 0 0 20 20 re f', 20, 20),
        form_xobject('', 20, 20),
    ]);
}

echo "-- two Page objects, both drawn\n";
$doc = Pdfium\Document::fromString(form_pdf());
$p1 = $doc->page(0);
$p2 = $doc->page(0);
png_md5($p1);
png_md5($p2);
$list = $p1->annotations();
echo implode(' ', array_map(fn($a) => $a->type(), $list)), "\n";
$list[2]->remove();
png_md5($p1);
png_md5($p2);
$p2->removeAnnotation(0);
png_md5($p1);
unset($p1);
png_md5($p2);
$list[1]->setStringValue('AS', 'On');
$list[1]->clearAppearance();
$list[3]->clearAppearance();
$list[3]->setColor(255, 0, 0);
$list[4]->setRect(new Pdfium\Rect(10, 10, 20, 20));
$list[5]->setAppearance('0 g 10 10 5 5 re f');
png_md5($p2);
var_dump($p2->annotationCount(), png_md5($doc->page(0)) === png_md5($p2));
$saved = $doc->toString();
unset($list, $p2, $doc);
var_dump(Pdfium\Document::fromString($saved)->page(0)->annotationCount());

echo "-- edits of a Widget both Page objects drew\n";
$edits = [
    'AS' => fn($l) => $l[2]->setStringValue('AS', 'On'),
    'V' => fn($l) => $l[0]->setStringValue('V', 'Bob'),
    'clearAppearance' => fn($l) => $l[2]->clearAppearance(),
    'setColor' => function ($l) { $l[4]->clearAppearance(); $l[4]->setColor(255, 0, 0); },
    'setRect' => fn($l) => $l[0]->setRect(new Pdfium\Rect(10, 10, 200, 40)),
    'setRect choice' => fn($l) => $l[5]->setRect(new Pdfium\Rect(10, 10, 20, 20)),
    'setAppearance' => fn($l) => $l[5]->setAppearance('0 g 10 10 5 5 re f'),
    'setFlags' => fn($l) => $l[0]->setFlags(Pdfium\Annotation::FLAG_HIDDEN),
    'Subtype' => fn($l) => $l[0]->setStringValue('Subtype', 'Square'),
];
foreach ($edits as $name => $edit) {
    $doc = Pdfium\Document::fromString(form_pdf());
    $p1 = $doc->page(0);
    $p2 = $doc->page(0);
    $before = png_md5($p1);
    png_md5($p2);
    $edit($p1->annotations());
    $fresh = png_md5($doc->page(0));
    echo $name, ': ', png_md5($p1) === $fresh && png_md5($p2) === $fresh ? 'drawn as a new Page draws it' : 'stale',
        png_md5($p1) === $before ? ', unchanged' : '', "\n";
}

echo "-- every widget removed, then saved\n";
$doc = Pdfium\Document::fromString(form_pdf());
$page = $doc->page(0);
png_md5($page);
for ($i = $page->annotationCount() - 1; $i >= 0; $i--) {
    $page->removeAnnotation($i);
}
var_dump(png_md5($page) === png_md5($page, false));
$fresh = $doc->page(0);
var_dump(png_md5($fresh) === png_md5($fresh, false));
unset($page);
var_dump(Pdfium\Document::fromString($doc->toString())->page(0)->annotationCount());
unset($fresh, $doc);

echo "-- removed before the first render\n";
$doc = Pdfium\Document::fromString(form_pdf());
$page = $doc->page(0);
$page->removeAnnotation(0);
png_md5($page);
var_dump($page->annotationCount());
unset($page, $doc);

echo "-- page freed before the removal\n";
$doc = Pdfium\Document::fromString(form_pdf());
$page = $doc->page(0);
png_md5($page);
$a = $page->annotation(1);
unset($page);
$other = $doc->page(0);
png_md5($other);
$a->remove();
png_md5($other);
var_dump($other->annotationCount());
unset($doc, $other);
try { $a->type(); } catch (Pdfium\PdfiumException $e) { echo $e->getMessage(), "\n"; }
unset($a);

echo "-- removed, then the page, document and annotation freed in turn\n";
$doc = Pdfium\Document::fromString(form_pdf());
$page = $doc->page(0);
png_md5($page);
$a = $page->annotation(0);
$a->remove();
unset($doc);
unset($page);
try { $a->index(); } catch (Pdfium\PdfiumException $e) { echo $e->getMessage(), "\n"; }
unset($a);

echo "-- removed, then split and merged\n";
$doc = Pdfium\Document::fromString(form_pdf());
$page = $doc->page(0);
png_md5($page);
$page->annotation(3)->remove();
png_md5($doc->extractPages([0])->page(0));
$merged = Pdfium\Document::merge([$doc, $doc]);
png_md5($merged->page(1));
var_dump($merged->page(0)->annotationCount(), $merged->page(1)->annotationCount());
unset($merged, $page, $doc);

function direct_pdf(): string
{
    return build_pdf([
        '<< /Type /Catalog /Pages 2 0 R /AcroForm << /Fields [] /NeedAppearances true /DA (/Helv 12 Tf 0 g) '
            . '/DR << /Font << /Helv 4 0 R >> >> >> >>',
        '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
        '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Annots [<< /Type /Annot /Subtype /Widget /FT /Tx /T (d) '
            . '/V (direct) /Rect [100 700 300 730] /F 4 /DA (/Helv 12 Tf 0 g) >>] >>',
        '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
    ]);
}

echo "-- a Widget written as a direct dictionary\n";
foreach (['page first', 'document first'] as $order) {
    $doc = Pdfium\Document::fromString(direct_pdf());
    $page = $doc->page(0);
    $other = $doc->page(0);
    $bare = png_md5($page, false);
    var_dump($page->annotation(0)->type(), png_md5($page) !== $bare, png_md5($other) !== $bare);
    $page->removeAnnotation(0);
    var_dump(png_md5($page) === $bare, png_md5($other) === $bare, png_md5($doc->page(0)) === $bare);
    $saved = $doc->toString();
    if ($order === 'page first') {
        unset($page, $other, $doc);
    } else {
        unset($doc);
        unset($other);
        unset($page);
    }
    var_dump(Pdfium\Document::fromString($saved)->page(0)->annotationCount());
}

function need_pdf(bool $need): string
{
    $field = fn(string $t, int $page, int $y, string $more = '') => "<< /Type /Annot /Subtype /Widget /FT /Tx /T ($t) /V ($t) "
        . "/Rect [100 $y 300 " . ($y + 30) . "] /F 4 /DA (/Helv 12 Tf 0 g) /P $page 0 R $more >>";
    return build_pdf([
        '<< /Type /Catalog /Pages 2 0 R /AcroForm 5 0 R >>',
        '<< /Type /Pages /Kids [3 0 R 4 0 R] /Count 2 >>',
        '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Annots [7 0 R 8 0 R] >>',
        '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Annots [9 0 R 10 0 R] >>',
        '<< /Fields [7 0 R 8 0 R 9 0 R 10 0 R] ' . ($need ? '/NeedAppearances true ' : '')
            . '/DA (/Helv 12 Tf 0 g) /DR << /Font << /Helv 6 0 R >> >> >>',
        '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
        $field('own', 3, 700, '/AP << /N 11 0 R >>'),
        $field('none', 3, 600),
        $field('own2', 4, 700, '/AP << /N 11 0 R >>'),
        $field('none2', 4, 600),
        form_xobject('0 0 1 rg 0 0 200 30 re f', 200, 30),
    ]);
}

echo "-- appearances on a form with and without /NeedAppearances\n";
$kind = fn(?string $s) => match (true) {
    $s === null => 'none',
    $s === '0 0 1 rg 0 0 200 30 re f' => 'own',
    $s === '1 0 0 rg 100 700 200 30 re f' => 'set',
    default => 'generated',
};
foreach ([false, true] as $need) {
    echo $need ? 'with' : 'without', ': ';
    $doc = Pdfium\Document::fromString(need_pdf($need));
    $page = $doc->page(0);
    $page->render(dpi: 36);
    echo implode(' ', array_map(fn($a) => $kind($a->appearance()), [...$page->annotations(), ...$doc->page(1)->annotations()]));
    $own = $page->annotation(0);
    $own->setAppearance('1 0 0 rg 100 700 200 30 re f');
    echo ', set: ', $kind($own->appearance()), ', saved: ', $kind(Pdfium\Document::fromString($doc->toString())->page(0)->annotation(0)->appearance());
    $page->render(dpi: 36);
    echo ', rendered: ', $kind($own->appearance()), ', saved: ', $kind(Pdfium\Document::fromString($doc->toString())->page(0)->annotation(0)->appearance()), "\n";
}

echo "-- a non-Widget removed on a /NeedAppearances form\n";
$doc = Pdfium\Document::fromString(need_pdf(true));
$page = $doc->page(0);
$other = $doc->page(0);
$own = $page->annotation(0);
$own->setAppearance('1 0 0 rg 100 700 200 30 re f');
$note = $page->addAnnotation('Text', new Pdfium\Rect(10, 10, 30, 30));
png_md5($page);
png_md5($other);
$drawn = $own->appearance();
$note->remove();
$after = png_md5($page);
var_dump($kind($drawn), $own->appearance() === $drawn, png_md5($other) === $after, png_md5($doc->page(0)) === $after,
    $page->annotationCount());
$page->removeAnnotation(0);
$fresh = png_md5($doc->page(0));
var_dump($fresh !== $after, png_md5($page) === $fresh, png_md5($other) === $fresh);
?>
--EXPECT--
-- form.pdf
bool(true)
bool(true)
bool(true)
0 Widget field1
bool(true)
bool(true)
bool(true)
bool(true)
bool(true)
int(0)
bool(true)
bool(true)
bool(true)
The annotation was removed from its page
bool(true)
int(0)
bool(true)
-- two Page objects, both drawn
Widget Widget Widget Widget Widget Widget
int(4)
bool(true)
int(4)
-- edits of a Widget both Page objects drew
AS: drawn as a new Page draws it, unchanged
V: drawn as a new Page draws it
clearAppearance: drawn as a new Page draws it
setColor: drawn as a new Page draws it
setRect: drawn as a new Page draws it
setRect choice: drawn as a new Page draws it
setAppearance: drawn as a new Page draws it, unchanged
setFlags: drawn as a new Page draws it
Subtype: drawn as a new Page draws it
-- every widget removed, then saved
bool(true)
bool(true)
int(0)
-- removed before the first render
int(5)
-- page freed before the removal
int(5)
The annotation was removed from its page
-- removed, then the page, document and annotation freed in turn
The annotation was removed from its page
-- removed, then split and merged
int(5)
int(5)
-- a Widget written as a direct dictionary
string(6) "Widget"
bool(true)
bool(true)
bool(true)
bool(true)
bool(true)
int(0)
string(6) "Widget"
bool(true)
bool(true)
bool(true)
bool(true)
bool(true)
int(0)
-- appearances on a form with and without /NeedAppearances
without: own generated own none, set: set, saved: set, rendered: set, saved: set
with: generated generated own none, set: set, saved: set, rendered: generated, saved: generated
-- a non-Widget removed on a /NeedAppearances form
string(9) "generated"
bool(true)
bool(true)
bool(true)
int(2)
bool(true)
bool(true)
bool(true)
