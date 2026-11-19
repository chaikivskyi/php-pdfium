--TEST--
Filling in a form of an encrypted document: the values are saved encrypted with encryption kept, read back with the password, and saved in clear with removeSecurity; permissions that deny filling do not stop it
--EXTENSIONS--
pdfium
--FILE--
<?php
require __DIR__ . '/form.inc';

$doc = Pdfium\Document::fromFile(__DIR__ . '/fixtures/encrypted-form.pdf', password: 'user');
$f = fields($doc);
var_dump($doc->formType(), $f['field1']->value(), $f['agree']->isChecked());
$f['field1']->setValue('SECRET-MARKER-1234');
$f['agree']->setChecked();
var_dump($f['field1']->value(), $f['agree']->isChecked());
$kept = $doc->toString();
$clear = $doc->toString(removeSecurity: true);
var_dump(str_contains($kept, 'SECRET-MARKER-1234'), str_contains($clear, 'SECRET-MARKER-1234'));
try { Pdfium\Document::fromString($kept); } catch (Pdfium\PasswordException $e) { echo "password needed\n"; }
foreach ([Pdfium\Document::fromString($kept, password: 'user'), Pdfium\Document::fromString($clear)] as $back) {
    $g = fields($back);
    var_dump($g['field1']->value(), $g['agree']->isChecked());
}
$second = Pdfium\Document::fromString($kept, password: 'user');
$g = fields($second);
$g['field1']->setValue('SECOND');
$g['agree']->setChecked(false);
$g = fields(Pdfium\Document::fromString($second->toString(), password: 'user'));
var_dump($g['field1']->value(), $g['agree']->isChecked());
$again = Pdfium\Document::fromString($kept, password: 'user');
var_dump($again->page(0)->flatten());
$flat = Pdfium\Document::fromString($again->toString(), password: 'user');
var_dump($flat->page(0)->formFields(), str_contains($flat->page(0)->text(), 'SECRET-MARKER-1234'));
$locked = Pdfium\Document::fromFile(__DIR__ . '/fixtures/encrypted-form-locked.pdf');
$g = fields($locked);
$g['field1']->setValue('LOCKED');
$g['agree']->setChecked();
var_dump($locked->permissions()->fillForms, $g['field1']->value(), $g['agree']->isChecked());
?>
--EXPECT--
enum(Pdfium\FormType::AcroForm)
string(6) "before"
bool(false)
string(18) "SECRET-MARKER-1234"
bool(true)
bool(false)
bool(true)
password needed
string(18) "SECRET-MARKER-1234"
bool(true)
string(18) "SECRET-MARKER-1234"
bool(true)
string(6) "SECOND"
bool(false)
bool(true)
array(0) {
}
bool(true)
bool(false)
string(6) "LOCKED"
bool(true)
