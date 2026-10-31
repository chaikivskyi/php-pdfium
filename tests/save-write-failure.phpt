--TEST--
save*() turns a failed write into FileException without a notice
--EXTENSIONS--
pdfium
--SKIPIF--
<?php if (!is_writable('/dev/full')) die('skip /dev/full is not writable'); ?>
--FILE--
<?php
$img = Pdfium\Document::open(__DIR__ . '/fixtures/tiny.pdf')->page(0)->render(dpi: 1);
foreach ([fn() => $img->savePng('/dev/full'), fn() => $img->saveJpeg('/dev/full')] as $fn) {
    try { $fn(); echo "ok\n"; } catch (Throwable $e) { echo get_class($e), "\n"; }
}
?>
--EXPECT--
Pdfium\FileException
Pdfium\FileException
