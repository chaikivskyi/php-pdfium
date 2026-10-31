--TEST--
Page::render() refuses a bitmap that would not fit under memory_limit
--EXTENSIONS--
pdfium
--INI--
memory_limit=64M
--FILE--
<?php
$page = Pdfium\Document::open(__DIR__ . '/fixtures/text-3pages.pdf')->page(0);
try { $page->render(dpi: 600); } catch (Pdfium\MemoryException $e) { echo "MemoryException\n"; }
var_dump($page->render(dpi: 72)->width());
?>
--EXPECT--
MemoryException
int(612)
