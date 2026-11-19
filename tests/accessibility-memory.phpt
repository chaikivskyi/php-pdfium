--TEST--
language() and setLanguage() throw MemoryException, not a fatal error, when the language would not fit under memory_limit; a refused value is a ValueError whatever its size
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
$small = Pdfium\Document::fromString(nav_pdf('/Lang (en)'));
try { $small->setLanguage(str_repeat('x', 40 << 20)); } catch (Pdfium\MemoryException $e) { echo $e->getMessage(), "\n"; }
var_dump($small->language());
unset($e);
try { $small->setLanguage(str_repeat("\x1Bx", 20 << 20)); } catch (ValueError $e) { echo get_class($e), "\n"; }
?>
--EXPECT--
The document language needs more memory than memory_limit leaves
bool(false)
The language needs more memory than memory_limit leaves
string(2) "en"
ValueError
