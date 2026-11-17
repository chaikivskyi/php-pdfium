--TEST--
permanentId() and changingId() throw MemoryException, not a fatal error, when the identifier would not fit under memory_limit
--EXTENSIONS--
pdfium
--INI--
memory_limit=256M
--FILE--
<?php
require __DIR__ . '/pdf.inc';
$doc = Pdfium\Document::fromString(nav_pdf(trailer: '/ID [(' . str_repeat('a', 32 << 20) . ') (' . str_repeat('b', 32 << 20) . ')] '));
ini_set('memory_limit', (string) (memory_get_usage(true) + (16 << 20)));
try { $doc->permanentId(); } catch (Pdfium\MemoryException $e) { echo $e->getMessage(), "\n"; }
try { $doc->changingId(); } catch (Pdfium\MemoryException $e) { echo $e->getMessage(), "\n"; }
var_dump(Pdfium\Document::fromString(nav_pdf())->permanentId());
?>
--EXPECT--
The file identifier needs more memory than memory_limit leaves
The file identifier needs more memory than memory_limit leaves
NULL
