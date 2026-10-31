--TEST--
A failed save keeps the reason it could not open the file as the previous exception
--EXTENSIONS--
pdfium
--FILE--
<?php
$doc = Pdfium\Document::open(__DIR__ . '/fixtures/tiny.pdf');
$img = $doc->page(0)->render(dpi: 1);
$path = __DIR__ . '/no-such-dir/out';
foreach ([fn() => $img->savePng($path), fn() => $img->saveJpeg($path), fn() => $doc->save($path)] as $fn) {
    try { $fn(); } catch (Pdfium\FileException $e) {
        echo get_class($e->getPrevious()), ': ', $e->getPrevious()->getMessage(), "\n";
    }
}
?>
--EXPECTF--
Pdfium\FileException: Pdfium\Bitmap::savePng(%sno-such-dir/out): Failed to open stream: No such file or directory
Pdfium\FileException: Pdfium\Bitmap::saveJpeg(%sno-such-dir/out): Failed to open stream: No such file or directory
Pdfium\FileException: Pdfium\Document::save(%sno-such-dir/out): Failed to open stream: No such file or directory
