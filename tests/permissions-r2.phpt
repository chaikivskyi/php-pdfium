--TEST--
permissions() derives the newer flags from the four that revision-2 encryption defines
--EXTENSIONS--
pdfium
--FILE--
<?php
echo json_encode(Pdfium\Document::open(__DIR__ . '/fixtures/encrypted-r2.pdf')->permissions()), "\n";
$p = Pdfium\Document::open(__DIR__ . '/fixtures/encrypted-owner.pdf')->permissions();
var_dump($p['printHighQuality']);
?>
--EXPECT--
{"print":false,"modify":false,"copy":true,"annotate":true,"fillForms":true,"extractForAccessibility":true,"assemble":false,"printHighQuality":false}
bool(false)
