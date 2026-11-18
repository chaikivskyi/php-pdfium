--TEST--
FormField::setValue() rebuilds the appearance of the field's widgets on other pages, or leaves it when memory_limit leaves no room to load them
--EXTENSIONS--
pdfium
--FILE--
<?php
require __DIR__ . '/form.inc';

function many_pdf(int $pages): string
{
    $objs = [
        '<< /Type /Catalog /Pages 2 0 R /AcroForm 3 0 R >>',
        null,
        '<< /Fields [6 0 R] /DA (/Helv 12 Tf 0 g) /DR << /Font << /Helv 5 0 R >> >> >>',
        '<< >>',
        '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
        null,
    ];
    $kids = $widgets = [];
    for ($i = 0; $i < $pages; $i++) {
        $page = count($objs) + 1;
        $kids[] = "$page 0 R";
        $widgets[] = ($page + 1) . ' 0 R';
        $objs[] = "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 400 100] /Annots [" . ($page + 1) . ' 0 R] >>';
        $objs[] = "<< /Type /Annot /Subtype /Widget /Parent 6 0 R /P $page 0 R /F 4 /Rect [100 40 300 60] /AP << /N " . ($page + 2) . ' 0 R >> >>';
        $objs[] = text_ap('old');
    }
    $objs[1] = '<< /Type /Pages /Kids [' . implode(' ', $kids) . "] /Count $pages >>";
    $objs[5] = '<< /FT /Tx /T (shared) /V (old) /Kids [' . implode(' ', $widgets) . '] >>';
    return build_pdf($objs);
}

function png(Pdfium\Document $doc, int $i): string
{
    return $doc->page($i)->render(dpi: 72)->toPng();
}

$untouched = png(Pdfium\Document::fromString(repeat_pdf()), 1);

echo "-- no Page for page 1 alive\n";
$doc = Pdfium\Document::fromString(repeat_pdf());
$doc->page(0)->formFields()[0]->setValue('NEW');
$again = Pdfium\Document::fromString($doc->toString());
var_dump($again->page(0)->formFields()[0]->value(), $again->page(1)->formFields()[0]->value());
var_dump(png($again, 1) === png($again, 0), png($again, 1) !== $untouched);

echo "-- a Page for page 1 alive\n";
$doc = Pdfium\Document::fromString(repeat_pdf());
$p1 = $doc->page(1);
$other = $p1->formFields()[0];
$before = $p1->render(dpi: 72)->toPng();
$doc->page(0)->formFields()[0]->setValue('NEW');
var_dump($other->value());
var_dump(png($doc, 1) === png($doc, 0), png($doc, 1) !== $untouched);
var_dump($p1->render(dpi: 72)->toPng() === png($doc, 0), $p1->render(dpi: 72)->toPng() !== $before);
$again = Pdfium\Document::fromString($doc->toString());
var_dump($again->page(1)->formFields()[0]->value(), png($again, 1) === png($again, 0));

echo "-- a field repeated on 30 pages\n";
$doc = Pdfium\Document::fromString(many_pdf(30));
$old = png($doc, 29);
$doc->page(0)->formFields()[0]->setValue('NEW');
$again = Pdfium\Document::fromString($doc->toString());
$first = png($again, 0);
$same = 0;
foreach ($again as $page) {
    $same += $page->formFields()[0]->value() === 'NEW' && $page->render(dpi: 72)->toPng() === $first;
}
var_dump($same, $first !== $old);

echo "-- no memory left for the list of other pages\n";
$doc = Pdfium\Document::fromString(many_pdf(200));
$f = $doc->page(0)->formFields()[0];
ini_set('memory_limit', (string) (memory_get_usage(true) + (2 << 20) + 800));
$f->setValue('NEW');
ini_set('memory_limit', '-1');
$again = Pdfium\Document::fromString($doc->toString());
var_dump($again->page(199)->formFields()[0]->value(), png($again, 0) !== png($again, 199), png($again, 199) === $old);
?>
--EXPECT--
-- no Page for page 1 alive
string(3) "NEW"
string(3) "NEW"
bool(true)
bool(true)
-- a Page for page 1 alive
string(3) "NEW"
bool(true)
bool(true)
bool(true)
bool(true)
string(3) "NEW"
bool(true)
-- a field repeated on 30 pages
int(30)
bool(true)
-- no memory left for the list of other pages
string(3) "NEW"
bool(true)
bool(true)
