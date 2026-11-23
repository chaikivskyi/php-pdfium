--TEST--
An incremental save throws MemoryException, not a fatal error, when the output would pass memory_limit
--EXTENSIONS--
pdfium
--INI--
memory_limit=32M
--FILE--
<?php
require __DIR__ . '/pdf.inc';
ini_set('memory_limit', '256M');
$doc = Pdfium\Document::fromString(noise_pdf(2400));
ini_set('memory_limit', '32M');
$path = sys_get_temp_dir() . '/pdfium-save-incremental-memory-' . getmypid() . '.pdf';
foreach ([fn () => $doc->toString(incremental: true), fn () => $doc->save($path, incremental: true)] as $fn) {
    try { $fn(); echo "serialised\n"; } catch (Pdfium\MemoryException $e) { echo get_class($e), "\n"; }
}
var_dump(file_exists($path));
var_dump(strlen(Pdfium\Document::fromFile(__DIR__ . '/fixtures/tiny.pdf')->toString(incremental: true)) > 0);
?>
--CLEAN--
<?php
array_map('unlink', glob(sys_get_temp_dir() . '/pdfium-save-incremental-memory-*.pdf'));
?>
--EXPECT--
Pdfium\MemoryException
Pdfium\MemoryException
bool(false)
bool(true)
