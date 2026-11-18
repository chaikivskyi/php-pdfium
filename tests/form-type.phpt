--TEST--
Document::formType() tells AcroForm, XFA and no form apart, and does not change the document
--EXTENSIONS--
pdfium
--FILE--
<?php
require __DIR__ . '/form.inc';
$withoutId = fn(string $pdf) => preg_replace('/\/ID\s*\[[^\]]*\]/', '', $pdf);
$doc = Pdfium\Document::fromString(standard_form());
$other = Pdfium\Document::fromString(standard_form());
var_dump($doc->formType());
var_dump($withoutId($doc->toString()) === $withoutId($other->toString()));
var_dump(Pdfium\Document::fromFile(__DIR__ . '/fixtures/a4.pdf')->formType());
$xfa = fn(string $catalog) => form_pdf([], [
    1 => "<< /Type /Catalog /Pages 2 0 R /AcroForm 4 0 R $catalog >>",
    4 => '<< /Fields [] /XFA [(x) 7 0 R] >>',
    7 => stream_obj('<x/>'),
]);
var_dump(Pdfium\Document::fromString($xfa('/NeedsRendering true'))->formType());
var_dump(Pdfium\Document::fromString($xfa(''))->formType());
?>
--EXPECT--
enum(Pdfium\FormType::AcroForm)
bool(true)
enum(Pdfium\FormType::None)
enum(Pdfium\FormType::XfaFull)
enum(Pdfium\FormType::XfaForeground)
