--TEST--
save() to a read-only data: stream throws FileException without a notice
--EXTENSIONS--
pdfium
--FILE--
<?php
try { Pdfium\Document::fromFile(__DIR__ . "/fixtures/tiny.pdf")->save("data:,x"); } catch (Throwable $e) { echo get_class($e), "\n"; }
?>
--EXPECT--
Pdfium\FileException
