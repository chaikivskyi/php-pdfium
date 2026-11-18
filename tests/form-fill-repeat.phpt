--TEST--
FormField::setValue() rebuilds the appearance of the field's widgets on other pages
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

foreach ([[0, 1], [1, 0]] as [$via, $other]) {
    echo "-- filled through page $via, a Page for page $other listed and rendered before\n";
    $doc = Pdfium\Document::fromString(repeat_pdf());
    $old = $doc->page($other);
    $oldField = $old->formFields()[0];
    $shots = ['old' => $old->render(dpi: 72)->toPng()];
    $field = $doc->page($via)->formFields()[0];
    foreach (['A', 'B', 'C', 'D'] as $v) {
        if ($v === 'D') {
            echo "through the old Page's field\n";
            $oldField->setValue($v);
        } else {
            $field->setValue($v);
        }
        $shots[$v] = png($doc, $via);
        echo json_encode([$v, $field->value(), $oldField->value(), png($doc, $other) === $shots[$v],
            array_search($old->render(dpi: 72)->toPng(), $shots, true)]), "\n";
    }
    $again = Pdfium\Document::fromString($doc->toString());
    echo json_encode([$again->page(0)->formFields()[0]->value(), $again->page(1)->formFields()[0]->value(),
        png($again, 0) === $shots['D'], png($again, 1) === $shots['D']]), "\n";
    unset($doc, $old, $again);
    var_dump($field->value());
    unset($field);
    var_dump($oldField->value());
    unset($oldField);
}

echo "-- the only Page for page 1, rendered before\n";
$doc = Pdfium\Document::fromString(repeat_pdf());
$only = $doc->page(1);
$only->render(dpi: 72);
$field = $doc->page(0)->formFields()[0];
foreach (['A', 'B'] as $v) {
    $field->setValue($v);
    var_dump($only->render(dpi: 72)->toPng() === png($doc, 0));
}
unset($doc, $only, $field);

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
?>
--EXPECT--
-- no Page for page 1 alive
string(3) "NEW"
string(3) "NEW"
bool(true)
bool(true)
-- filled through page 0, a Page for page 1 listed and rendered before
["A","A","A",true,"A"]
["B","B","B",true,"A"]
["C","C","C",true,"A"]
through the old Page's field
["D","D","D",true,"D"]
["D","D",true,true]
string(1) "D"
string(1) "D"
-- filled through page 1, a Page for page 0 listed and rendered before
["A","A","A",true,"A"]
["B","B","B",true,"A"]
["C","C","C",true,"A"]
through the old Page's field
["D","D","D",true,"D"]
["D","D",true,true]
string(1) "D"
string(1) "D"
-- the only Page for page 1, rendered before
bool(true)
bool(true)
-- a field repeated on 30 pages
int(30)
bool(true)
