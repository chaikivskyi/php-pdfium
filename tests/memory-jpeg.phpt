--TEST--
Bitmap::toJpeg(100) throws MemoryException, not a fatal error, when the encoding would pass memory_limit
--EXTENSIONS--
pdfium
--INI--
memory_limit=128M
--FILE--
<?php
require __DIR__ . '/pdf.inc';
$img = Pdfium\Document::fromString(noise_pdf(1500))->page(0)->render(dpi: 72);
ini_set('memory_limit', (string) (memory_get_usage(true) + (3 << 20)));
try { $img->toJpeg(100); echo "encoded\n"; } catch (Pdfium\MemoryException $e) { echo get_class($e), "\n"; }
ini_set('memory_limit', '128M');
var_dump(strlen($img->toJpeg(100)) > 1 << 20);
?>
--EXPECT--
Pdfium\MemoryException
bool(true)
