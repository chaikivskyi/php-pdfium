--TEST--
open() honours open_basedir without warnings
--EXTENSIONS--
pdfium
--INI--
open_basedir={PWD}/fixtures
--FILE--
<?php
var_dump(Pdfium\Document::open(__DIR__ . '/fixtures/text-3pages.pdf')->pageCount());
try { Pdfium\Document::open('/etc/hostname'); } catch (Pdfium\FileException $e) { echo "FileException\n"; }
?>
--EXPECT--
int(3)
FileException
