--TEST--
Page::text() returns UTF-8 page text, reports permissions without enforcing them
--EXTENSIONS--
pdfium
--FILE--
<?php
$f = __DIR__ . '/fixtures/';
foreach (Pdfium\Document::fromFile($f . 'text-3pages.pdf')->pages() as $p) echo trim($p->text()), "\n";
$t = Pdfium\Document::fromFile($f . 'unicode.pdf')->page(0)->text();
var_dump(preg_match('//u', $t), str_contains($t, "\u{1F600}"), str_contains($t, 'A'), str_contains($t, "\xED\xA0\x80"));
echo trim(Pdfium\Document::fromFile($f . 'encrypted-owner.pdf')->page(0)->text()), "\n";
var_dump(Pdfium\Document::fromFile($f . 'rotated.pdf')->page(0)->text());
?>
--EXPECT--
Page 1 alpha
Page 2 beta
Page 3 gamma
int(1)
bool(true)
bool(true)
bool(false)
Page 1 alpha
string(0) ""
