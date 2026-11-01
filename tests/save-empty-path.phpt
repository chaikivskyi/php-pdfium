--TEST--
save(), savePng() and saveJpeg() throw ValueError for an empty path
--EXTENSIONS--
pdfium
--FILE--
<?php
$doc = Pdfium\Document::fromFile(__DIR__ . "/fixtures/tiny.pdf");
$img = $doc->page(0)->render(dpi: 1);
foreach ([
    'save'     => fn() => $doc->save(''),
    'savePng'  => fn() => $img->savePng(''),
    'saveJpeg' => fn() => $img->saveJpeg(''),
] as $name => $fn) {
    try { $fn(); echo "$name: saved\n"; } catch (Throwable $e) { echo "$name: ", get_class($e), ": ", $e->getMessage(), "\n"; }
}
?>
--EXPECT--
save: ValueError: Pdfium\Document::save(): Argument #1 ($path) must not be empty
savePng: ValueError: Pdfium\Bitmap::savePng(): Argument #1 ($path) must not be empty
saveJpeg: ValueError: Pdfium\Bitmap::saveJpeg(): Argument #1 ($path) must not be empty
