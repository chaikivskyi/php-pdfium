--TEST--
A file added to an encrypted document is encrypted when saved with encryption kept and reads back with the password; with removeSecurity it is saved in clear
--EXTENSIONS--
pdfium
--FILE--
<?php
$doc = Pdfium\Document::fromFile(__DIR__ . '/fixtures/encrypted-user.pdf', password: 'user');
$doc->addAttachment('secret.txt', 'PLAINTEXT-MARKER-1234', 'DESCRIPTION-MARKER');
$kept = $doc->toString();
$clear = $doc->toString(removeSecurity: true);
var_dump(str_contains($kept, 'DESCRIPTION-MARKER'), str_contains($clear, 'DESCRIPTION-MARKER'));
try { Pdfium\Document::fromString($kept); } catch (Pdfium\PasswordException $e) { echo "password needed\n"; }
$back = Pdfium\Document::fromString($kept, password: 'user')->attachment(0);
var_dump($back->name(), $back->contents(), $back->metadata()->description);
var_dump(Pdfium\Document::fromString($clear)->attachment(0)->contents());
?>
--EXPECT--
bool(false)
bool(true)
password needed
string(10) "secret.txt"
string(21) "PLAINTEXT-MARKER-1234"
string(18) "DESCRIPTION-MARKER"
string(21) "PLAINTEXT-MARKER-1234"
