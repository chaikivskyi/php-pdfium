--TEST--
Page::text() throws MemoryException, not a fatal error, when the text would pass memory_limit
--EXTENSIONS--
pdfium
--INI--
memory_limit=128M
--FILE--
<?php
require __DIR__ . '/pdf.inc';
$page = Pdfium\Document::fromString(text_pdf(200000))->page(0);
ini_set('memory_limit', (string) (memory_get_usage(true) + (3 << 20)));
try { $page->text(); echo "extracted\n"; } catch (Pdfium\MemoryException $e) { echo get_class($e), "\n"; }
ini_set('memory_limit', '128M');
var_dump(strlen($page->text()));
?>
--EXPECT--
Pdfium\MemoryException
int(200000)
