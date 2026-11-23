--TEST--
extractPages() throws MemoryException, not a fatal error, when its page indexes would not fit under memory_limit
--EXTENSIONS--
pdfium
--FILE--
<?php
$doc = Pdfium\Document::fromFile(__DIR__ . '/fixtures/text-3pages.pdf');
$many = array_fill(0, 1500000, 0);
ini_set('memory_limit', (string) (memory_get_usage(true) + (1 << 20)));
try { $doc->extractPages($many); } catch (Pdfium\MemoryException $e) { echo $e->getMessage(), "\n"; }
ini_restore('memory_limit');
echo $doc->pageCount(), ' ', count($doc->extractPages([2, 0])), "\n";
?>
--EXPECT--
The page indexes need more memory than memory_limit leaves
3 2
