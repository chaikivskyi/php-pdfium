--TEST--
Pages from extractPages() and merge() still draw their form fields
--EXTENSIONS--
pdfium
--FILE--
<?php
$f = __DIR__ . '/fixtures/';
$form = Pdfium\Document::fromFile($f . 'form.pdf');
$empty = Pdfium\Document::fromFile($f . 'form-empty.pdf');
$png = fn(Pdfium\Document $d, int $i = 0) => $d->page($i)->render(dpi: 72)->toPng();
var_dump(
    $png($form->extractPages([0])) === $png($form),
    $png(Pdfium\Document::merge([$empty, $form]), 1) === $png($form),
    $png($form->extractPages([0])) !== $png($empty->extractPages([0])),
);
?>
--EXPECT--
bool(true)
bool(true)
bool(true)
