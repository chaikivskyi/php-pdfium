--TEST--
A text field without an appearance stream shows what setValue() stored after the document is saved and reopened, whether or not the form asks for NeedAppearances, and flatten() turns it into page text, as it does with a value or checked state the document came with when nothing touched the form before; in a document without an AcroForm it leaves out annotations that have no appearance stream
--EXTENSIONS--
pdfium
--FILE--
<?php
require __DIR__ . '/pdf.inc';

function pdf(bool $need, string $field = '/FT /Tx /T (name) /Rect [100 700 300 720] /DA (/Helv 12 Tf 0 g)'): string
{
    $acro = '<< /Fields [8 0 R] /DA (/Helv 12 Tf 0 g) /DR << /Font << /Helv 5 0 R >> >>' . ($need ? ' /NeedAppearances true' : '') . ' >>';
    return form_pdf([
        8 => "<< /Type /Annot /Subtype /Widget /P 3 0 R /F 4 $field >>",
    ], [4 => $acro]);
}

foreach ([false, true] as $need) {
    echo $need ? "-- NeedAppearances true\n" : "-- no NeedAppearances\n";
    $empty = Pdfium\Document::fromString(pdf($need))->page(0)->render(dpi: 72)->toPng();
    $doc = Pdfium\Document::fromString(pdf($need));
    $doc->page(0)->formFields()[0]->setValue('SHOWN');
    $page = Pdfium\Document::fromString($doc->toString())->page(0);
    var_dump($page->formFields()[0]->value());
    var_dump($page->render(dpi: 72)->toPng() !== $empty);
    var_dump($page->render(dpi: 72, annotations: false)->toPng() === $empty);
    var_dump(str_contains($page->text(), 'SHOWN'));
    var_dump($page->flatten(), str_contains($page->text(), 'SHOWN'), $page->formFields());
    echo "flattened straight after opening\n";
    $doc = Pdfium\Document::fromString(pdf($need, '/FT /Tx /T (name) /V (KEPT) /Rect [100 700 300 720] /DA (/Helv 12 Tf 0 g)'));
    var_dump($doc->page(0)->flatten());
    $page = Pdfium\Document::fromString($doc->toString())->page(0);
    var_dump(str_contains($page->text(), 'KEPT'), $page->formFields());
    foreach (['Yes', 'Off'] as $state) {
        $doc = Pdfium\Document::fromString(pdf($need, "/FT /Btn /T (agree) /V /$state /AS /$state /Rect [100 650 120 670]"));
        var_dump($doc->page(0)->flatten());
        $page = Pdfium\Document::fromString($doc->toString())->page(0);
        var_dump($page->render(dpi: 72, annotations: false)->toPng() !== $empty, $page->formFields());
    }
}
echo "-- no AcroForm\n";
$pdf = build_pdf([
    '<< /Type /Catalog /Pages 2 0 R >>',
    '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
    '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Annots [4 0 R 5 0 R] >>',
    '<< /Type /Annot /Subtype /Square /F 4 /Rect [100 100 200 200] /C [1 0 0] >>',
    '<< /Type /Annot /Subtype /Widget /F 4 /FT /Tx /T (x) /V (orphan) /Rect [100 700 300 720] /DA (/Helv 12 Tf 0 g) >>',
]);
$blank = Pdfium\Document::fromString($pdf)->page(0)->render(dpi: 72, annotations: false)->toPng();
$doc = Pdfium\Document::fromString($pdf);
var_dump($doc->formType(), $doc->page(0)->flatten());
$page = Pdfium\Document::fromString($doc->toString())->page(0);
var_dump($page->render(dpi: 72, annotations: false)->toPng() === $blank, $page->text());
?>
--EXPECT--
-- no NeedAppearances
string(5) "SHOWN"
bool(true)
bool(true)
bool(false)
bool(true)
bool(true)
array(0) {
}
flattened straight after opening
bool(true)
bool(true)
array(0) {
}
bool(true)
bool(true)
array(0) {
}
bool(true)
bool(false)
array(0) {
}
-- NeedAppearances true
string(5) "SHOWN"
bool(true)
bool(true)
bool(false)
bool(true)
bool(true)
array(0) {
}
flattened straight after opening
bool(true)
bool(true)
array(0) {
}
bool(true)
bool(true)
array(0) {
}
bool(true)
bool(false)
array(0) {
}
-- no AcroForm
enum(Pdfium\FormType::None)
bool(true)
bool(true)
string(0) ""
