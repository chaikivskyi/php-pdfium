--TEST--
javaScripts() throws MemoryException, not a fatal error, when a script would not fit under memory_limit, returns scripts that together fit, and returns an empty list for a document without scripts however little memory is left
--EXTENSIONS--
pdfium
--INI--
memory_limit=64M
--FILE--
<?php
require __DIR__ . '/pdf.inc';
$doc = Pdfium\Document::fromString(js_pdf('(small) 6 0 R (big) 7 0 R', [
    '<< /S /JavaScript /JS (ok) >>',
    '<< /S /JavaScript /JS (' . str_repeat('A', 12 << 20) . ') >>',
]));
try { $doc->javaScripts(); } catch (Pdfium\MemoryException $e) { echo $e->getMessage(), "\n"; }
unset($doc);
var_dump(Pdfium\Document::fromString(js_pdf('(a) 6 0 R', ['<< /S /JavaScript /JS (ok) >>']))->javaScripts()[0]->script);
ini_set('memory_limit', '256M');
$names = '';
$objs = [];
for ($i = 0; $i < 12; $i++) {
    $names .= sprintf('(s%02d) %d 0 R ', $i, 6 + $i);
    $objs[] = '<< /S /JavaScript /JS (' . str_repeat('B', 1 << 20) . ') >>';
}
$many = Pdfium\Document::fromString(js_pdf($names, $objs));
unset($names, $objs);
ini_set('memory_limit', '64M');
$list = $many->javaScripts();
var_dump(count($list), strlen($list[11]->script));
unset($list, $many);
$none = Pdfium\Document::fromFile(__DIR__ . '/fixtures/tiny.pdf');
ini_set('memory_limit', (string) (memory_get_usage(true) + (1 << 20)));
var_dump($none->javaScripts());
?>
--EXPECT--
The document's JavaScript needs more memory than memory_limit leaves
string(2) "ok"
int(12)
int(1048576)
array(0) {
}
