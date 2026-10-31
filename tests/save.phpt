--TEST--
toString() and save() serialise a document, its extracts and merges
--EXTENSIONS--
pdfium
--FILE--
<?php
$f = __DIR__ . '/fixtures/';
$doc = Pdfium\Document::open($f . 'text-3pages.pdf');
$bytes = $doc->toString();
var_dump(str_starts_with($bytes, '%PDF-'), Pdfium\Document::fromString($bytes)->pageCount());
$out = sys_get_temp_dir() . '/pdfium-save-test.pdf';
$doc->extractPages([1])->save($out);
var_dump(trim(Pdfium\Document::open($out)->page(0)->text()));
unlink($out);
try { $doc->save('/nonexistent-dir/x.pdf'); } catch (Pdfium\FileException $e) { echo "FileException\n"; }
var_dump(Pdfium\Document::fromString(Pdfium\Document::merge([$doc, $doc])->toString())->pageCount());
?>
--EXPECT--
bool(true)
int(3)
string(11) "Page 2 beta"
FileException
int(6)
