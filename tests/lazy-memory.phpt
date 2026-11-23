--TEST--
A file larger than memory_limit cannot be loaded whole, but opens lazily and renders a page
--EXTENSIONS--
pdfium
--INI--
memory_limit=64M
--FILE--
<?php
require __DIR__ . '/pdf.inc';
$path = sys_get_temp_dir() . '/pdfium-lazy-memory-' . getmypid() . '.pdf';
ini_set('memory_limit', '512M');
$page = Pdfium\Document::fromString(noise_pdf(1000));
Pdfium\Document::merge(array_fill(0, 30, $page))->save($path);
unset($page);
gc_collect_cycles();
ini_set('memory_limit', '64M');
var_dump(filesize($path) > 80 << 20);
try { Pdfium\Document::fromFile($path); } catch (Pdfium\MemoryException $e) { echo get_class($e), "\n"; }
$lazy = Pdfium\Document::fromFile($path, lazy: true);
var_dump($lazy->pageCount(), $lazy->page(29)->render(dpi: 10)->width());
unset($lazy);
unlink($path);
?>
--CLEAN--
<?php
array_map('unlink', glob(sys_get_temp_dir() . '/pdfium-lazy-memory-*.pdf'));
?>
--EXPECT--
bool(true)
Pdfium\MemoryException
int(30)
int(139)
