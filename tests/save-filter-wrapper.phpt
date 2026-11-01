--TEST--
save() through php://filter to a path outside open_basedir throws FileException without a warning
--EXTENSIONS--
pdfium
--INI--
open_basedir={PWD}
--FILE--
<?php
try { Pdfium\Document::fromFile(__DIR__ . "/fixtures/tiny.pdf")->save("php://filter/resource=/tmp/pdfium-filter.pdf"); } catch (Throwable $e) { echo get_class($e), "\n"; }
?>
--EXPECT--
Pdfium\FileException
