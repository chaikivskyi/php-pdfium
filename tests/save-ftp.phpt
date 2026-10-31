--TEST--
save() to an unreachable ftp:// URL throws FileException without a warning
--EXTENSIONS--
pdfium
--SKIPIF--
<?php if (!in_array('ftp', stream_get_wrappers())) die('skip ftp wrapper not registered'); ?>
--FILE--
<?php
try { Pdfium\Document::open(__DIR__ . "/fixtures/tiny.pdf")->save("ftp://127.0.0.1:1/x.pdf"); } catch (Throwable $e) { echo get_class($e), "\n"; }
?>
--EXPECT--
Pdfium\FileException
