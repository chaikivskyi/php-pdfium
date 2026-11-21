--TEST--
version sets the PDF version in the header on a full save; for values PDFium does not accept it writes what it would without the option, and it ignores the version on an incremental save that is not a full copy
--EXTENSIONS--
pdfium
--FILE--
<?php
$src = file_get_contents(__DIR__ . '/fixtures/tiny.pdf');
$doc = Pdfium\Document::fromString($src);
foreach ([10, 14, 17] as $v) {
    var_dump(substr($doc->toString(version: $v), 0, 8));
}
$old = Pdfium\Document::fromString($doc->toString(version: 14));
foreach ([0, 5, 20, -1] as $v) {
    var_dump(substr($old->toString(version: $v), 0, 8));
}
var_dump(substr($old->toString(), 0, 8));
var_dump(substr($doc->toString(incremental: true, version: 14), 0, 8));
$path = sys_get_temp_dir() . '/pdfium-save-version-' . getmypid() . '.pdf';
$doc->save($path, version: 13);
var_dump(file_get_contents($path, false, null, 0, 8));
unlink($path);
foreach ([fn () => $doc->toString(version: PHP_INT_MAX), fn () => $doc->save($path, version: PHP_INT_MIN)] as $fn) {
    try { $fn(); } catch (ValueError $e) { echo $e->getMessage(), "\n"; }
}
var_dump(file_exists($path));
$two = Pdfium\Document::fromString(str_replace('%PDF-1.7', '%PDF-2.0', $src));
var_dump(substr($two->toString(), 0, 8), substr($two->toString(version: 99), 0, 8), substr($two->toString(version: 14), 0, 8));
$made = Pdfium\Document::fromFile(__DIR__ . '/fixtures/text-3pages.pdf')->extractPages([0]);
var_dump(substr($made->toString(version: 5), 0, 8), substr($made->toString(incremental: true, version: 14), 0, 8));
$enc = Pdfium\Document::fromFile(__DIR__ . '/fixtures/encrypted-user.pdf', 'user');
var_dump(substr($enc->toString(removeSecurity: true, incremental: true, version: 14), 0, 8));
?>
--EXPECT--
string(8) "%PDF-1.0"
string(8) "%PDF-1.4"
string(8) "%PDF-1.7"
string(8) "%PDF-1.4"
string(8) "%PDF-1.4"
string(8) "%PDF-1.4"
string(8) "%PDF-1.4"
string(8) "%PDF-1.4"
string(8) "%PDF-1.7"
string(8) "%PDF-1.3"
Pdfium\Document::toString(): Argument #3 ($version) must be between -2147483648 and 2147483647
Pdfium\Document::save(): Argument #4 ($version) must be between -2147483648 and 2147483647
bool(false)
string(8) "%PDF-1.0"
string(8) "%PDF-1.0"
string(8) "%PDF-1.4"
string(8) "%PDF-1.7"
string(8) "%PDF-1.4"
string(8) "%PDF-1.4"
