--TEST--
A broken script list (a /Kids loop, nodes sharing a kid 10 levels deep, 40 levels of nesting, /JavaScript of the wrong type) or a NUL in a script neither crashes nor hangs javaScripts()
--EXTENSIONS--
pdfium
--FILE--
<?php
require __DIR__ . '/pdf.inc';
$leaf = '<< /S /JavaScript /JS (x) >>';
$deep = [];
for ($i = 0; $i < 40; $i++) {
    $deep[] = '<< /Kids [' . (7 + $i) . ' 0 R] >>';
}
$deep[] = '<< /Names [(a) 47 0 R] >>';
$deep[] = $leaf;
$shared = ['<< /Kids [7 0 R 18 0 R] >>'];
for ($i = 0; $i < 11; $i++) {
    $shared[] = $i === 10 ? '<< >>' : '<< /Kids [' . (8 + $i) . ' 0 R ' . (8 + $i) . ' 0 R] >>';
}
$shared[] = '<< /Names [(a) 19 0 R] >>';
$shared[] = $leaf;
$cases = [
    'loop' => nav_pdf('/Names << /JavaScript 6 0 R >>', [
        '<< /Kids [6 0 R 7 0 R] >>',
        '<< /Names [(a) 8 0 R] >>',
        $leaf,
    ]),
    'shared kids' => nav_pdf('/Names << /JavaScript 6 0 R >>', $shared),
    'deep' => nav_pdf('/Names << /JavaScript 6 0 R >>', $deep),
    'wrong type' => nav_pdf('/Names << /JavaScript 5 >>'),
];
foreach ($cases as $name => $pdf) {
    $doc = Pdfium\Document::fromString($pdf);
    try {
        echo $name, ': ', count($doc->javaScripts()), "\n";
    } catch (Pdfium\PdfiumException $e) {
        echo $name, ': ', get_class($e), "\n";
    }
}
$nul = Pdfium\Document::fromString(js_pdf('(n) 6 0 R', ['<< /S /JavaScript /JS (before\000after) >>']))->javaScripts();
var_dump(bin2hex($nul[0]->script));
?>
--EXPECT--
loop: 1
shared kids: 1
deep: 0
wrong type: 0
string(24) "6265666f7265006166746572"
