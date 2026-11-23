--TEST--
Document::formType() tells AcroForm, XFA and no form apart, and does not change the document; the fields of XFA documents and of extractPages() and merge() output, which have no form, still fill
--EXTENSIONS--
pdfium
--INI--
pcre.jit=0
--FILE--
<?php
require __DIR__ . '/form.inc';
$withoutId = fn(string $pdf) => preg_replace('/\/ID\s*\[[^\]]*\]/', '', $pdf);
$doc = Pdfium\Document::fromString(standard_form());
$other = Pdfium\Document::fromString(standard_form());
var_dump($doc->formType());
var_dump($withoutId($doc->toString()) === $withoutId($other->toString()));
var_dump(Pdfium\Document::fromFile(__DIR__ . '/fixtures/a4.pdf')->formType());
$xfa = fn(string $catalog) => Pdfium\Document::fromString(form_pdf([
    8 => '<< /Type /Annot /Subtype /Widget /P 3 0 R /F 4 /FT /Tx /T (name) /Rect [100 700 300 720] >>',
], [
    1 => "<< /Type /Catalog /Pages 2 0 R /AcroForm 4 0 R $catalog >>",
    4 => '<< /Fields [8 0 R] /DA (/Helv 12 Tf 0 g) /XFA [(x) 7 0 R] >>',
    7 => stream_obj('<x/>'),
]));
foreach ([$xfa('/NeedsRendering true'), $xfa(''), $doc->extractPages([0]), Pdfium\Document::merge([$doc])] as $d) {
    field($d, 'name')->setValue('new');
    var_dump($d->formType(), field($d, 'name')->value());
}
?>
--EXPECT--
enum(Pdfium\FormType::AcroForm)
bool(true)
enum(Pdfium\FormType::None)
enum(Pdfium\FormType::XfaFull)
string(3) "new"
enum(Pdfium\FormType::XfaForeground)
string(3) "new"
enum(Pdfium\FormType::None)
string(3) "new"
enum(Pdfium\FormType::None)
string(3) "new"
