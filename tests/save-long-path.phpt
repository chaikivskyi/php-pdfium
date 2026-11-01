--TEST--
savePng() refuses a path of MAXPATHLEN or more with FileException and no open_basedir warning
--EXTENSIONS--
pdfium
--INI--
open_basedir={PWD}
--FILE--
<?php
$img = Pdfium\Document::fromFile(__DIR__ . "/fixtures/tiny.pdf")->page(0)->render(dpi: 1);
try { $img->savePng(__DIR__ . "/" . str_repeat("a", 5000)); } catch (Throwable $e) { echo get_class($e), ' <- ', $e->getPrevious()?->getMessage() ?? 'none', "\n"; }
?>
--EXPECTF--
Pdfium\FileException <- %s: Failed to open stream: File%Sname too long
