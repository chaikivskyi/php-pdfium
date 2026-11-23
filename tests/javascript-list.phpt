--TEST--
javaScripts() lists the document-level scripts with their names, leaving out entries that are not JavaScript
--EXTENSIONS--
pdfium
--FILE--
<?php
require __DIR__ . '/pdf.inc';
$js = fn (string $src) => "<< /S /JavaScript /JS ($src) >>";

var_dump(Pdfium\Document::fromFile(__DIR__ . '/fixtures/text-3pages.pdf')->javaScripts());

$doc = Pdfium\Document::fromString(js_pdf('(first) 6 0 R (second) 7 0 R', [
    $js('app.alert\(1\);'),
    $js('var x = 2;'),
]));
$list = $doc->javaScripts();
var_dump(array_keys($list));
var_dump($list[0] instanceof Pdfium\JavaScript);
var_dump($list[0]->name, $list[0]->script);
var_dump($list[1]->name, $list[1]->script);
var_dump($doc->javaScripts() == $list);

$mixed = Pdfium\Document::fromString(js_pdf('(a) 6 0 R (b) 7 0 R (c) 8 0 R (d) 42 (e) 9 0 R', [
    $js('one'),
    '<< /S /URI /URI (http://example.com/) >>',
    '<< /S /JavaScript >>',
    $js('two'),
]))->javaScripts();
var_dump(array_keys($mixed));
var_dump($mixed[0]->name, $mixed[1]->name);

$stream = Pdfium\Document::fromString(js_pdf('(s) 6 0 R', [
    '<< /S /JavaScript /JS 7 0 R >>',
    stream_obj('from a stream'),
]))->javaScripts();
var_dump($stream[0]->script);

$empty = Pdfium\Document::fromString(js_pdf('(e) 6 0 R', ['<< /S /JavaScript /JS () >>']))->javaScripts();
var_dump(count($empty), $empty[0]->script);

$utf = Pdfium\Document::fromString(js_pdf('<FEFF0441043A04400438043F0442> 6 0 R', [
    '<< /S /JavaScript /JS <FEFF0061003D002704370027003B> >>',
]))->javaScripts();
var_dump($utf[0]->name, $utf[0]->script);

$s = unserialize(serialize($list[0]));
var_dump($s == $list[0]);
try { $list[0]->name = 'x'; } catch (Error $e) { echo get_class($e), "\n"; }
unset($doc);
var_dump($list[0]->script);
?>
--EXPECT--
array(0) {
}
array(2) {
  [0]=>
  int(0)
  [1]=>
  int(1)
}
bool(true)
string(5) "first"
string(13) "app.alert(1);"
string(6) "second"
string(10) "var x = 2;"
bool(true)
array(2) {
  [0]=>
  int(0)
  [1]=>
  int(1)
}
string(1) "a"
string(1) "e"
string(13) "from a stream"
int(1)
string(0) ""
string(12) "скрипт"
string(7) "a='з';"
bool(true)
Error
string(13) "app.alert(1);"
