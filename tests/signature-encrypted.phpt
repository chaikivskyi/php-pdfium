--TEST--
In an encrypted document a signature dictionary with /Type /Sig has its contents read as stored, unencrypted; one without /Type has them decrypted like any other string
--EXTENSIONS--
pdfium
--FILE--
<?php
$path = __DIR__ . '/fixtures/encrypted-signed.pdf';
$raw = file_get_contents($path);
var_dump(str_contains($raw, '3082ab000000'), str_contains($raw, 'cafe00'), str_contains($raw, 'I approve'));
$doc = Pdfium\Document::fromFile($path, password: 'user');
var_dump($doc->signatureCount());
[$typed, $untyped] = $doc->signatures();
var_dump(bin2hex($typed->contents), $typed->byteRange === [[0, 10], [20, 30]], $typed->subFilter, $typed->reason, $typed->time);
var_dump(bin2hex($untyped->contents), $untyped->reason);
?>
--EXPECT--
bool(true)
bool(false)
bool(false)
int(2)
string(12) "3082ab000000"
bool(true)
string(19) "adbe.pkcs7.detached"
string(9) "I approve"
string(23) "D:20261118120000+02'00'"
string(6) "cafe00"
string(7) "No type"
