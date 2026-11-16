--TEST--
Page::render(grayscale: true) keeps one byte per pixel, so a gray render fits where a colour one would not
--EXTENSIONS--
pdfium
--INI--
memory_limit=64M
--FILE--
<?php
$page = Pdfium\Document::fromFile(__DIR__ . '/fixtures/text-3pages.pdf')->page(0);
try { $page->render(dpi: 330); } catch (Pdfium\MemoryException $e) { echo "MemoryException\n"; }
$before = memory_get_usage();
$gray = $page->render(dpi: 330, grayscale: true);
$pixels = $gray->width() * $gray->height();
var_dump($pixels > 10000000, memory_get_usage() - $before < $pixels * 1.1);
var_dump(strlen($gray->toPng()) > 0);
?>
--EXPECT--
MemoryException
bool(true)
bool(true)
bool(true)
