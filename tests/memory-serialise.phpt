--TEST--
toString() and save() throw MemoryException, not a fatal error, when the output would pass memory_limit
--EXTENSIONS--
pdfium
--INI--
memory_limit=32M
--FILE--
<?php
require __DIR__ . '/pdf.inc';
$doc = Pdfium\Document::fromString(noise_pdf(600));
$big = Pdfium\Document::merge(array_fill(0, 40, $doc));
foreach ([fn() => $big->toString(), fn() => $big->save(sys_get_temp_dir() . '/pdfium-memory-serialise.pdf')] as $fn) {
    try { $fn(); echo "serialised\n"; } catch (Pdfium\MemoryException $e) { echo get_class($e), "\n"; }
}
var_dump(file_exists(sys_get_temp_dir() . '/pdfium-memory-serialise.pdf'), strlen($doc->toString()) > 3 * 600 * 600);
?>
--EXPECT--
Pdfium\MemoryException
Pdfium\MemoryException
bool(false)
bool(true)
