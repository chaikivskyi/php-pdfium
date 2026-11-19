--TEST--
language() throws MemoryException, not a fatal error, when it would not fit under memory_limit
--EXTENSIONS--
pdfium
--INI--
memory_limit=64M
--FILE--
<?php
require __DIR__ . '/pdf.inc';
$doc = Pdfium\Document::fromString(nav_pdf('/Lang (' . str_repeat('A', 12 << 20) . ')'));
try { $doc->language(); } catch (Pdfium\MemoryException $e) { echo $e->getMessage(), "\n"; }
var_dump($doc->isTagged());
?>
--EXPECT--
The document language needs more memory than memory_limit leaves
bool(false)
