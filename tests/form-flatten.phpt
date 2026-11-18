--TEST--
Page::flatten() turns annotations and form fields into page content, in display or print mode
--EXTENSIONS--
pdfium
--FILE--
<?php
require __DIR__ . '/form.inc';

function flags_pdf(int $name, int $other, int $agree): string
{
    $w = '/Type /Annot /Subtype /Widget /P 3 0 R';
    return form_pdf([
        8 => "<< $w /F $name /FT /Tx /T (name) /V (old) /Rect [100 700 300 720] /AP << /N 11 0 R >> >>",
        9 => "<< $w /F $other /FT /Tx /T (other) /V (hid) /Rect [100 650 300 670] /AP << /N 12 0 R >> >>",
        10 => "<< $w /F $agree /FT /Btn /T (agree) /V /Yes /AS /Yes /Rect [100 600 120 620] " . check_ap('Yes', 13) . ' >>',
    ], [
        11 => text_ap('old'),
        12 => text_ap('hid'),
    ] + check_ap_objects(13));
}

$doc = Pdfium\Document::fromString(standard_form());
$page = $doc->page(0);
$before = $page->render(dpi: 72, annotations: false)->toPng();
field($page, 'name')->setValue('Jane');
var_dump($page->flatten(), $page->flatten());
var_dump(Pdfium\Document::fromFile(__DIR__ . '/fixtures/a4.pdf')->page(0)->flatten());
var_dump($page->formFields(), $page->links(), $page->formFieldAt(150, 710), str_contains($page->text(), 'Jane'));
$plain = $page->render(dpi: 72, annotations: false)->toPng();
var_dump($plain === $page->render(dpi: 72)->toPng(), $plain !== $before);
var_dump(str_contains($page->textPage()->text(), 'Jane'));
$again = Pdfium\Document::fromString($doc->toString());
var_dump($again->formType(), str_contains($again->page(0)->text(), 'Jane'), $again->page(0)->flatten());

echo "-- rendered with annotations before\n";
$doc = Pdfium\Document::fromString(standard_form());
$page = $doc->page(0);
$page->render(dpi: 72);
var_dump($page->flatten());
var_dump($page->render(dpi: 72)->toPng() === $page->render(dpi: 72, annotations: false)->toPng());

echo "-- modes\n";
foreach ([[true, 4, 0, 0], [false, 4, 0, 0], [false, 4, 2, 2], [true, 4, 2, 2], [false, 4, 1, 1], [true, 4, 6, 6]] as [$print, $name, $other, $agree]) {
    $doc = Pdfium\Document::fromString(flags_pdf($name, $other, $agree));
    $result = $doc->page(0)->flatten(forPrint: $print);
    $page = Pdfium\Document::fromString($doc->toString())->page(0);
    $text = $page->text();
    $region = fn(float $left) => $page->render(dpi: 72, region: new Pdfium\Rect($left, 600, $left + 20, 620))->toPng();
    echo json_encode(['print' => $print, 'F' => [$name, $other, $agree], 'result' => $result,
        'old' => str_contains($text, 'old'), 'hid' => str_contains($text, 'hid'), 'mark' => $region(100) !== $region(400)]), "\n";
}
?>
--EXPECT--
bool(true)
bool(false)
bool(false)
array(0) {
}
array(0) {
}
NULL
bool(true)
bool(true)
bool(true)
bool(true)
enum(Pdfium\FormType::None)
bool(true)
bool(false)
-- rendered with annotations before
bool(true)
bool(true)
-- modes
{"print":true,"F":[4,0,0],"result":true,"old":true,"hid":false,"mark":false}
{"print":false,"F":[4,0,0],"result":true,"old":true,"hid":true,"mark":true}
{"print":false,"F":[4,2,2],"result":true,"old":true,"hid":false,"mark":false}
{"print":true,"F":[4,2,2],"result":true,"old":true,"hid":false,"mark":false}
{"print":false,"F":[4,1,1],"result":true,"old":true,"hid":false,"mark":false}
{"print":true,"F":[4,6,6],"result":true,"old":true,"hid":false,"mark":false}
