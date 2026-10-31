--TEST--
toString() keeps encryption unless removeSecurity is set; extracts are never encrypted
--EXTENSIONS--
pdfium
--FILE--
<?php
$f = __DIR__ . '/fixtures/';
$user = Pdfium\Document::open($f . 'encrypted-user.pdf', password: 'user');
$kept = $user->toString();
try { Pdfium\Document::fromString($kept); echo "opened\n"; } catch (Pdfium\PasswordException $e) { echo "PasswordException\n"; }
var_dump(Pdfium\Document::fromString($kept, password: 'user')->pageCount());
var_dump(Pdfium\Document::fromString($user->toString(removeSecurity: true))->pageCount());
var_dump(Pdfium\Document::fromString($user->extractPages([0])->toString())->pageCount());
$owner = Pdfium\Document::open($f . 'encrypted-owner.pdf');
var_dump(Pdfium\Document::fromString($owner->toString())->permissions()['copy']);
var_dump(Pdfium\Document::fromString($owner->toString(removeSecurity: true))->permissions()['copy']);
?>
--EXPECT--
PasswordException
int(3)
int(3)
int(1)
bool(false)
bool(true)
