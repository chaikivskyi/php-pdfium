--TEST--
FormField::setValue() sets the text of text fields and editable combo boxes, refuses the other types and runs no field scripts
--EXTENSIONS--
pdfium
--FILE--
<?php
require __DIR__ . '/form.inc';

function attempt(Pdfium\Document $doc, string $name, string $value): void
{
    $f = field($doc, $name);
    $before = $f->value();
    echo "$name: ", outcome(fn() => $f->setValue($value)), "\n";
    var_dump($f->value() === $before);
    $doc->toString();
    $n = field($doc, 'name');
    $n->setValue('ok');
    var_dump($n->value());
}

$doc = Pdfium\Document::fromString(standard_form());
$name = field($doc, 'name');
$name->setValue('Jane');
var_dump($name->value());
$again = Pdfium\Document::fromString($doc->toString());
var_dump(field($again, 'name')->value());
$original = Pdfium\Document::fromString(standard_form())->page(0)->render(dpi: 72)->toPng();
var_dump($again->page(0)->render(dpi: 72)->toPng() !== $original);

$name->setValue('Jane Doe is too long');
var_dump($name->value());
$name->setValue('');
var_dump($name->value());
$name->setValue("Zoë €");
var_dump($name->value());

$notes = field($doc, 'parent.notes');
$notes->setValue("line1\nline2");
var_dump(bin2hex($notes->value()));

$locked = field($doc, 'locked');
$locked->setValue('changed');
var_dump($locked->value());

$city = field($doc, 'city');
$city->setValue('Odesa');
var_dump($city->value());

$again = Pdfium\Document::fromString($doc->toString());
foreach (['name', 'locked', 'city'] as $n) {
    var_dump(field($again, $n)->value());
}
var_dump(bin2hex(field($again, 'parent.notes')->value()));

foreach (["a\r\nb", "a\rb", "a\tb"] as $v) {
    $notes->setValue($v);
    var_dump(bin2hex($notes->value()));
}
foreach (["a\nb", "a\r\nb", "\u{1F600}x"] as $v) {
    $name->setValue($v);
    var_dump(bin2hex($name->value()));
}

foreach (['country', 'agree', 'sig', 'go', 'langs'] as $n) {
    attempt($doc, $n, 'x');
}
attempt($doc, 'name', "a\0b");
attempt($doc, 'name', "\xFF");
$read = form_pdf([8 => '<< /Type /Annot /Subtype /Widget /P 3 0 R /F 4 /FT /Tx /T (e) /V <FEFFD83DDE000078> /Rect [100 700 300 720] >>']);
var_dump(bin2hex(Pdfium\Document::fromString($read)->page(0)->formFields()[0]->value()));

$combo = form_pdf([8 => '<< /Type /Annot /Subtype /Widget /P 3 0 R /F 4 /FT /Ch /Ff 393216 /T (city) /Opt [(Kyiv) [(lv) (Lviv)]] /V (Kyiv) /Rect [100 500 300 520] >>']);
$doc = Pdfium\Document::fromString($combo);
$city = $doc->page(0)->formFields()[0];
$state = fn(Pdfium\FormField $f) => [$f->value(), array_map(fn($o) => $o->selected, $f->options())];
$city->setValue('Custom');
echo json_encode([$state($city), $state(Pdfium\Document::fromString($doc->toString())->page(0)->formFields()[0])]), "\n";
$city->select(1);
foreach (['Typed', '', 'Kyiv', 'Kyi', 'Lviv', 'lv'] as $v) {
    $city->setValue($v);
    echo json_encode([$state($city), $state(Pdfium\Document::fromString($doc->toString())->page(0)->formFields()[0])]), "\n";
}

$js = fn(string $code) => "<< /S /JavaScript /JS ($code) >>";
$w = '/Type /Annot /Subtype /Widget /P 3 0 R /F 4 /FT /Tx';
$scripted = fields(Pdfium\Document::fromString(form_pdf([
    8 => "<< $w /T (src) /Rect [100 700 300 720] /AA << /K {$js('event.rc = false;')} /F {$js('event.value = 1;')} /V {$js('event.rc = false;')} >> >>",
    9 => "<< $w /T (sum) /V (0) /Rect [100 600 300 620] /AA << /C {$js('event.value = 2;')} >> >>",
], [4 => '<< /Fields [8 0 R 9 0 R] /CO [9 0 R] /DA (/Helv 12 Tf 0 g) /DR << /Font << /Helv 5 0 R >> >> >>'])));
$scripted['src']->setValue('abc');
var_dump($scripted['src']->value(), $scripted['sum']->value());
?>
--EXPECT--
string(4) "Jane"
string(4) "Jane"
bool(true)
string(10) "Jane Doe i"
string(0) ""
string(8) "Zoë €"
string(24) "6c696e65310d0a6c696e6532"
string(7) "changed"
string(5) "Odesa"
string(8) "Zoë €"
string(7) "changed"
string(5) "Odesa"
string(24) "6c696e65310d0a6c696e6532"
string(8) "610d0a62"
string(8) "610d0a62"
string(6) "612062"
string(4) "6162"
string(4) "6162"
string(8) "ef988078"
country: Pdfium\PdfiumException: Cannot set the text of a ComboBox field that is not editable
bool(true)
string(2) "ok"
agree: Pdfium\PdfiumException: Cannot set the text of a Checkbox field
bool(true)
string(2) "ok"
sig: Pdfium\PdfiumException: Cannot set the text of a Signature field
bool(true)
string(2) "ok"
go: Pdfium\PdfiumException: Cannot set the text of a PushButton field
bool(true)
string(2) "ok"
langs: Pdfium\PdfiumException: Cannot set the text of a ListBox field
bool(true)
string(2) "ok"
name: ValueError: Pdfium\FormField::setValue(): Argument #1 ($value) must not contain any null bytes
bool(true)
string(2) "ok"
name: ValueError: Pdfium\FormField::setValue(): Argument #1 ($value) must be valid UTF-8
bool(true)
string(2) "ok"
string(10) "f09f988078"
[["Custom",[false,false]],["Custom",[false,false]]]
[["Typed",[false,false]],["Typed",[false,false]]]
[["",[false,false]],["",[false,false]]]
[["Kyiv",[true,false]],["Kyiv",[true,false]]]
[["Kyi",[false,false]],["Kyi",[false,false]]]
[["Lviv",[false,false]],["Lviv",[false,false]]]
[["lv",[false,true]],["lv",[false,true]]]
string(3) "abc"
string(1) "0"
