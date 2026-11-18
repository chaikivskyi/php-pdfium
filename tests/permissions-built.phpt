--TEST--
permissions() reports every permission for an unencrypted document PDFium built in memory, before and after a save and reopen
--EXTENSIONS--
pdfium
--FILE--
<?php
$f = __DIR__ . '/fixtures/text-3pages.pdf';
$docs = [
    'extractPages' => Pdfium\Document::fromFile($f)->extractPages([1]),
    'merge' => Pdfium\Document::merge([Pdfium\Document::fromFile($f)]),
    'create' => Pdfium\Document::create(),
    'nUp' => Pdfium\Document::fromFile($f)->nUp(612, 792, 2, 1),
];
foreach ($docs as $name => $doc) {
    echo $name, ' ', json_encode($doc->permissions()), "\n";
    echo $name, ' reopened ', json_encode(Pdfium\Document::fromString($doc->toString())->permissions()), "\n";
}
?>
--EXPECT--
extractPages {"print":true,"modify":true,"copy":true,"annotate":true,"fillForms":true,"extractForAccessibility":true,"assemble":true,"printHighQuality":true}
extractPages reopened {"print":true,"modify":true,"copy":true,"annotate":true,"fillForms":true,"extractForAccessibility":true,"assemble":true,"printHighQuality":true}
merge {"print":true,"modify":true,"copy":true,"annotate":true,"fillForms":true,"extractForAccessibility":true,"assemble":true,"printHighQuality":true}
merge reopened {"print":true,"modify":true,"copy":true,"annotate":true,"fillForms":true,"extractForAccessibility":true,"assemble":true,"printHighQuality":true}
create {"print":true,"modify":true,"copy":true,"annotate":true,"fillForms":true,"extractForAccessibility":true,"assemble":true,"printHighQuality":true}
create reopened {"print":true,"modify":true,"copy":true,"annotate":true,"fillForms":true,"extractForAccessibility":true,"assemble":true,"printHighQuality":true}
nUp {"print":true,"modify":true,"copy":true,"annotate":true,"fillForms":true,"extractForAccessibility":true,"assemble":true,"printHighQuality":true}
nUp reopened {"print":true,"modify":true,"copy":true,"annotate":true,"fillForms":true,"extractForAccessibility":true,"assemble":true,"printHighQuality":true}
