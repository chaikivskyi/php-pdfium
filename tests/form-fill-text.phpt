--TEST--
FormField::setValue() sets the text of text fields and editable combo boxes and refuses the other types
--EXTENSIONS--
pdfium
--FILE--
<?php
require __DIR__ . '/form.inc';

function attempt(Pdfium\Document $doc, string $name, string $value): void
{
    $f = field($doc, $name);
    $before = $f->value();
    try {
        $f->setValue($value);
        echo "$name: no exception\n";
    } catch (Throwable $e) {
        echo "$name: ", get_class($e), ': ', $e->getMessage(), "\n";
    }
    var_dump($f->value() === $before);
    var_dump(strlen($doc->toString()) > 0);
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
foreach (["a\nb", "a\r\nb"] as $v) {
    $name->setValue($v);
    var_dump(bin2hex($name->value()));
}

foreach (['country', 'agree', 'sig', 'go', 'langs'] as $n) {
    attempt($doc, $n, 'x');
}
attempt($doc, 'name', "a\0b");
attempt($doc, 'name', "\xFF");
attempt($doc, 'name', "\u{1F600}x");
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
country: Pdfium\PdfiumException: Cannot set the text of a ComboBox field that is not editable
bool(true)
bool(true)
string(2) "ok"
agree: Pdfium\PdfiumException: Cannot set the text of a Checkbox field
bool(true)
bool(true)
string(2) "ok"
sig: Pdfium\PdfiumException: Cannot set the text of a Signature field
bool(true)
bool(true)
string(2) "ok"
go: Pdfium\PdfiumException: Cannot set the text of a PushButton field
bool(true)
bool(true)
string(2) "ok"
langs: Pdfium\PdfiumException: Cannot set the text of a ListBox field
bool(true)
bool(true)
string(2) "ok"
name: ValueError: Pdfium\FormField::setValue(): Argument #1 ($value) must not contain any null bytes
bool(true)
bool(true)
string(2) "ok"
name: ValueError: Pdfium\FormField::setValue(): Argument #1 ($value) must be valid UTF-8
bool(true)
bool(true)
string(2) "ok"
name: ValueError: Pdfium\FormField::setValue(): Argument #1 ($value) must not contain characters outside the Basic Multilingual Plane; PDFium's form filler cannot store them
bool(true)
bool(true)
string(2) "ok"
