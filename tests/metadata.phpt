--TEST--
metadata() returns the Info entries as UTF-8, with null for empty or missing ones
--EXTENSIONS--
pdfium
--FILE--
<?php
var_dump(Pdfium\Document::fromFile(__DIR__ . '/fixtures/text-3pages.pdf')->metadata());
?>
--EXPECTF--
object(Pdfium\Metadata)#%d (8) {
  ["title"]=>
  string(13) "Fixture Title"
  ["author"]=>
  string(16) "Ünïcode Author"
  ["subject"]=>
  NULL
  ["keywords"]=>
  NULL
  ["creator"]=>
  NULL
  ["producer"]=>
  NULL
  ["creationDate"]=>
  string(17) "D:20261031120000Z"
  ["modDate"]=>
  NULL
}
