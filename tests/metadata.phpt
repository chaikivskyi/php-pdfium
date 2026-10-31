--TEST--
metadata returns the non-empty Info entries as UTF-8
--EXTENSIONS--
pdfium
--FILE--
<?php
$m = Pdfium\Document::open(__DIR__ . '/fixtures/text-3pages.pdf')->metadata();
ksort($m);
var_dump($m);
?>
--EXPECT--
array(3) {
  ["author"]=>
  string(16) "Ünïcode Author"
  ["creationDate"]=>
  string(17) "D:20261031120000Z"
  ["title"]=>
  string(13) "Fixture Title"
}
