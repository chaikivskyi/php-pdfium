--TEST--
permissions are reported as bools, the same whether or not the owner password was given
--EXTENSIONS--
pdfium
--FILE--
<?php
$f = __DIR__ . '/fixtures/';
echo json_encode(Pdfium\Document::open($f . 'text-3pages.pdf')->permissions()), "\n";
$p = Pdfium\Document::open($f . 'encrypted-owner.pdf')->permissions();
var_dump($p['print'], $p['modify'], $p['copy'], $p['extractForAccessibility']);
var_dump(Pdfium\Document::open($f . 'encrypted-owner.pdf', 'owner')->permissions() === $p);
?>
--EXPECT--
{"print":true,"modify":true,"copy":true,"annotate":true,"fillForms":true,"extractForAccessibility":true,"assemble":true,"printHighQuality":true}
bool(false)
bool(false)
bool(false)
bool(true)
bool(true)
