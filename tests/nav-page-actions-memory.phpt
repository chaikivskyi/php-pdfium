--TEST--
openAction() and closeAction() throw MemoryException, not a fatal error, when the action would not fit under memory_limit
--EXTENSIONS--
pdfium
--INI--
memory_limit=256M
--FILE--
<?php
require __DIR__ . '/pdf.inc';
$uri = str_repeat('a', 20 << 20);
$page = Pdfium\Document::fromString(nav_pdf(pageExtra: [
    "/AA << /O << /S /URI /URI ($uri) >> /C << /S /Launch /F ($uri) >> >>",
    '',
    '',
]))->page(0);
unset($uri);
ini_set('memory_limit', (string) (memory_get_usage(true) + (16 << 20)));
try { $page->openAction(); } catch (Pdfium\MemoryException $e) { echo $e->getMessage(), "\n"; }
try { $page->closeAction(); } catch (Pdfium\MemoryException $e) { echo $e->getMessage(), "\n"; }
var_dump(Pdfium\Document::fromString(nav_pdf())->page(0)->openAction());
?>
--EXPECT--
The action needs more memory than memory_limit leaves
The action needs more memory than memory_limit leaves
NULL
