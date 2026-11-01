--TEST--
Pages loaded before the first render, and rendered more than once, still draw their form fields
--EXTENSIONS--
pdfium
--FILE--
<?php
$f = __DIR__ . '/fixtures/';
$doc = Pdfium\Document::fromFile($f . 'form.pdf');
$page = $doc->page(0);
$page->text();
$first = $page->render(dpi: 72)->toPng();
var_dump(
    $first === Pdfium\Document::fromFile($f . 'form.pdf')->page(0)->render(dpi: 72)->toPng(),
    $first === $page->render(dpi: 72)->toPng(),
    $first !== Pdfium\Document::fromFile($f . 'form-empty.pdf')->page(0)->render(dpi: 72)->toPng(),
);
?>
--EXPECT--
bool(true)
bool(true)
bool(true)
