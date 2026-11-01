--TEST--
toString() keeps encryption unless removeSecurity is set; extractPages() and merge() refuse an encrypted document unless removeSecurity is set, and then give an unencrypted one
--EXTENSIONS--
pdfium
--FILE--
<?php
$f = __DIR__ . '/fixtures/';
$user = Pdfium\Document::fromFile($f . 'encrypted-user.pdf', password: 'user');
$kept = $user->toString();
try { Pdfium\Document::fromString($kept); echo "opened\n"; } catch (Pdfium\PasswordException $e) { echo "PasswordException\n"; }
var_dump(Pdfium\Document::fromString($kept, password: 'user')->pageCount());
var_dump(Pdfium\Document::fromString($user->toString(removeSecurity: true))->pageCount());
try { $user->extractPages([0]); } catch (Pdfium\UnsupportedException $e) { echo $e->getMessage(), "\n"; }
var_dump(Pdfium\Document::fromString($user->extractPages([0], removeSecurity: true)->toString())->pageCount());
$owner = Pdfium\Document::fromFile($f . 'encrypted-owner.pdf');
var_dump(Pdfium\Document::fromString($owner->toString())->permissions()->copy);
var_dump(Pdfium\Document::fromString($owner->toString(removeSecurity: true))->permissions()->copy);
$plain = Pdfium\Document::fromFile($f . 'text-3pages.pdf');
foreach ([[$plain, $owner], ['first' => $plain, 'second' => $owner]] as $sources) {
    try { Pdfium\Document::merge($sources); } catch (Pdfium\UnsupportedException $e) { echo $e->getMessage(), "\n"; }
}
$merged = Pdfium\Document::fromString(Pdfium\Document::merge([$plain, $owner], removeSecurity: true)->toString());
var_dump($merged->pageCount(), $merged->permissions()->copy);
?>
--EXPECT--
PasswordException
int(3)
int(3)
The document is encrypted and PDFium cannot encrypt the result; pass removeSecurity: true to extract its pages unencrypted
int(1)
bool(false)
bool(true)
The document at index 1 is encrypted and PDFium cannot encrypt the result; pass removeSecurity: true to merge it unencrypted
The document at index "second" is encrypted and PDFium cannot encrypt the result; pass removeSecurity: true to merge it unencrypted
int(6)
bool(true)
