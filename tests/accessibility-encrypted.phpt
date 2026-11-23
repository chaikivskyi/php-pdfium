--TEST--
A language set on an encrypted document is kept when saved with encryption and reads back with the password
--EXTENSIONS--
pdfium
--FILE--
<?php
$doc = Pdfium\Document::fromFile(__DIR__ . '/fixtures/encrypted-user.pdf', password: 'user');
$doc->setLanguage('LANGUAGE-MARKER');
$kept = $doc->toString();
$clear = $doc->toString(removeSecurity: true);
var_dump(str_contains($kept, 'LANGUAGE-MARKER'), str_contains($clear, 'LANGUAGE-MARKER'));
try { Pdfium\Document::fromString($kept); } catch (Pdfium\PasswordException $e) { echo "password needed\n"; }
var_dump(Pdfium\Document::fromString($kept, password: 'user')->language());
var_dump(Pdfium\Document::fromString($clear)->language());
$owner = Pdfium\Document::fromFile(__DIR__ . '/fixtures/encrypted-owner.pdf');
var_dump($owner->permissions()->modify);
$owner->setLanguage('en');
var_dump($owner->language());
?>
--EXPECT--
bool(false)
bool(true)
password needed
string(15) "LANGUAGE-MARKER"
string(15) "LANGUAGE-MARKER"
bool(false)
string(2) "en"
