--TEST--
Page::render() draws form fields that have no appearance stream
--EXTENSIONS--
pdfium
--FILE--
<?php
$f = __DIR__ . '/fixtures/';
$filled = Pdfium\Document::fromFile($f . 'form.pdf')->page(0)->render(dpi: 72)->toPng();
$empty  = Pdfium\Document::fromFile($f . 'form-empty.pdf')->page(0)->render(dpi: 72)->toPng();
var_dump($filled !== $empty);
?>
--EXPECT--
bool(true)
